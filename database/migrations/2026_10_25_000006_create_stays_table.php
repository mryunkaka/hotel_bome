<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('guest_id'); // FK reference only, D-007 open
            $table->unsignedBigInteger('room_id');
            $table->string('status', 30)->default('in_house'); // in_house, checked_out
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->index('property_id');
            $table->index('reservation_id');
            $table->index(['room_id', 'status']);
        });

        Schema::create('stay_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stay_id')->constrained('stays')->restrictOnDelete();
            $table->string('event_type', 30); // check_in, check_out, room_move
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->json('changes')->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at')->useCurrent();
        });

        // Immutable triggers for stay_events
        foreach (['UPDATE', 'DELETE'] as $operation) {
            $name = 'stay_events_no_'.strtolower($operation);
            $message = "stay_events is immutable: {$operation} not allowed";
            $sql = match (DB::getDriverName()) {
                'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON stay_events BEGIN SELECT RAISE(ABORT, '{$message}'); END",
                'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON stay_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{$message}'",
                default => throw new RuntimeException('Stay event triggers are unsupported for this database driver.'),
            };
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS stay_events_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS stay_events_no_delete');
        Schema::dropIfExists('stay_events');
        Schema::dropIfExists('stays');
    }
};
