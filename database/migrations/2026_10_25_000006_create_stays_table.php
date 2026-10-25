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
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER stay_events_no_update
            BEFORE UPDATE ON stay_events
            BEGIN
                SELECT RAISE(ABORT, 'stay_events is immutable: UPDATE not allowed');
            END;

            CREATE TRIGGER stay_events_no_delete
            BEFORE DELETE ON stay_events
            BEGIN
                SELECT RAISE(ABORT, 'stay_events is immutable: DELETE not allowed');
            END;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS stay_events_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS stay_events_no_delete');
        Schema::dropIfExists('stay_events');
        Schema::dropIfExists('stays');
    }
};
