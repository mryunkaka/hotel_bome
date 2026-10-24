<?php

namespace Tests\Feature\Identity;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyMembershipEventsMigrationTest extends TestCase
{
    public function test_membership_audit_migration_is_immutable_and_rolls_back_its_schema(): void
    {
        $connection = 'property_membership_events_migration_test';
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

            $migration = require database_path('migrations/2026_10_18_000004_create_property_membership_events_table.php');
            $migration->up();
            $this->assertSame(2, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());

            DB::table('users')->insert([['id' => 1], ['id' => 2]]);
            DB::table('properties')->insert(['id' => 1]);
            $eventId = DB::table('property_membership_events')->insertGetId([
                'actor_id' => 1,
                'user_id' => 2,
                'property_id' => 1,
                'event' => 'assigned',
                'previous_role' => null,
                'role' => 'housekeeping',
                'reason' => 'Test reason',
                'created_at' => now(),
            ]);

            try {
                DB::table('property_membership_events')->where('id', $eventId)->update(['reason' => 'changed']);
                $this->fail('Membership audit updates must be rejected.');
            } catch (QueryException) {
                $this->assertDatabaseHas('property_membership_events', ['id' => $eventId, 'reason' => 'Test reason']);
            }
            try {
                DB::table('property_membership_events')->where('id', $eventId)->delete();
                $this->fail('Membership audit deletes must be rejected.');
            } catch (QueryException) {
                $this->assertDatabaseHas('property_membership_events', ['id' => $eventId, 'reason' => 'Test reason']);
            }

            $migration->down();
            $this->assertSame(0, DB::connection($connection)->table('sqlite_master')->where('type', 'trigger')->count());
            $this->assertFalse(Schema::hasTable('property_membership_events'));
        } finally {
            DB::purge($connection);
            config(['database.default' => $previousConnection]);
        }
    }
}
