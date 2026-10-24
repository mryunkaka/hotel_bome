<?php

namespace Tests\Feature\Properties;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoomOperationsMigrationTest extends TestCase
{
    public function test_existing_rooms_are_backfilled_as_dirty_without_actor_identity(): void
    {
        $connection = 'room_migration_test';
        $previousConnection = DB::getDefaultConnection();
        config([
            'database.connections.'.$connection => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => $connection,
        ]);
        DB::purge($connection);

        try {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
            });
            Schema::create('rooms', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->boolean('is_active')->default(true);
            });
            DB::table('rooms')->insert([
                ['property_id' => 10, 'is_active' => true],
                ['property_id' => 20, 'is_active' => false],
            ]);

            $migration = require database_path('migrations/2026_10_18_000002_create_room_operations_tables.php');
            $migration->up();

            $this->assertSame(6, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertDatabaseCount('room_status_events', 2, $connection);
            $this->assertDatabaseHas('room_status_events', [
                'property_id' => 10,
                'room_id' => 1,
                'previous_status' => null,
                'status' => 'dirty',
                'actor_id' => null,
            ], $connection);
            $this->assertDatabaseHas('room_status_events', [
                'property_id' => 20,
                'room_id' => 2,
                'previous_status' => null,
                'status' => 'dirty',
                'actor_id' => null,
            ], $connection);

            $migration->down();
            $this->assertSame(0, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertFalse(Schema::hasTable('room_status_events'));
            $this->assertFalse(Schema::hasTable('room_blocks'));
            $this->assertFalse(Schema::hasTable('room_block_cancellations'));
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousConnection]);
        }
    }
}
