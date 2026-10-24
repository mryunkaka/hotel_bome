<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineering_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('created_by');
            $table->string('code', 32);
            $table->string('name', 160);
            $table->string('category', 100);
            $table->string('location', 160)->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->enum('status', ['operational', 'under_repair', 'retired'])->default('operational');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['property_id', 'code']);
            $table->unique(['property_id', 'id']);
            $table->foreign('property_id')->references('id')->on('properties')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'is_active', 'status']);
        });

        Schema::create('engineering_asset_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('event_type', 24);
            $table->json('before_data')->nullable();
            $table->json('after_data');
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at');
            $table->foreign(['property_id', 'asset_id'])->references(['property_id', 'id'])->on('engineering_assets')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'asset_id', 'id']);
        });

        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'engineering_asset_events_no_'.strtolower($operation);
            DB::unprepared(match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON engineering_asset_events BEGIN SELECT RAISE(ABORT, 'Engineering asset history is immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON engineering_asset_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Engineering asset history is immutable'",
                default => throw new RuntimeException('Unsupported database driver for immutable asset history.'),
            });
        }
    }

    public function down(): void
    {
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'engineering_asset_events_no_'.strtolower($operation);
            DB::unprepared(match (DB::getDriverName()) {
                'sqlite', 'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                default => throw new RuntimeException('Unsupported database driver for asset history rollback.'),
            });
        }
        Schema::dropIfExists('engineering_asset_events');
        Schema::dropIfExists('engineering_assets');
    }
};
