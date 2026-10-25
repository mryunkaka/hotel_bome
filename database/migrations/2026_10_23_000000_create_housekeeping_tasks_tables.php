<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->string('instruction', 500)->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'id']);
            $table->foreign(['property_id', 'room_id'])->references(['property_id', 'id'])->on('rooms')->restrictOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'status', 'created_at']);
            $table->index(['property_id', 'assigned_to', 'status']);
        });

        Schema::create('housekeeping_task_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('task_id');
            $table->enum('event_type', ['created', 'assigned', 'status_changed']);
            $table->enum('from_status', ['open', 'in_progress', 'completed', 'cancelled'])->nullable();
            $table->enum('to_status', ['open', 'in_progress', 'completed', 'cancelled'])->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('assigned_to_before')->nullable();
            $table->unsignedBigInteger('assigned_to_after')->nullable();
            $table->foreign('assigned_to_before')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_after')->references('id')->on('users')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at');
            $table->foreign(['property_id', 'task_id'])->references(['property_id', 'id'])->on('housekeeping_tasks')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'task_id', 'id']);
            $table->index(['property_id', 'to_status', 'created_at']);
        });

        foreach (['housekeeping_task_events'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $table.'_no_'.strtolower($operation);
                $sql = match (DB::getDriverName()) {
                    'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} BEGIN SELECT RAISE(ABORT, 'Operational history is immutable'); END",
                    'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operational history is immutable'",
                    default => throw new RuntimeException('Unsupported database driver for immutable housekeeping history.'),
                };
                DB::unprepared($sql);
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'housekeeping_task_events_no_'.strtolower($operation);
            DB::unprepared(match ($driver) {
                'sqlite', 'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                default => throw new RuntimeException('Unsupported database driver for housekeeping history rollback.'),
            });
        }

        Schema::dropIfExists('housekeeping_task_events');
        Schema::dropIfExists('housekeeping_tasks');
    }
};
