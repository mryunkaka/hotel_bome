<?php

namespace Tests\Feature\Properties;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HousekeepingTaskMigrationTest extends TestCase
{
    public function test_migration_creates_and_removes_queue_schema_and_triggers(): void
    {
        $connection = 'housekeeping_migration_test';
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
                $table->unique(['property_id', 'id']);
            });

            $migration = require database_path('migrations/2026_10_23_000000_create_housekeeping_tasks_tables.php');
            $migration->up();
            $this->assertTrue(Schema::hasTable('housekeeping_tasks'));
            $this->assertTrue(Schema::hasTable('housekeeping_task_events'));
            $this->assertSame(2, DB::table('sqlite_master')->where('type', 'trigger')->count());

            $migration->down();
            $this->assertFalse(Schema::hasTable('housekeeping_task_events'));
            $this->assertFalse(Schema::hasTable('housekeeping_tasks'));
            $this->assertSame(0, DB::table('sqlite_master')->where('type', 'trigger')->count());
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousConnection]);
        }
    }
}
