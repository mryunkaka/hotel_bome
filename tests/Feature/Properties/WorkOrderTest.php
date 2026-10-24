<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use App\Services\Properties\WorkOrders;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_lifecycle_records_history_and_does_not_change_room_status(): void
    {
        [$property, $roomId] = $this->room();
        $actor = $this->operationsUser($property);
        $service = app(WorkOrders::class);
        $statusCount = DB::table('room_status_events')->where('room_id', $roomId)->count();
        $id = $service->create($property->id, $roomId, null, 'Repair sink', null, $actor);
        $service->transition($property->id, $id, 'in_progress', null, $actor);
        $service->transition($property->id, $id, 'resolved', null, $actor);

        $this->assertSame('resolved', DB::table('work_orders')->where('id', $id)->value('status'));
        $this->assertSame(3, DB::table('work_order_events')->where('work_order_id', $id)->count());
        $this->assertSame($statusCount, DB::table('room_status_events')->where('room_id', $roomId)->count());
    }

    public function test_cancellation_requires_reason_and_terminal_work_order_cannot_transition(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);
        $service = app(WorkOrders::class);
        $id = $service->create($property->id, null, null, 'Repair lift', null, $actor);

        try {
            $service->transition($property->id, $id, 'cancelled', null, $actor);
            $this->fail('Cancellation requires reason.');
        } catch (ValidationException) {
            $this->assertSame('open', DB::table('work_orders')->where('id', $id)->value('status'));
        }

        $service->transition($property->id, $id, 'cancelled', 'Duplicate report', $actor);
        $this->expectException(ValidationException::class);
        $service->transition($property->id, $id, 'in_progress', null, $actor);
    }

    public function test_cross_property_room_and_nonmember_assignee_are_rejected(): void
    {
        [$propertyA] = $this->room();
        [, $roomB] = $this->room();
        $actor = $this->operationsUser($propertyA);
        $other = User::factory()->create(['status' => 'active']);
        $service = app(WorkOrders::class);

        try {
            $service->create($propertyA->id, $roomB, null, 'Repair sink', null, $actor);
            $this->fail('Cross-property room must fail.');
        } catch (NotFoundHttpException) {
            $this->assertSame(0, DB::table('work_orders')->count());
        }

        $this->expectException(NotFoundHttpException::class);
        $service->create($propertyA->id, null, $other->id, 'Repair lift', null, $actor);
    }

    public function test_operations_member_cannot_read_or_create_work_orders_for_another_property(): void
    {
        [$propertyA] = $this->room();
        [$propertyB] = $this->room();
        $actor = $this->operationsUser($propertyA);

        $this->actingAs($actor)->get(route('properties.operations.work-orders.index', $propertyB))->assertNotFound();
        $this->actingAs($actor)->post(route('properties.operations.work-orders.store', $propertyB), ['title' => 'Cross-property repair'])
            ->assertNotFound();
        $this->assertSame(0, DB::table('work_orders')->count());
    }

    public function test_work_order_status_filter_rejects_unknown_values(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);

        $this->actingAs($actor)
            ->get(route('properties.operations.work-orders.index', [$property, 'status' => 'archived']))
            ->assertSessionHasErrors('status');
    }

    public function test_work_order_all_status_filter_accepts_empty_value(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);
        app(WorkOrders::class)->create($property->id, null, null, 'Keep visible', null, $actor);

        $this->actingAs($actor)
            ->get(route('properties.operations.work-orders.index', [$property, 'status' => '']))
            ->assertOk()
            ->assertSee('Keep visible');
    }

    public function test_assignment_and_transition_reject_work_order_id_from_another_property(): void
    {
        [$propertyA] = $this->room();
        [$propertyB] = $this->room();
        $actorA = $this->operationsUser($propertyA);
        $actorB = $this->operationsUser($propertyB);
        $workOrderId = app(WorkOrders::class)->create($propertyB->id, null, null, 'Repair lift', null, $actorB);

        $this->actingAs($actorA)->patch(route('properties.operations.work-orders.assign', [$propertyA, $workOrderId]), ['assigned_to' => null])
            ->assertNotFound();
        $this->actingAs($actorA)->patch(route('properties.operations.work-orders.transition', [$propertyA, $workOrderId, 'start']))
            ->assertNotFound();
        $this->assertDatabaseHas('work_orders', ['id' => $workOrderId, 'property_id' => $propertyB->id, 'status' => 'open']);
        $this->assertSame(1, DB::table('work_order_events')->where('work_order_id', $workOrderId)->count());
    }

    public function test_history_rejects_update_and_delete(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);
        $id = app(WorkOrders::class)->create($property->id, null, null, 'Repair lift', null, $actor);

        try {
            DB::table('work_order_events')->where('work_order_id', $id)->update(['reason' => 'tampered']);
            $this->fail('Event update must fail.');
        } catch (QueryException) {
            $this->assertSame(1, DB::table('work_order_events')->where('work_order_id', $id)->count());
        }

        $this->expectException(QueryException::class);
        DB::table('work_order_events')->where('work_order_id', $id)->delete();
    }

    public function test_inactive_operations_user_cannot_read_or_create_work_orders(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);
        DB::table('users')->where('id', $actor->id)->update(['status' => 'inactive']);

        $this->actingAs($actor->fresh())->get(route('properties.operations.work-orders.index', $property))->assertRedirect(route('login'));
        $this->actingAs($actor->fresh())->post(route('properties.operations.work-orders.store', $property), ['title' => 'Denied'])
            ->assertRedirect(route('login'));
        $this->assertSame(0, DB::table('work_orders')->count());
    }

    public function test_inactive_property_denies_work_order_read_and_create(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);
        DB::table('properties')->where('id', $property->id)->update(['is_active' => false]);

        $this->actingAs($actor)->get(route('properties.operations.work-orders.index', $property))->assertNotFound();
        $this->actingAs($actor)->post(route('properties.operations.work-orders.store', $property), ['title' => 'Denied'])
            ->assertNotFound();
        $this->assertSame(0, DB::table('work_orders')->count());
    }

    public function test_operations_membership_revocation_blocks_work_order_read_and_create(): void
    {
        [$property] = $this->room();
        $actor = $this->operationsUser($property);
        $orderId = app(WorkOrders::class)->create($property->id, null, null, 'Existing repair', null, $actor);
        DB::table('property_user')->where('user_id', $actor->id)->where('property_id', $property->id)->delete();

        $this->actingAs($actor)->get(route('properties.operations.work-orders.index', $property))->assertNotFound();
        $this->actingAs($actor)->post(route('properties.operations.work-orders.store', $property), ['title' => 'Unauthorized repair'])
            ->assertNotFound();
        $this->assertDatabaseHas('work_orders', ['id' => $orderId, 'status' => 'open']);
        $this->assertSame(1, DB::table('work_order_events')->where('work_order_id', $orderId)->count());
    }

    public function test_operations_selector_lists_only_active_assigned_properties(): void
    {
        $propertyA = Property::factory()->create(['name' => 'Assigned A']);
        $propertyB = Property::factory()->create(['name' => 'Unassigned B']);
        $propertyInactive = Property::factory()->create(['name' => 'Inactive C', 'is_active' => false]);
        $actor = $this->operationsUser($propertyA);

        $this->actingAs($actor)->get(route('my.operations.properties'))
            ->assertOk()->assertSee('Assigned A')->assertDontSee('Unassigned B')->assertDontSee('Inactive C');

        $nonOperationsUser = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'user_id' => $nonOperationsUser->id, 'property_id' => $propertyB->id, 'role' => 'housekeeping',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($nonOperationsUser)->get(route('my.operations.properties'))
            ->assertOk()->assertDontSee('Assigned A')->assertDontSee('Unassigned B')->assertDontSee('Inactive C');
    }

    public function test_platform_admin_operations_selector_lists_only_active_properties(): void
    {
        Property::factory()->create(['name' => 'Active A']);
        Property::factory()->create(['name' => 'Inactive B', 'is_active' => false]);
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);

        $this->actingAs($admin)->get(route('my.operations.properties'))
            ->assertOk()->assertSee('Active A')->assertDontSee('Inactive B');
    }

    public function test_operations_member_can_view_work_order_detail_and_history_only_in_its_property(): void
    {
        [$propertyA] = $this->room();
        [$propertyB] = $this->room();
        $actorA = $this->operationsUser($propertyA);
        $actorB = $this->operationsUser($propertyB);
        $id = app(WorkOrders::class)->create($propertyA->id, null, null, 'Inspect boiler', 'Pressure drop', $actorA);
        app(WorkOrders::class)->transition($propertyA->id, $id, 'in_progress', null, $actorA);

        $this->actingAs($actorA)->get(route('properties.operations.work-orders.index', $propertyA))
            ->assertOk()->assertSee(route('properties.operations.work-orders.show', [$propertyA, $id]), false);
        $this->actingAs($actorA)->get(route('properties.operations.work-orders.show', [$propertyA, $id]))
            ->assertOk()->assertSee('Inspect boiler')->assertSee('Pressure drop')->assertSee($actorA->name)->assertSee('In progress');
        $this->actingAs($actorB)->get(route('properties.operations.work-orders.show', [$propertyB, $id]))
            ->assertNotFound();
    }

    public function test_operations_member_can_create_view_and_transition_work_order(): void
    {
        [$property] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'user_id' => $actor->id,
            'property_id' => $property->id,
            'role' => 'operations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor)->get(route('properties.operations.work-orders.index', $property))->assertOk();
        $this->actingAs($actor)->post(route('properties.operations.work-orders.store', $property), [
            'title' => 'Fix <script>alert(1)</script>',
            'description' => 'Leaking faucet',
        ])->assertRedirect();
        $id = DB::table('work_orders')->value('id');
        $this->actingAs($actor)->patch(route('properties.operations.work-orders.transition', [$property, $id, 'start']))->assertRedirect();
        $this->actingAs($actor)->patch(route('properties.operations.work-orders.transition', [$property, $id, 'resolve']))->assertRedirect();
        $this->actingAs($actor)->get(route('properties.operations.work-orders.index', $property))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('alert(1)');
    }

    private function operationsUser(Property $property): User
    {
        $user = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'role' => 'operations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function room(): array
    {
        $property = Property::factory()->create();
        $typeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD-'.$property->id,
            'is_active' => true,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $typeId,
            'code' => '101',
            'is_active' => true,
        ]);

        return [$property, $roomId];
    }
}
