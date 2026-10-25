<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('room_id');
            $table->foreignId('rate_snapshot_id')->constrained('rate_snapshots')->restrictOnDelete();
            $table->string('status', 20)->default('confirmed'); // confirmed, checked_in, checked_out, cancelled, no_show
            $table->boolean('is_walk_in')->default(false);
            $table->timestamps();

            $table->foreign(['property_id', 'room_id'])
                ->references(['property_id', 'id'])
                ->on('rooms')
                ->restrictOnDelete();

            $table->index(['property_id', 'status']);
            $table->index('guest_id');
        });

        // Add composite unique on rooms for the FK
        // rooms already has unique(property_id, code) but we need (property_id, id)
        // Check if it exists first
        try {
            Schema::table('rooms', function (Blueprint $table) {
                $table->unique(['property_id', 'id']);
            });
        } catch (Exception) {
            // Already exists
        }

        Schema::create('reservation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index('reservation_id');
        });

        // Immutable event log
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'reservation_events_no_'.strtolower($operation);
            $sql = match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON reservation_events BEGIN SELECT RAISE(ABORT, 'reservation_events rows are immutable'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON reservation_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'reservation_events rows are immutable'",
                default => throw new RuntimeException('Reservation event triggers are unsupported for this database driver.'),
            };
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS reservation_events_no_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS reservation_events_no_update');
        Schema::dropIfExists('reservation_events');
        Schema::dropIfExists('reservations');
    }
};
