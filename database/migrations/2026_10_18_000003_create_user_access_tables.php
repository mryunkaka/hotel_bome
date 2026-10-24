<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->enum('role', ['hotel_admin', 'front_desk', 'housekeeping', 'finance', 'operations']);
            $table->timestamps();
            $table->unique(['user_id', 'property_id']);
            $table->index(['property_id', 'role']);
        });

        Schema::create('user_access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('event', ['approved', 'rejected']);
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
        Schema::dropIfExists('user_access_events');
        Schema::dropIfExists('property_user');
    }

    private function createImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'user_access_events_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON user_access_events BEGIN SELECT RAISE(ABORT, 'User access audit is immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON user_access_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User access audit is immutable'",
                default => throw new RuntimeException("User access audit triggers are unsupported for database driver [{$driver}]."),
            };
            DB::unprepared($sql);
        }
    }

    private function dropImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'user_access_events_no_'.strtolower($operation);
            $sql = match ($driver) {
                'sqlite', 'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                default => throw new RuntimeException("User access audit triggers are unsupported for database driver [{$driver}]."),
            };
            DB::unprepared($sql);
        }
    }
};
