<?php

namespace Tests\Feature\Identity;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAccountLifecycleMigrationTest extends TestCase
{
    public function test_account_lifecycle_migration_adds_version_immutable_audit_and_rolls_back(): void
    {
        $connection = 'user_account_lifecycle_migration_test';
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
                $table->string('name');
            });
            $migration = require database_path('migrations/2026_10_19_000001_create_user_account_lifecycle_table.php');
            $migration->up();

            $this->assertTrue(Schema::hasColumn('users', 'auth_version'));
            $this->assertSame(2, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            DB::table('users')->insert([['id' => 1, 'name' => 'Actor'], ['id' => 2, 'name' => 'Target']]);
            DB::table('user_account_events')->insert([
                'actor_id' => 1,
                'user_id' => 2,
                'event' => 'suspended',
                'reason' => 'Test reason',
                'created_at' => now(),
            ]);

            try {
                DB::table('user_account_events')->where('user_id', 2)->update(['reason' => 'changed']);
                $this->fail('Account lifecycle audit updates must be rejected.');
            } catch (QueryException) {
                $this->assertDatabaseHas('user_account_events', ['user_id' => 2, 'reason' => 'Test reason']);
            }
            try {
                DB::table('user_account_events')->where('user_id', 2)->delete();
                $this->fail('Account lifecycle audit deletes must be rejected.');
            } catch (QueryException) {
                $this->assertDatabaseHas('user_account_events', ['user_id' => 2, 'reason' => 'Test reason']);
            }

            $migration->down();
            $this->assertSame(0, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertFalse(Schema::hasTable('user_account_events'));
            $this->assertFalse(Schema::hasColumn('users', 'auth_version'));
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousConnection]);
        }
    }
}
