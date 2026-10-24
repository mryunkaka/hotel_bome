<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('auth_version')->default(0);
        });

        Schema::create('user_account_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('event', ['suspended', 'reactivated']);
            $table->string('reason', 500);
            $table->timestamp('created_at');
            $table->index(['user_id', 'id']);
        });

        $this->createImmutableTriggers();
    }

    public function down(): void
    {
        $this->dropImmutableTriggers();
        Schema::dropIfExists('user_account_events');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('auth_version');
        });
    }

    private function createImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'user_account_events_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON user_account_events BEGIN SELECT RAISE(ABORT, 'User account audit is immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON user_account_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User account audit is immutable'",
                default => throw new RuntimeException("User account audit triggers are unsupported for database driver [{$driver}]."),
            };
            DB::unprepared($sql);
        }
    }

    private function dropImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'user_account_events_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite', 'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                default => throw new RuntimeException("User account audit triggers are unsupported for database driver [{$driver}]."),
            };
            DB::unprepared($sql);
        }
    }
};
