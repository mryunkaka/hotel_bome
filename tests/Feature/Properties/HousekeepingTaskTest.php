<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use App\Services\Properties\HousekeepingTasks;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class HousekeepingTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeping_task_schema_scopes_room_to_property_and_keeps_history_immutable(): void
    {
        [$property, $roomId] = $this->room();
        $user = User::factory()->create(['status' => 'active']);
        $taskId = DB::table('housekeeping_tasks')->insertGetId([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'status' => 'open',
            'instruction' => 'Clean room',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('housekeeping_task_events')->insert([
            'property_id' => $property->id,
            'task_id' => $taskId,
            'event_type' => 'created',
            'from_status' => null,
            'to_status' => 'open',
            'actor_id' => $user->id,
            'assigned_to_before' => null,
            'assigned_to_after' => null,
            'created_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('housekeeping_task_events')->where('task_id', $taskId)->delete();
    }

    public function test_housekeeping_task_event_update_is_rejected(): void
    {
        [$property, $roomId] = $this->room();
        $user = User::factory()->create(['status' => 'active']);
        $taskId = DB::table('housekeeping_tasks')->insertGetId([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'created_by' => $user->id,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('housekeeping_task_events')->insert([
            'property_id' => $property->id,
            'task_id' => $taskId,
            'event_type' => 'created',
            'to_status' => 'open',
            'actor_id' => $user->id,
            'created_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('housekeeping_task_events')->where('task_id', $taskId)->update(['reason' => 'tampered']);
    }

    public function test_housekeeping_task_rejects_room_from_another_property(): void
    {
        [$propertyA] = $this->room();
        [, $roomB] = $this->room();
        $user = User::factory()->create(['status' => 'active']);

        $this->expectException(QueryException::class);
        DB::table('housekeeping_tasks')->insert([
            'property_id' => $propertyA->id,
            'room_id' => $roomB,
            'status' => 'open',
            'instruction' => 'Clean room',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_task_lifecycle_records_immutable_events_without_changing_room_status(): void
    {
        [$property, $roomId] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        $tasks = app(HousekeepingTasks::class);

        $roomStatusEventsBefore = DB::table('room_status_events')->where('room_id', $roomId)->count();
        $taskId = $tasks->create($property->id, $roomId, null, 'Change linen', $actor);
        $tasks->transition($property->id, $taskId, 'in_progress', null, $actor);
        $tasks->transition($property->id, $taskId, 'completed', null, $actor);

        $this->assertSame('completed', DB::table('housekeeping_tasks')->where('id', $taskId)->value('status'));
        $this->assertSame(3, DB::table('housekeeping_task_events')->where('task_id', $taskId)->count());
        $this->assertSame($roomStatusEventsBefore, DB::table('room_status_events')->where('room_id', $roomId)->count());
    }

    public function test_task_cancellation_requires_reason_and_terminal_state_cannot_change(): void
    {
        [$property, $roomId] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        $tasks = app(HousekeepingTasks::class);
        $taskId = $tasks->create($property->id, $roomId, null, null, $actor);

        try {
            $tasks->transition($property->id, $taskId, 'cancelled', null, $actor);
            $this->fail('Cancellation without reason must fail.');
        } catch (ValidationException) {
            $this->assertSame('open', DB::table('housekeeping_tasks')->where('id', $taskId)->value('status'));
        }

        $tasks->transition($property->id, $taskId, 'cancelled', 'Duplicate request', $actor);
        try {
            $tasks->transition($property->id, $taskId, 'in_progress', null, $actor);
            $this->fail('Cancelled task must be terminal.');
        } catch (ValidationException) {
            $this->assertSame('cancelled', DB::table('housekeeping_tasks')->where('id', $taskId)->value('status'));
        }
    }

    public function test_task_rejects_ineligible_assignee_and_cross_property_task(): void
    {
        [$property, $roomId] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $tasks = app(HousekeepingTasks::class);

        try {
            $tasks->create($property->id, $roomId, $other->id, null, $actor);
            $this->fail('User without housekeeping membership must be rejected.');
        } catch (NotFoundHttpException) {
            $this->assertSame(0, DB::table('housekeeping_tasks')->count());
        }

        $taskId = $tasks->create($property->id, $roomId, null, null, $actor);
        try {
            $tasks->task($property->id + 1, $taskId);
            $this->fail('Cross-property task read must be rejected.');
        } catch (NotFoundHttpException) {
            $this->assertSame(1, DB::table('housekeeping_tasks')->count());
        }
    }

    public function test_housekeeping_status_filter_rejects_unknown_values(): void
    {
        [$property] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'user_id' => $actor->id, 'property_id' => $property->id, 'role' => 'housekeeping',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($actor)
            ->get(route('properties.housekeeping.tasks.index', [$property, 'status' => 'archived']))
            ->assertSessionHasErrors('status');
    }

    public function test_housekeeping_all_status_filter_accepts_empty_value(): void
    {
        [$property, $roomId] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'user_id' => $actor->id, 'property_id' => $property->id, 'role' => 'housekeeping',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(HousekeepingTasks::class)->create($property->id, $roomId, null, 'Keep visible', $actor);

        $this->actingAs($actor)
            ->get(route('properties.housekeeping.tasks.index', [$property, 'status' => '']))
            ->assertOk()
            ->assertSee('Keep visible');
    }

    public function test_housekeeping_member_can_create_and_complete_task_through_property_scoped_routes(): void
    {
        [$property, $roomId] = $this->room();
        $actor = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'user_id' => $actor->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor)
            ->get(route('properties.housekeeping.tasks.index', $property))
            ->assertOk()
            ->assertSee('Housekeeping tasks');

        $this->actingAs($actor)->post(route('properties.housekeeping.tasks.store', $property), [
            'room_id' => $roomId,
            'instruction' => '<script>alert(1)</script>',
        ])->assertRedirect();

        $taskId = DB::table('housekeeping_tasks')->value('id');
        $this->actingAs($actor)->patch(route('properties.housekeeping.tasks.transition', [$property, $taskId, 'start']))->assertRedirect();
        $this->actingAs($actor)->patch(route('properties.housekeeping.tasks.transition', [$property, $taskId, 'complete']))->assertRedirect();
        $this->actingAs($actor)->get(route('properties.housekeeping.tasks.index', $property))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('alert(1)')
            ->assertSee($actor->name)
            ->assertSee('status_changed');
    }

    public function test_other_property_and_non_housekeeping_user_cannot_read_or_create_tasks(): void
    {
        [$property, $roomId] = $this->room();
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($other)
            ->get(route('properties.housekeeping.tasks.index', $property))
            ->assertNotFound();
        $this->actingAs($other)
            ->post(route('properties.housekeeping.tasks.store', $property), ['room_id' => $roomId])
            ->assertNotFound();
        $this->assertSame(0, DB::table('housekeeping_tasks')->count());
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
