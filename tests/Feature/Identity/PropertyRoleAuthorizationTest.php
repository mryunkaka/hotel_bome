<?php

namespace Tests\Feature\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeping_membership_does_not_grant_engineering_asset_or_work_order_routes(): void
    {
        [$user, $property] = $this->housekeepingAssignment();

        $this->actingAs($user)->get(route('properties.operations.assets.index', $property))->assertNotFound();
        $this->actingAs($user)->post(route('properties.operations.assets.store', $property), [
            'code' => 'DENY', 'name' => 'Denied asset', 'category' => 'Test', 'status' => 'operational',
        ])->assertNotFound();
        $this->actingAs($user)->get(route('properties.operations.work-orders.index', $property))->assertNotFound();
        $this->actingAs($user)->post(route('properties.operations.work-orders.store', $property), ['title' => 'Denied order'])
            ->assertNotFound();
        $this->assertSame(0, DB::table('engineering_assets')->count());
        $this->assertSame(0, DB::table('work_orders')->count());
    }

    public function test_housekeeping_member_sees_only_assigned_property_board_without_admin_controls(): void
    {
        [$user, $property, $roomId] = $this->housekeepingAssignment();

        $response = $this->actingAs($user)->get(route('properties.housekeeping.board', $property));

        $response->assertOk()
            ->assertSee('Housekeeping board')
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee('101')
            ->assertDontSee('Add room')
            ->assertDontSee('Add room block')
            ->assertDontSee('Test maintenance reason')
            ->assertSee(route('properties.housekeeping.status', [$property, $roomId]));

        $this->get(route('properties.rooms.index', $property))->assertNotFound();
        $this->get(route('properties.rooms.history', $property))->assertNotFound();
        $this->post(route('properties.room-blocks.store', $property), [
            'room_id' => $roomId,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Unauthorized',
        ])->assertNotFound();
        $this->post(route('properties.rooms.store', $property), [
            'room_type_id' => DB::table('room_types')->where('property_id', $property->id)->value('id'),
            'code' => '102',
        ])->assertNotFound();
    }

    public function test_housekeeping_member_can_change_status_only_in_assigned_property(): void
    {
        [$user, $property, $roomId] = $this->housekeepingAssignment();
        $otherProperty = Property::factory()->create();

        $this->actingAs($user)
            ->get(route('properties.housekeeping.board', $otherProperty))
            ->assertNotFound();

        $this->patch(route('properties.housekeeping.status', [$property, $roomId]), [
            'status' => 'clean',
            'reason' => 'Room cleaned',
        ])->assertRedirect(route('properties.housekeeping.board', $property));

        $this->assertDatabaseHas('room_status_events', [
            'property_id' => $property->id,
            'room_id' => $roomId,
            'status' => 'clean',
            'actor_id' => $user->id,
        ]);

        $this->patch(route('properties.housekeeping.status', [$otherProperty, $roomId]), [
            'status' => 'dirty',
        ])->assertNotFound();

        $foreignRoomId = $this->createRoom($otherProperty);
        $this->patch(route('properties.housekeeping.status', [$property, $foreignRoomId]), [
            'status' => 'clean',
        ])->assertNotFound();
    }

    public function test_housekeeping_cannot_update_a_room_from_another_property(): void
    {
        [$user, $propertyA, $roomA] = $this->housekeepingAssignment();
        $propertyB = Property::factory()->create();
        $roomB = $this->createRoom($propertyB, '202');

        $this->actingAs($user)
            ->patch(route('properties.housekeeping.status', [$propertyA, $roomB]), [
                'status' => 'clean',
                'reason' => 'Cross-property attempt',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('room_status_events', [
            'property_id' => $propertyA->id,
            'room_id' => $roomA,
            'status' => 'dirty',
        ]);
        $this->assertDatabaseHas('room_status_events', [
            'property_id' => $propertyB->id,
            'room_id' => $roomB,
            'status' => 'dirty',
        ]);
        $this->assertDatabaseCount('room_status_events', 2);
    }

    public function test_revoked_housekeeping_membership_blocks_the_next_board_and_status_request(): void
    {
        [$user, $property, $roomId] = $this->housekeepingAssignment();

        $this->actingAs($user)
            ->get(route('properties.housekeeping.board', $property))
            ->assertOk();

        DB::table('property_user')
            ->where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->delete();

        $this->get(route('properties.housekeeping.board', $property))->assertNotFound();
        $this->patch(route('properties.housekeeping.status', [$property, $roomId]), [
            'status' => 'clean',
            'reason' => 'Should not write after access revoke',
        ])->assertNotFound();

        $this->assertDatabaseCount('room_status_events', 1);
    }

    public function test_pending_and_inactive_housekeeping_users_cannot_read_or_write_property(): void
    {
        foreach (['pending', 'inactive'] as $status) {
            [$user, $property, $roomId] = $this->housekeepingAssignment();
            DB::table('users')->where('id', $user->id)->update(['status' => $status]);

            $this->actingAs($user->fresh())
                ->get(route('properties.housekeeping.board', $property))
                ->assertRedirect(route('login'));

            $this->actingAs($user->fresh())
                ->patch(route('properties.housekeeping.status', [$property, $roomId]), [
                    'status' => 'clean',
                    'reason' => 'Inactive account attempt',
                ])
                ->assertRedirect(route('login'));
        }

        $this->assertDatabaseCount('room_status_events', 2);
    }

    public function test_inactive_property_blocks_housekeeping_board_and_status_write(): void
    {
        [$user, $property, $roomId] = $this->housekeepingAssignment();
        DB::table('properties')->where('id', $property->id)->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('properties.housekeeping.board', $property))
            ->assertNotFound();

        $this->patch(route('properties.housekeeping.status', [$property, $roomId]), [
            'status' => 'clean',
            'reason' => 'Attempt against inactive property',
        ])->assertNotFound();

        $this->assertDatabaseCount('room_status_events', 1);
    }

    public function test_housekeeping_member_cannot_skip_status_transition(): void
    {
        [$user, $property, $roomId] = $this->housekeepingAssignment();

        $this->actingAs($user)
            ->from(route('properties.housekeeping.board', $property))
            ->patch(route('properties.housekeeping.status', [$property, $roomId]), ['status' => 'inspected'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('room_status_events', 1);
    }

    public function test_membership_alone_does_not_grant_housekeeping_or_master_routes(): void
    {
        [$user, $property, $roomId] = $this->housekeepingAssignment('front_desk');

        $this->actingAs($user)
            ->get(route('properties.housekeeping.board', $property))
            ->assertNotFound();

        $this->get(route('properties.rooms.index', $property))->assertNotFound();
        $this->get(route('properties.rooms.history', $property))->assertNotFound();
        $this->post(route('properties.room-blocks.store', $property), [
            'room_id' => $roomId,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Unauthorized',
        ])->assertNotFound();
        $this->get(route('properties.index'))->assertNotFound();
        $this->get(route('my.properties.index'))->assertOk()->assertDontSee($property->name);
    }

    public function test_my_properties_lists_only_active_housekeeping_assignments(): void
    {
        [$user, $assignedProperty] = $this->housekeepingAssignment();
        $otherProperty = Property::factory()->create(['name' => 'Not assigned']);
        $inactiveProperty = Property::factory()->create(['name' => 'Inactive assignment', 'is_active' => false]);
        DB::table('property_user')->insert([
            [
                'user_id' => $user->id,
                'property_id' => $otherProperty->id,
                'role' => 'front_desk',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'property_id' => $inactiveProperty->id,
                'role' => 'housekeeping',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->get(route('my.properties.index'))
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee($assignedProperty->name)
            ->assertDontSee($otherProperty->name)
            ->assertDontSee($inactiveProperty->name);
        $this->get(route('properties.housekeeping.board', $inactiveProperty))->assertNotFound();
    }

    private function createRoom(Property $property, string $code = '101'): int
    {
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
            'actor_id' => null,
            'created_at' => now(),
        ]);

        return $roomId;
    }

    /** @return array{User, Property, int} */
    private function housekeepingAssignment(string $role = 'housekeeping'): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();
        DB::table('property_user')->insert([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        DB::table('room_blocks')->insert([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Test maintenance reason',
            'created_by' => User::factory()->create()->id,
            'created_at' => now(),
        ]);

        return [$user, $property, $roomId];
    }
}
