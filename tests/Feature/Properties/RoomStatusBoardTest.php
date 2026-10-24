<?php

namespace Tests\Feature\Properties;

use App\Enums\HousekeepingStatus;
use App\Models\Property;
use App\Models\User;
use App\Services\Properties\RoomOperations;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomStatusBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_change_status_from_board_and_see_current_status(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();

        $this->actingAs($admin)
            ->patch(route('properties.rooms.status.update', [$property, $roomId]), [
                'status' => 'clean',
                'reason' => 'Housekeeping complete',
            ])
            ->assertRedirect(route('properties.rooms.index', $property));

        $this->assertDatabaseHas('room_status_events', [
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => 'dirty',
            'status' => 'clean',
            'actor_id' => $admin->id,
            'reason' => 'Housekeeping complete',
        ]);
        $this->actingAs($admin)->get(route('properties.rooms.index', [$property, 'housekeeping_status' => 'clean']))
            ->assertOk()
            ->assertSee('<td class="px-4 py-3">Clean</td>', false);
    }

    public function test_board_rejects_invalid_transition_and_scopes_room_and_block_ids(): void
    {
        $admin = $this->platformAdmin();
        [$propertyA, $roomId] = $this->room();
        $propertyB = Property::factory()->create();

        $this->actingAs($admin)
            ->from(route('properties.rooms.index', $propertyA))
            ->patch(route('properties.rooms.status.update', [$propertyA, $roomId]), ['status' => 'inspected'])
            ->assertSessionHasErrors('status');
        $this->assertDatabaseCount('room_status_events', 1);

        $blockId = app(RoomOperations::class)->createBlock(
            $propertyA->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-10'),
            'Maintenance',
            $admin,
        );
        $this->actingAs($admin)
            ->patch(route('properties.room-blocks.cancel', [$propertyB, $roomId, $blockId]), ['reason' => 'Wrong property'])
            ->assertNotFound();
        $this->assertDatabaseCount('room_block_cancellations', 0);
    }

    public function test_platform_admin_can_create_cancel_and_filter_blocks(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();

        $this->actingAs($admin)
            ->post(route('properties.room-blocks.store', $property), [
                'room_id' => $roomId,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-12',
                'reason' => 'Maintenance',
            ])
            ->assertRedirect(route('properties.rooms.index', $property));
        $blockId = (int) DB::table('room_blocks')->value('id');
        $this->assertDatabaseHas('room_blocks', [
            'id' => $blockId,
            'property_id' => $property->id,
            'room_id' => $roomId,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
        ]);
        $this->actingAs($admin)->get(route('properties.rooms.index', $property))
            ->assertOk()
            ->assertSee('Maintenance')
            ->assertSee('2026-11-10');

        $this->actingAs($admin)
            ->patch(route('properties.room-blocks.cancel', [$property, $roomId, $blockId]), ['reason' => 'Repair complete'])
            ->assertRedirect(route('properties.rooms.index', $property));
        $this->assertDatabaseCount('room_blocks', 1);
        $this->assertDatabaseHas('room_block_cancellations', [
            'room_block_id' => $blockId,
            'actor_id' => $admin->id,
            'reason' => 'Repair complete',
        ]);
        $this->assertFalse(app(RoomOperations::class)->hasActiveBlockOnDate(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-11'),
        ));
    }

    public function test_board_filters_by_code_status_and_active_state_and_escapes_block_reason(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room('101');
        [, $otherRoomId] = $this->room('102', $property);
        DB::table('rooms')->where('id', $otherRoomId)->update(['is_active' => false]);
        app(RoomOperations::class)->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-10'),
            '<script>alert(1)</script>',
            $admin,
        );

        $this->actingAs($admin)->get(route('properties.rooms.index', [
            $property,
            'q' => '101',
            'housekeeping_status' => 'dirty',
            'active' => 'active',
        ]))
            ->assertOk()
            ->assertSee('<td class="px-4 py-3 font-mono">101</td>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('102');

        $this->actingAs($admin)->get(route('properties.rooms.index', [$property, 'active' => 'inactive']))
            ->assertOk()
            ->assertSee('<td class="px-4 py-3 font-mono">102</td>', false)
            ->assertDontSee('<td class="px-4 py-3 font-mono">101</td>', false);
    }

    public function test_room_history_shows_status_and_block_events_with_property_scope(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room('101');
        [$otherProperty, $otherRoomId] = $this->room('OTHER');
        $operations = app(RoomOperations::class);
        $operations->changeHousekeepingStatus($property->id, $roomId, HousekeepingStatus::Clean, $admin, 'Cleaning done');
        $blockId = $operations->createBlock(
            $property->id,
            $roomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-12'),
            'Local <script>alert(2)</script>',
            $admin,
        );
        $operations->cancelBlock($property->id, $roomId, $blockId, 'Repair complete', $admin);
        $otherBlockId = $operations->createBlock(
            $otherProperty->id,
            $otherRoomId,
            CarbonImmutable::parse('2026-11-10'),
            CarbonImmutable::parse('2026-11-12'),
            'Other property secret marker',
            $admin,
        );
        $operations->cancelBlock($otherProperty->id, $otherRoomId, $otherBlockId, 'Other property cancel marker', $admin);

        $this->actingAs($admin)
            ->get(route('properties.rooms.history', $property))
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee('Housekeeping status changed')
            ->assertSee('Room block added')
            ->assertSee('Room block cancelled')
            ->assertSee('Cleaning done')
            ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertSee('Repair complete')
            ->assertDontSee('Other property secret marker')
            ->assertDontSee('Other property cancel marker')
            ->assertDontSee('OTHER');
    }

    public function test_staff_cannot_view_room_history(): void
    {
        $staff = User::factory()->create();
        [$property] = $this->room();

        $this->actingAs($staff)->get(route('properties.rooms.history', $property))->assertNotFound();
    }

    public function test_staff_and_cross_property_room_block_creation_are_denied(): void
    {
        $staff = User::factory()->create();
        [$propertyA] = $this->room();
        [$propertyB, $roomBId] = $this->room();

        $this->actingAs($staff)
            ->post(route('properties.room-blocks.store', $propertyA), [
                'room_id' => $roomBId,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'reason' => 'Maintenance',
            ])
            ->assertNotFound();
        $this->actingAs($this->platformAdmin())
            ->from(route('properties.rooms.index', $propertyA))
            ->post(route('properties.room-blocks.store', $propertyA), [
                'room_id' => $roomBId,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'reason' => 'Maintenance',
            ])
            ->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('room_blocks', 0);
    }

    public function test_invalid_block_dates_and_reason_are_rejected_on_form(): void
    {
        $admin = $this->platformAdmin();
        [$property, $roomId] = $this->room();

        $this->actingAs($admin)
            ->from(route('properties.rooms.index', $property))
            ->post(route('properties.room-blocks.store', $property), [
                'room_id' => $roomId,
                'start_date' => '2026-11-12',
                'end_date' => '2026-11-11',
                'reason' => ' ',
            ])
            ->assertSessionHasErrors(['end_date', 'reason']);
        $this->assertDatabaseCount('room_blocks', 0);

        $this->actingAs($admin)
            ->post(route('properties.room-blocks.store', $property), [
                'room_id' => $roomId,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'reason' => '   ',
            ])
            ->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('room_blocks', 0);
    }

    /** @return array{Property, int} */
    private function room(string $code = '101', ?Property $property = null): array
    {
        $property ??= Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD-'.$code,
            'is_active' => true,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => $code,
            'is_active' => true,
        ]);
        DB::table('room_status_events')->insert([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => null,
            'status' => 'dirty',
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
