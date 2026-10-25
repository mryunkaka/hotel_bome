<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GUEST_ROLES = ['receptionist', 'supervisor', 'owner'];

    public function up(): void
    {
        foreach ([
            ['property_user', 'role', false],
            ['user_access_events', 'role', true],
            ['property_membership_events', 'previous_role', true],
            ['property_membership_events', 'role', true],
        ] as [$tableName, $column, $nullable]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $nullable): void {
                $definition = $table->string($column, 32);
                if ($nullable) {
                    $definition->nullable()->change();
                } else {
                    $definition->change();
                }
            });
        }

        Schema::table('guests', function (Blueprint $table): void {
            $table->boolean('is_validated')->default(false);
        });

        Schema::table('guest_events', function (Blueprint $table): void {
            $table->string('reason', 500)->nullable();
        });

        Schema::create('guest_access_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('event', 40);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['property_id', 'id']);
            $table->index(['actor_id', 'id']);
        });

        Schema::create('guest_deactivation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending');
            $table->string('decision_reason', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['property_id', 'status', 'id']);
            $table->index(['guest_id', 'status']);
        });

        foreach (['user_access_events', 'property_membership_events', 'guest_access_events', 'guest_events'] as $table) {
            $this->createImmutableTriggers($table, $table === 'guest_events' ? 'Guest profile audit is immutable' : 'Guest access audit is immutable');
        }
        $this->createRoleValidationTriggers();
    }

    public function down(): void
    {
        foreach ([
            ['property_user', 'role'],
            ['user_access_events', 'role'],
            ['property_membership_events', 'previous_role'],
            ['property_membership_events', 'role'],
        ] as [$table, $column]) {
            if (DB::table($table)->whereIn($column, self::GUEST_ROLES)->exists()) {
                throw new RuntimeException('Cannot roll back guest roles while memberships or audit events use them.');
            }
        }

        $this->dropRoleValidationTriggers();
        foreach (['user_access_events', 'property_membership_events', 'guest_access_events', 'guest_events'] as $table) {
            $this->dropImmutableTriggers($table);
        }
        Schema::dropIfExists('guest_deactivation_requests');
        Schema::dropIfExists('guest_access_events');
        Schema::table('guest_events', fn (Blueprint $table) => $table->dropColumn('reason'));
        Schema::table('guests', fn (Blueprint $table) => $table->dropColumn('is_validated'));

        foreach ([
            ['property_user', 'role', false],
            ['user_access_events', 'role', true],
            ['property_membership_events', 'previous_role', true],
            ['property_membership_events', 'role', true],
        ] as [$tableName, $column, $nullable]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $nullable): void {
                $definition = $table->enum($column, ['hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations']);
                if ($nullable) {
                    $definition->nullable()->change();
                } else {
                    $definition->change();
                }
            });
        }

        $this->createImmutableTriggers('user_access_events', 'User access audit is immutable');
        $this->createImmutableTriggers('property_membership_events', 'Property membership audit is immutable');
    }

    private function createRoleValidationTriggers(): void
    {
        $roles = "'hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations', 'receptionist', 'supervisor', 'owner'";
        $specifications = [
            'property_user' => ['role' => false],
            'user_access_events' => ['role' => true],
            'property_membership_events' => ['role' => true, 'previous_role' => true],
        ];
        foreach ($specifications as $table => $columns) {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $conditions = [];
                foreach ($columns as $column => $nullable) {
                    $field = 'NEW.'.$column;
                    $conditions[] = $nullable
                        ? "({$field} IS NOT NULL AND {$field} NOT IN ({$roles}))"
                        : "{$field} NOT IN ({$roles})";
                }
                $condition = implode(' OR ', $conditions);
                $name = $table.'_role_validate_'.strtolower($operation);
                $sql = match (DB::getDriverName()) {
                    'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} WHEN {$condition} BEGIN SELECT RAISE(ABORT, 'Unsupported membership role'); END",
                    'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} FOR EACH ROW BEGIN IF {$condition} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unsupported membership role'; END IF; END",
                    default => throw new RuntimeException('Role validation triggers are unsupported for this database driver.'),
                };
                DB::unprepared($sql);
            }
        }
    }

    private function dropRoleValidationTriggers(): void
    {
        foreach (['property_user', 'user_access_events', 'property_membership_events'] as $table) {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $sql = 'DROP TRIGGER IF EXISTS '.$table.'_role_validate_'.strtolower($operation);
                DB::unprepared($sql);
            }
        }
    }

    private function createImmutableTriggers(string $table, string $message): void
    {
        $this->dropImmutableTriggers($table);
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = $table.'_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} BEGIN SELECT RAISE(ABORT, '{$message}'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
                default => throw new RuntimeException("Guest audit triggers are unsupported for database driver [{$driver}]."),
            };
            // @phpstan-ignore argument.type (identifiers and message come from fixed migration constants)
            DB::unprepared($sql);
        }
    }

    private function dropImmutableTriggers(string $table): void
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['sqlite', 'mysql'], true)) {
            throw new RuntimeException("Guest audit triggers are unsupported for database driver [{$driver}].");
        }
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $sql = 'DROP TRIGGER IF EXISTS '.$table.'_no_'.strtolower($operation);
            // @phpstan-ignore argument.type (table and operation come from fixed migration constants)
            DB::unprepared($sql);
        }
    }
};
