<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_membership_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->enum('event', ['assigned', 'role_changed', 'revoked']);
            $table->enum('previous_role', ['hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations'])->nullable();
            $table->enum('role', ['hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations'])->nullable();
            $table->string('reason', 500);
            $table->timestamp('created_at');
            $table->index(['user_id', 'id']);
            $table->index(['property_id', 'id']);
        });

        $this->createImmutableTriggers();
    }

    public function down(): void
    {
        $this->dropImmutableTriggers();
        Schema::dropIfExists('property_membership_events');
    }

    private function createImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'property_membership_events_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON property_membership_events BEGIN SELECT RAISE(ABORT, 'Property membership audit is immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON property_membership_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Property membership audit is immutable'",
                default => throw new RuntimeException("Property membership audit triggers are unsupported for database driver [{$driver}]."),
            };
            DB::unprepared($sql);
        }
    }

    private function dropImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'property_membership_events_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite', 'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                default => throw new RuntimeException("Property membership audit triggers are unsupported for database driver [{$driver}]."),
            };
            DB::unprepared($sql);
        }
    }
};
