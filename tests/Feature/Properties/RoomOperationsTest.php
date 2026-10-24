<?php

namespace Tests\Feature\Properties;

use App\Enums\HousekeepingStatus;
use App\Models\Property;
use App\Models\User;
use App\Services\Properties\RoomOperations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_room_starts_dirty_with_creation_actor_in_status_history(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('properties.rooms.store', $property), [
                'room_type_id' => $roomTypeId,
                'code' => '101',
            ])
            ->assertRedirect(route('properties.rooms.index', $property));

        $roomId = (int) DB::table('rooms')->where('property_id', $property->id)->value('id');
        $this->assertDatabaseHas('room_status_events', [
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => null,
            'status' => 'dirty',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_housekeeping_transitions_append_history_and_reject_dirty_to_inspected(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        $operations = app(RoomOperations::class);

        try {
            $operations->changeHousekeepingStatus($property->id, $roomId, HousekeepingStatus::Inspected, $admin);
            $this->fail('Dirty rooms cannot transition directly to inspected.');
        } catch (\DomainException) {
            $this->assertDatabaseCount('room_status_events', 1);
        }

        $operations->changeHousekeepingStatus($property->id, $roomId, HousekeepingStatus::Clean, $admin);
        $operations->changeHousekeepingStatus($property->id, $roomId, HousekeepingStatus::Inspected, $admin, 'Supervisor check');
        $operations->changeHousekeepingStatus($property->id, $roomId, HousekeepingStatus::Dirty, $admin, 'Guest departure');

        $this->assertDatabaseCount('room_status_events', 4);
        $this->assertDatabaseHas('room_status_events', [
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => 'clean',
            'status' => 'inspected',
            'actor_id' => $admin->id,
            'reason' => 'Supervisor check',
        ]);
        $this->assertSame('dirty', DB::table('room_status_events')->where('room_id', $roomId)->orderByDesc('id')->value('status'));
    }

    public function test_overlapping_inclusive_blocks_remain_unavailable_until_all_are_cancelled(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        $operations = app(RoomOperations::class);
        $firstBlock = $operations->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-12'),
            'Plumbing repair',
            $admin,
        );
        $secondBlock = $operations->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-12'),
            CarbonImmutable::parse('2026-11-14'),
            'Follow-up inspection',
            $admin,
        );

        $this->assertTrue($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-10')));
        $this->assertTrue($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-12')));
        $this->assertTrue($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-14')));
        $this->assertFalse($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-15')));

        $operations->cancelBlock($property->id, $roomId, $firstBlock, 'Repair completed', $admin);
        $this->assertTrue($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-12')));
        $this->assertFalse($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-11')));

        $operations->cancelBlock($property->id, $roomId, $secondBlock, 'Inspection complete', $admin);
        $this->assertFalse($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-12')));
        $this->assertDatabaseHas('room_blocks', ['id' => $firstBlock, 'reason' => 'Plumbing repair']);
        $this->assertDatabaseHas('room_block_cancellations', [
            'room_block_id' => $firstBlock,
            'property_id' => $property->id,
            'room_id' => $roomId,
            'actor_id' => $admin->id,
            'reason' => 'Repair completed',
        ]);
        $this->assertDatabaseCount('room_blocks', 2);
        $this->assertDatabaseCount('room_block_cancellations', 2);
    }

    public function test_room_blocks_reject_invalid_dates_and_blank_reason(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        $operations = app(RoomOperations::class);

        foreach ([
            [CarbonImmutable::parse('2026-11-12'), CarbonImmutable::parse('2026-11-11'), 'Repair'],
            [CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-11'), '   '],
        ] as [$startsAt, $endsAt, $reason]) {
            try {
                $operations->createBlock($property->id, $roomId, $startsAt, $endsAt, $reason, $admin);
                $this->fail('Invalid room block input must be rejected.');
            } catch (\InvalidArgumentException) {
                $this->assertDatabaseCount('room_blocks', 0);
            }
        }
    }

    public function test_room_status_mutation_cannot_cross_property_boundary_or_change_inactive_room(): void
    {
        $admin = $this->platformAdmin();
        [$propertyA, $roomId] = $this->room();
        $propertyB = Property::factory()->create();
        $operations = app(RoomOperations::class);

        try {
            $operations->changeHousekeepingStatus($propertyB->id, $roomId, HousekeepingStatus::Clean, $admin);
            $this->fail('Room status mutation must remain property scoped.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('room_status_events', 1);
        }

        DB::table('properties')->where('id', $propertyA->id)->update(['is_active' => false]);
        $this->expectException(ModelNotFoundException::class);
        $operations->changeHousekeepingStatus($propertyA->id, $roomId, HousekeepingStatus::Clean, $admin);
    }

    public function test_inactive_room_cannot_change_status(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        DB::table('rooms')->where('id', $roomId)->update(['is_active' => false]);

        $this->expectException(ModelNotFoundException::class);
        app(RoomOperations::class)->changeHousekeepingStatus($property->id, $roomId, HousekeepingStatus::Clean, $admin);
    }

    public function test_block_cancellation_foreign_key_cannot_claim_another_property(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        $otherProperty = Property::factory()->create();
        $blockId = app(RoomOperations::class)->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-10'),
            'Maintenance',
            $admin,
        );

        try {
            DB::table('room_block_cancellations')->insert([
                'property_id' => $otherProperty->id,
                'room_id' => $roomId,
                'room_block_id' => $blockId,
                'actor_id' => $admin->id,
                'created_at' => now(),
                'reason' => 'Forged property',
            ]);
            $this->fail('Cancellation event must stay within parent property boundary.');
        } catch (QueryException) {
            $this->assertDatabaseCount('room_block_cancellations', 0);
        }
    }

    public function test_blocks_require_an_active_room_and_cannot_be_cancelled_twice(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        $operations = app(RoomOperations::class);
        $blockId = $operations->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-10'),
            'Maintenance',
            $admin,
        );
        $operations->cancelBlock($property->id, $roomId, $blockId, 'Finished', $admin);

        $this->expectException(ModelNotFoundException::class);
        $operations->cancelBlock($property->id, $roomId, $blockId, 'Duplicate cancel', $admin);
    }

    public function test_database_rejects_unknown_status_and_cross_property_room_blocks(): void
    {
        [$property, $roomId] = $this->room();
        $otherProperty = Property::factory()->create();

        try {
            DB::table('room_status_events')->insert([
                'property_id' => $property->id,
                'room_id' => $roomId,
                'previous_status' => 'dirty',
                'status' => 'ready',
                'created_at' => now(),
            ]);
            $this->fail('Database must reject unknown housekeeping statuses.');
        } catch (QueryException) {
            $this->assertDatabaseCount('room_status_events', 1);
        }

        try {
            DB::table('room_blocks')->insert([
                'property_id' => $otherProperty->id,
                'room_id' => $roomId,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'reason' => 'Maintenance',
                'created_at' => now(),
            ]);
            $this->fail('Database must reject cross-property room blocks.');
        } catch (QueryException) {
            $this->assertDatabaseCount('room_blocks', 0);
        }
    }

    public function test_status_block_and_cancellation_history_cannot_be_updated_or_deleted(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();
        $operations = app(RoomOperations::class);
        $statusEventId = (int) DB::table('room_status_events')->where('room_id', $roomId)->value('id');
        $blockId = $operations->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-10'),
            'Maintenance',
            $admin,
        );
        $activeBlockId = $operations->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-11'),
            CarbonImmutable::parse('2026-11-11'),
            'Independent active block',
            $admin,
        );
        $operations->cancelBlock($property->id, $roomId, $blockId, 'Completed', $admin);
        $cancellationId = (int) DB::table('room_block_cancellations')->where('room_block_id', $blockId)->value('id');

        foreach ([
            ['room_status_events', $statusEventId, ['status' => 'clean'], 1],
            ['room_blocks', $activeBlockId, ['reason' => 'Tampered'], 2],
            ['room_block_cancellations', $cancellationId, ['reason' => 'Tampered'], 1],
        ] as [$table, $id, $changes, $count]) {
            try {
                DB::table($table)->where('id', $id)->update($changes);
                $this->fail("{$table} must reject updates.");
            } catch (QueryException) {
                $this->assertDatabaseCount($table, $count);
            }

            try {
                DB::table($table)->where('id', $id)->delete();
                $this->fail("{$table} must reject deletes.");
            } catch (QueryException) {
                $this->assertDatabaseCount($table, $count);
            }
        }

        $this->assertFalse($operations->hasActiveBlockOnDate($property->id, $roomId, CarbonImmutable::parse('2026-11-10')));
        $this->assertDatabaseHas('room_status_events', ['id' => $statusEventId, 'status' => 'dirty']);
        $this->assertDatabaseHas('room_blocks', ['id' => $blockId, 'reason' => 'Maintenance']);
        $this->assertDatabaseHas('room_block_cancellations', ['id' => $cancellationId, 'reason' => 'Completed']);
    }

    public function test_database_restricts_deleting_room_with_status_history(): void
    {

        [$property, $roomId] = $this->room();

        try {
            DB::table('rooms')->where('id', $roomId)->delete();
            $this->fail('Database must retain rooms referenced by operational history.');
        } catch (QueryException) {
            $this->assertDatabaseHas('rooms', ['id' => $roomId, 'property_id' => $property->id]);
            $this->assertDatabaseCount('room_status_events', 1);
        }
    }

    /** @return array{Property, int} */
    private function room(): array
    {
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '101',
            'is_active' => true,
        ]);

        DB::table('room_status_events')->insert([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => null,
            'status' => 'dirty',
            'actor_id' => null,
            'created_at' => now(),
        ]);

        return [$property, $roomId];
    }

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin;
    }
}
