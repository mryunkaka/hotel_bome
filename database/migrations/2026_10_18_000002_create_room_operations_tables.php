<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unique(['property_id', 'id']);
            $table->index(['property_id', 'is_active', 'id']);
        });

        Schema::create('room_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id');
            $table->enum('previous_status', ['dirty', 'clean', 'inspected'])->nullable();
            $table->enum('status', ['dirty', 'clean', 'inspected']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at');
            $table->foreign(['property_id', 'room_id'])->references(['property_id', 'id'])->on('rooms')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['property_id', 'room_id', 'id']);
        });

        Schema::create('room_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason', 500);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at');
            $table->foreign(['property_id', 'room_id'])->references(['property_id', 'id'])->on('rooms')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['property_id', 'room_id', 'id']);
            $table->index(['property_id', 'room_id', 'start_date', 'end_date']);
        });

        Schema::create('room_block_cancellations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('room_block_id');
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('created_at');
            $table->string('reason', 500);
            $table->foreign(['property_id', 'room_id'])->references(['property_id', 'id'])->on('rooms')->restrictOnDelete();
            $table->foreign(['property_id', 'room_id', 'room_block_id'], 'rbc_block_fk')->references(['property_id', 'room_id', 'id'])->on('room_blocks')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique('room_block_id');
            $table->index(['property_id', 'room_id']);
        });

        $createdAt = now();
        DB::table('rooms')->orderBy('id')->chunk(500, function ($rooms) use ($createdAt): void {
            DB::table('room_status_events')->insert($rooms->map(fn ($room): array => [
                'property_id' => $room->property_id,
                'room_id' => $room->id,
                'previous_status' => null,
                'status' => 'dirty',
                'actor_id' => null,
                'created_at' => $createdAt,
            ])->all());
        });

        $this->createImmutableTriggers();
    }

    public function down(): void
    {
        $this->dropImmutableTriggers();

        Schema::dropIfExists('room_block_cancellations');
        Schema::dropIfExists('room_blocks');
        Schema::dropIfExists('room_status_events');

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['property_id', 'id']);
            $table->dropIndex(['property_id', 'is_active', 'id']);
        });
    }

    private function createImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['room_status_events', 'room_blocks', 'room_block_cancellations'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $table.'_no_'.strtolower($operation);
                $sql = match ($driver) {
                    'sqlite' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} BEGIN SELECT RAISE(ABORT, 'Operational history is immutable'); END",
                    'mysql' => "CREATE TRIGGER {$name} BEFORE {$operation} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Operational history is immutable'",
                    default => throw new RuntimeException("Append-only history triggers are unsupported for database driver [{$driver}]."),
                };
                DB::unprepared($sql);
            }
        }
    }

    private function dropImmutableTriggers(): void
    {
        $driver = DB::getDriverName();
        foreach (['room_status_events', 'room_blocks', 'room_block_cancellations'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $name = $table.'_no_'.strtolower($operation);
                $sql = match ($driver) {
                    'sqlite' => "DROP TRIGGER IF EXISTS {$name}",
                    'mysql' => "DROP TRIGGER IF EXISTS {$name}",
                    default => throw new RuntimeException("Append-only history triggers are unsupported for database driver [{$driver}]."),
                };
                DB::unprepared($sql);
            }
        }
    }
};
