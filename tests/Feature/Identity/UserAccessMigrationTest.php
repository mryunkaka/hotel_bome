<?php

namespace Tests\Feature\Identity;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAccessMigrationTest extends TestCase
{
    public function test_user_access_migration_rolls_back_its_tables_and_triggers(): void
    {
        $connection = 'user_access_migration_test';
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
            Schema::create('properties', function (Blueprint $table): void {
                $table->id();
            });

            $migration = require database_path('migrations/2026_10_18_000003_create_user_access_tables.php');
            $migration->up();

            $this->assertSame(2, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertTrue(Schema::hasTable('property_user'));
            $this->assertTrue(Schema::hasTable('user_access_events'));

            $migration->down();

            $this->assertSame(0, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertFalse(Schema::hasTable('user_access_events'));
            $this->assertFalse(Schema::hasTable('property_user'));
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousConnection]);
        }
    }
}
