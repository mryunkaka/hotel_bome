<?php

namespace Tests\Feature\Properties;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkOrderMigrationTest extends TestCase
{
    public function test_work_order_migration_rolls_back_tables_and_immutable_triggers(): void
    {
        $connection = 'work_order_migration_test';
        $previousConnection = DB::getDefaultConnection();
        config([
            'database.connections.'.$connection => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
            'database.default' => $connection,
        ]);
        DB::purge($connection);

        try {
            Schema::create('users', fn (Blueprint $table) => $table->id());
            Schema::create('properties', function (Blueprint $table): void {
                $table->id();
            });
            Schema::create('rooms', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->unique(['property_id', 'id']);
            });

            $migration = require database_path('migrations/2026_10_23_000001_create_work_orders_tables.php');
            $migration->up();
            $this->assertTrue(Schema::hasTable('work_orders'));
            $this->assertTrue(Schema::hasTable('work_order_events'));
            $this->assertSame(2, DB::table('sqlite_master')->where('type', 'trigger')->count());

            $migration->down();
            $this->assertFalse(Schema::hasTable('work_order_events'));
            $this->assertFalse(Schema::hasTable('work_orders'));
            $this->assertSame(0, DB::table('sqlite_master')->where('type', 'trigger')->count());
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousConnection]);
        }
    }
}
