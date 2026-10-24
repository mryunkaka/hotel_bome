<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'cancelled'])->default('open');
            $table->string('title', 160);
            $table->string('description', 1000)->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'id']);
            $table->foreign(['property_id', 'room_id'])->references(['property_id', 'id'])->on('rooms')->restrictOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->restrictOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'status', 'created_at']);
            $table->index(['property_id', 'assigned_to', 'status']);
        });

        Schema::create('work_order_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('work_order_id');
            $table->enum('event_type', ['created', 'assigned', 'status_changed']);
            $table->enum('from_status', ['open', 'in_progress', 'resolved', 'cancelled'])->nullable();
            $table->enum('to_status', ['open', 'in_progress', 'resolved', 'cancelled'])->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('assigned_to_before')->nullable();
            $table->unsignedBigInteger('assigned_to_after')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at');
            $table->foreign(['property_id', 'work_order_id'])->references(['property_id', 'id'])->on('work_orders')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_before')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_after')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'work_order_id', 'id']);
            $table->index(['property_id', 'to_status', 'created_at']);
        });

        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'work_order_events_no_'.strtolower($operation);
            DB::unprepared(match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON work_order_events BEGIN SELECT RAISE(ABORT, 'Work order history is immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON work_order_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Work order history is immutable'",
                default => throw new RuntimeException('Unsupported database driver for immutable work order history.'),
            });
        }
    }

    public function down(): void
    {
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'work_order_events_no_'.strtolower($operation);
            DB::unprepared(match (DB::getDriverName()) {
                'sqlite', 'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                default => throw new RuntimeException('Unsupported database driver for work order history rollback.'),
            });
        }

        Schema::dropIfExists('work_order_events');
        Schema::dropIfExists('work_orders');
    }
};
