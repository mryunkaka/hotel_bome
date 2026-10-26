<?php

namespace Tests\Feature\Properties;

use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuestProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_guests(): void
    {
        $property = Property::factory()->create();

        $this->get("/properties/{$property->id}/guests")->assertRedirect(route('login'));
        $this->post("/properties/{$property->id}/guests")->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_guests(): void
    {
        $staff = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($staff)->get("/properties/{$property->id}/guests")->assertNotFound();
        $this->actingAs($staff)->get("/properties/{$property->id}/guests/create")->assertNotFound();
        $this->actingAs($staff)->post("/properties/{$property->id}/guests", [
            'display_name' => 'Test Guest',
        ])->assertNotFound();
    }

    public function test_receptionist_with_property_membership_can_list_guests(): void
    {
        $receptionist = User::factory()->create();
        $property = Property::factory()->create();
        DB::table('property_user')->insert([
            'user_id' => $receptionist->id,
            'property_id' => $property->id,
            'role' => 'receptionist',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($receptionist)
            ->get("/properties/{$property->id}/guests")
            ->assertOk();
    }

    public function test_receptionist_cannot_review_guest_activity_or_reports(): void
    {
        $receptionist = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($receptionist, $property, 'receptionist');

        $this->actingAs($receptionist)->get("/properties/{$property->id}/guest-activity")->assertNotFound();
        $this->actingAs($receptionist)->get("/properties/{$property->id}/guest-report")->assertNotFound();
    }

    public function test_receptionist_cannot_validate_guest_records(): void
    {
        $receptionist = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($receptionist, $property, 'receptionist');
        $guest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Unvalidated Guest', 'is_active' => true]);

        $this->actingAs($receptionist)
            ->patch("/properties/{$property->id}/guests/{$guest->id}/validate", ['reason' => 'Not authorized'])
            ->assertNotFound();

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_validated' => false]);
        $this->assertDatabaseMissing('guest_events', ['guest_id' => $guest->id, 'event_type' => 'validated']);
    }

    public function test_other_property_roles_cannot_access_guest_profiles(): void
    {
        $staff = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($staff, $property, 'front_desk');

        $this->actingAs($staff)->get("/properties/{$property->id}/guests")->assertNotFound();
        $this->actingAs($staff)->post("/properties/{$property->id}/guests", [
            'display_name' => 'Denied Guest',
        ])->assertNotFound();
    }

    public function test_non_guest_property_roles_cannot_read_or_create_guest_records(): void
    {
        $property = Property::factory()->create();

        foreach (['hotel_admin', 'front_desk', 'finance', 'housekeeping', 'operations'] as $role) {
            $user = User::factory()->create();
            $this->addMembership($user, $property, $role);

            $this->actingAs($user)
                ->get("/properties/{$property->id}/guests")
                ->assertNotFound();
            $this->actingAs($user)
                ->post("/properties/{$property->id}/guests", ['display_name' => 'Denied '.$role])
                ->assertNotFound();
            $this->actingAs($user)
                ->get("/properties/{$property->id}/guest-activity")
                ->assertNotFound();
            $this->actingAs($user)
                ->get("/properties/{$property->id}/guest-report")
                ->assertNotFound();
            $this->actingAs($user)
                ->put("/properties/{$property->id}/guests/1", ['display_name' => 'Denied '.$role, 'reason' => 'Denied'])
                ->assertNotFound();
            $this->actingAs($user)
                ->patch("/properties/{$property->id}/guests/1/deactivate", ['reason' => 'Denied'])
                ->assertNotFound();
            $this->actingAs($user)
                ->patch("/properties/{$property->id}/guests/1/validate", ['reason' => 'Denied'])
                ->assertNotFound();
            $this->actingAs($user)
                ->patch("/properties/{$property->id}/guest-deactivation-requests/1/approve", ['reason' => 'Denied'])
                ->assertNotFound();
            $this->actingAs($user)
                ->patch("/properties/{$property->id}/guest-deactivation-requests/1/reject", ['reason' => 'Denied'])
                ->assertNotFound();
        }

        $this->assertDatabaseCount('guests', 0);
    }

    public function test_owner_can_open_guest_property_selector_and_employee_cannot(): void
    {
        $owner = User::factory()->create();
        $assigned = Property::factory()->create();
        $other = Property::factory()->create();
        $inactive = Property::factory()->create(['is_active' => false]);
        $this->addMembership($owner, $assigned, 'owner');

        $this->actingAs($owner)
            ->get('/guest-properties')
            ->assertOk()
            ->assertSee($assigned->name)
            ->assertSee($other->name)
            ->assertDontSee($inactive->name);

        $employee = User::factory()->create();
        $this->actingAs($employee)->get('/guest-properties')->assertNotFound();
    }

    public function test_owner_role_can_access_guest_records_across_properties(): void
    {
        $owner = User::factory()->create();
        $assigned = Property::factory()->create();
        $other = Property::factory()->create();
        $this->addMembership($owner, $assigned, 'owner');
        Guest::forceCreate(['property_id' => $other->id, 'display_name' => 'Cross Property Guest', 'is_active' => true]);

        $this->actingAs($owner)
            ->get("/properties/{$other->id}/guests")
            ->assertOk()
            ->assertSee('Cross Property Guest');
    }

    public function test_guest_creation_ignores_forged_property_and_lifecycle_fields(): void
    {
        $receptionist = User::factory()->create();
        $property = Property::factory()->create();
        $other = Property::factory()->create();
        $this->addMembership($receptionist, $property, 'receptionist');

        $this->actingAs($receptionist)
            ->post("/properties/{$property->id}/guests", [
                'display_name' => 'Synthetic Guest',
                'property_id' => $other->id,
                'is_active' => false,
                'is_validated' => true,
            ])
            ->assertRedirect();

        $guest = Guest::query()->where('display_name', 'Synthetic Guest')->firstOrFail();
        $this->assertSame(1, Guest::query()->where('display_name', 'Synthetic Guest')->count());
        $this->assertSame($property->id, $guest->property_id);
        $this->assertFalse(Guest::query()->where('display_name', 'Synthetic Guest')->where('property_id', $other->id)->exists());
        $this->assertTrue($guest->is_active);
        $this->assertFalse($guest->is_validated);
    }

    public function test_receptionist_update_requires_reason_and_audit_redacts_values(): void
    {
        $receptionist = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($receptionist, $property, 'receptionist');
        $guest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Before Name', 'notes' => 'Private detail', 'is_active' => true]);

        $this->actingAs($receptionist)->put("/properties/{$property->id}/guests/{$guest->id}", [
            'display_name' => 'After Name',
            'notes' => 'Changed private detail',
        ])->assertSessionHasErrors('reason');

        $this->actingAs($receptionist)->put("/properties/{$property->id}/guests/{$guest->id}", [
            'display_name' => 'After Name',
            'notes' => 'Changed private detail',
            'reason' => 'Corrected spelling',
        ])->assertRedirect();

        $event = DB::table('guest_events')->where('guest_id', $guest->id)->where('event_type', 'updated')->first();
        $this->assertSame('Corrected spelling', $event->reason);
        $this->assertSame(['display_name', 'notes'], json_decode($event->changes, true));
        $this->assertStringNotContainsString('After Name', $event->changes);
        $this->assertStringNotContainsString('Changed private detail', $event->changes);
    }

    public function test_receptionist_deactivation_waits_for_owner_approval(): void
    {
        $receptionist = User::factory()->create();
        $assigned = Property::factory()->create();
        $owner = User::factory()->create();
        $ownerProperty = Property::factory()->create();
        $this->addMembership($receptionist, $assigned, 'receptionist');
        $this->addMembership($owner, $ownerProperty, 'owner');
        $guest = Guest::forceCreate(['property_id' => $assigned->id, 'display_name' => 'Approval Guest', 'is_active' => true]);

        $this->actingAs($receptionist)
            ->patch("/properties/{$assigned->id}/guests/{$guest->id}/deactivate", ['reason' => 'Duplicate profile'])
            ->assertRedirect();

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_active' => true]);
        $request = DB::table('guest_deactivation_requests')->where('guest_id', $guest->id)->first();
        $this->assertNotNull($request);
        $this->assertSame('pending', $request->status);

        $this->actingAs($receptionist)
            ->patch("/properties/{$assigned->id}/guests/{$guest->id}/deactivate", ['reason' => 'Duplicate request'])
            ->assertSessionHasErrors('reason');
        $this->assertSame(1, DB::table('guest_deactivation_requests')->where('guest_id', $guest->id)->where('status', 'pending')->count());

        $this->assertDatabaseHas('guest_events', [
            'guest_id' => $guest->id,
            'event_type' => 'deactivation_requested',
            'reason' => 'Duplicate profile',
            'actor_id' => $receptionist->id,
        ]);

        $this->actingAs($owner)
            ->patch("/properties/{$assigned->id}/guest-deactivation-requests/{$request->id}/approve", ['reason' => 'Approved after review'])
            ->assertRedirect();

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_active' => false]);
        $this->assertDatabaseHas('guest_deactivation_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approver_id' => $owner->id,
            'decision_reason' => 'Approved after review',
        ]);
        $this->actingAs($owner)
            ->patch("/properties/{$assigned->id}/guest-deactivation-requests/{$request->id}/approve", ['reason' => 'Replay'])
            ->assertNotFound();
        $this->assertSame(1, DB::table('guest_events')->where('guest_id', $guest->id)->where('event_type', 'deactivation_approved')->count());

        $this->assertDatabaseHas('guest_events', [
            'guest_id' => $guest->id,
            'event_type' => 'deactivation_approved',
            'reason' => 'Approved after review',
            'actor_id' => $owner->id,
        ]);
    }

    public function test_supervisor_can_create_and_update_guest_with_reason(): void
    {
        $supervisor = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($supervisor, $property, 'supervisor');

        $this->actingAs($supervisor)
            ->post("/properties/{$property->id}/guests", ['display_name' => 'Supervisor Guest'])
            ->assertRedirect();

        $guest = Guest::query()->where('property_id', $property->id)->where('display_name', 'Supervisor Guest')->firstOrFail();
        $this->actingAs($supervisor)
            ->put("/properties/{$property->id}/guests/{$guest->id}", [
                'display_name' => 'Updated Supervisor Guest',
                'reason' => 'Corrected synthetic record',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'display_name' => 'Updated Supervisor Guest']);
        $this->assertDatabaseHas('guest_events', [
            'guest_id' => $guest->id,
            'event_type' => 'updated',
            'reason' => 'Corrected synthetic record',
            'actor_id' => $supervisor->id,
        ]);
    }

    public function test_supervisor_can_deactivate_with_reason_and_review_guest_activity(): void
    {
        $supervisor = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($supervisor, $property, 'supervisor');
        $guest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Review Guest', 'is_active' => true]);

        $this->actingAs($supervisor)
            ->patch("/properties/{$property->id}/guests/{$guest->id}/deactivate")
            ->assertSessionHasErrors('reason');

        $this->actingAs($supervisor)
            ->patch("/properties/{$property->id}/guests/{$guest->id}/deactivate", ['reason' => 'No longer needed'])
            ->assertRedirect();

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_active' => false]);
        $this->assertDatabaseHas('guest_events', [
            'guest_id' => $guest->id,
            'event_type' => 'deactivated',
            'reason' => 'No longer needed',
        ]);

        $this->actingAs($supervisor)->get("/properties/{$property->id}/guests")->assertOk();
        $this->actingAs($supervisor)->get("/properties/{$property->id}/guest-activity")
            ->assertOk()
            ->assertSee('guest_deactivated')
            ->assertSee('guest_list_viewed');
        $this->actingAs($supervisor)->get("/properties/{$property->id}/guest-report")->assertOk();

        $validatedGuest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Validated Guest', 'is_active' => true]);
        $this->actingAs($supervisor)
            ->patch("/properties/{$property->id}/guests/{$validatedGuest->id}/validate", ['reason' => 'Reviewed required fields'])
            ->assertRedirect();
        $this->assertDatabaseHas('guests', ['id' => $validatedGuest->id, 'is_validated' => true]);
    }

    public function test_receptionist_cannot_create_guest_for_unassigned_property(): void
    {
        $receptionist = User::factory()->create();
        $assigned = Property::factory()->create();
        $unassigned = Property::factory()->create();
        $this->addMembership($receptionist, $assigned, 'receptionist');

        $this->actingAs($receptionist)
            ->post("/properties/{$unassigned->id}/guests", ['display_name' => 'Unassigned Guest', 'notes' => 'Synthetic'])
            ->assertNotFound();

        $this->assertDatabaseMissing('guests', ['property_id' => $unassigned->id, 'display_name' => 'Unassigned Guest']);
    }

    public function test_receptionist_cannot_edit_or_update_direct_guest_id_from_another_property(): void
    {
        $receptionist = User::factory()->create();
        $assigned = Property::factory()->create();
        $other = Property::factory()->create();
        $this->addMembership($receptionist, $assigned, 'receptionist');
        $guest = Guest::forceCreate(['property_id' => $other->id, 'display_name' => 'Foreign Guest', 'is_active' => true]);

        $this->actingAs($receptionist)
            ->get("/properties/{$assigned->id}/guests/{$guest->id}/edit")
            ->assertNotFound();
        $this->actingAs($receptionist)
            ->put("/properties/{$assigned->id}/guests/{$guest->id}", [
                'display_name' => 'Changed',
                'reason' => 'Attempted cross-property update',
            ])
            ->assertNotFound();
        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'display_name' => 'Foreign Guest']);
    }

    public function test_owner_can_reject_deactivation_request_without_deactivating_guest(): void
    {
        $receptionist = User::factory()->create();
        $property = Property::factory()->create();
        $owner = User::factory()->create();
        $ownerProperty = Property::factory()->create();
        $this->addMembership($receptionist, $property, 'receptionist');
        $this->addMembership($owner, $ownerProperty, 'owner');
        $guest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Keep Guest', 'is_active' => true]);

        $this->actingAs($receptionist)
            ->patch("/properties/{$property->id}/guests/{$guest->id}/deactivate", ['reason' => 'Possible duplicate'])
            ->assertRedirect();
        $requestId = DB::table('guest_deactivation_requests')->where('guest_id', $guest->id)->value('id');

        $otherProperty = Property::factory()->create();
        $this->actingAs($owner)
            ->patch("/properties/{$otherProperty->id}/guest-deactivation-requests/{$requestId}/approve", ['reason' => 'Wrong property'])
            ->assertNotFound();
        $this->assertDatabaseHas('guest_deactivation_requests', ['id' => $requestId, 'status' => 'pending']);

        $this->actingAs($owner)
            ->patch("/properties/{$property->id}/guest-deactivation-requests/{$requestId}/reject", ['reason' => 'Record verified'])
            ->assertRedirect();
        $this->actingAs($owner)
            ->patch("/properties/{$property->id}/guest-deactivation-requests/{$requestId}/approve", ['reason' => 'Replay'])
            ->assertNotFound();

        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_active' => true]);
        $this->assertDatabaseHas('guest_deactivation_requests', ['id' => $requestId, 'status' => 'rejected']);
        $this->assertDatabaseHas('guest_deactivation_requests', [
            'id' => $requestId,
            'status' => 'rejected',
            'approver_id' => $owner->id,
            'decision_reason' => 'Record verified',
        ]);
        $this->assertDatabaseHas('guest_events', [
            'guest_id' => $guest->id,
            'event_type' => 'deactivation_rejected',
            'reason' => 'Record verified',
            'actor_id' => $owner->id,
        ]);
    }

    public function test_only_owner_can_decide_pending_deactivation_and_decisions_require_reasons(): void
    {
        $receptionist = User::factory()->create();
        $owner = User::factory()->create();
        $property = Property::factory()->create();
        $this->addMembership($receptionist, $property, 'receptionist');
        $this->addMembership($owner, $property, 'owner');
        $guest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Pending Guest', 'is_active' => true]);

        $this->actingAs($receptionist)
            ->patch("/properties/{$property->id}/guests/{$guest->id}/deactivate", ['reason' => 'Needs review'])
            ->assertRedirect();
        $requestId = DB::table('guest_deactivation_requests')->where('guest_id', $guest->id)->value('id');

        $this->actingAs($receptionist)
            ->patch("/properties/{$property->id}/guest-deactivation-requests/{$requestId}/approve", ['reason' => 'Unauthorized'])
            ->assertNotFound();
        $this->actingAs($receptionist)
            ->patch("/properties/{$property->id}/guest-deactivation-requests/{$requestId}/reject", ['reason' => 'Unauthorized'])
            ->assertNotFound();
        $this->assertDatabaseHas('guest_deactivation_requests', ['id' => $requestId, 'status' => 'pending']);
        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_active' => true]);

        $this->actingAs($owner)
            ->patch("/properties/{$property->id}/guest-deactivation-requests/{$requestId}/approve")
            ->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('guest_deactivation_requests', ['id' => $requestId, 'status' => 'pending']);
        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'is_active' => true]);
    }

    public function test_supervisor_cannot_review_another_property_guest_activity(): void
    {
        $supervisor = User::factory()->create();
        $assigned = Property::factory()->create();
        $other = Property::factory()->create();
        $this->addMembership($supervisor, $assigned, 'supervisor');

        $this->actingAs($supervisor)
            ->get("/properties/{$other->id}/guest-activity")
            ->assertNotFound();
    }

    private function addMembership(User $user, Property $property, string $role): void
    {
        DB::table('property_user')->insert([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_list_guests(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests")
            ->assertOk()
            ->assertSee('No guests yet.');

        DB::table('guests')->insert([
            'property_id' => $property->id,
            'display_name' => 'John Guest',
            'notes' => 'VIP repeat visitor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests")
            ->assertOk()
            ->assertSee('John Guest')
            ->assertSee('VIP repeat visitor');
    }

    public function test_admin_can_create_guest(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests/create")
            ->assertOk()
            ->assertSee('display_name', false);

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/guests", [
                'display_name' => 'New Guest Person',
                'notes' => 'Some notes',
                'property_id' => $property->id + 1000,
                'is_active' => false,
                'is_validated' => true,
            ])
            ->assertRedirect(route('properties.guests.index', $property));

        $this->assertDatabaseHas('guests', [
            'property_id' => $property->id,
            'display_name' => 'New Guest Person',
            'notes' => 'Some notes',
            'is_active' => true,
            'is_validated' => false,
        ]);

        $this->assertDatabaseHas('guest_events', [
            'property_id' => $property->id,
            'event_type' => 'created',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_guest_creation_validates_input(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/guests", [])
            ->assertSessionHasErrors('display_name');

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/guests", [
                'display_name' => str_repeat('A', 201),
            ])
            ->assertSessionHasErrors('display_name');

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/guests", [
                'display_name' => 'Valid',
                'notes' => str_repeat('B', 501),
            ])
            ->assertSessionHasErrors('notes');
    }

    public function test_admin_can_edit_guest(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $guest = Guest::forceCreate([
            'property_id' => $property->id,
            'display_name' => 'Old Name',
            'notes' => 'Old notes',
            'is_active' => true,
            'is_validated' => true,
        ]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests/{$guest->id}/edit")
            ->assertOk()
            ->assertSee('Old Name');

        $this->actingAs($admin)
            ->put("/properties/{$property->id}/guests/{$guest->id}", [
                'display_name' => 'New Name',
                'notes' => 'New notes',
                'property_id' => $property->id + 1000,
                'is_active' => false,
                'is_validated' => true,
            ])
            ->assertRedirect(route('properties.guests.index', $property));

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'property_id' => $property->id,
            'display_name' => 'New Name',
            'notes' => 'New notes',
            'is_active' => true,
            'is_validated' => true,
        ]);

        $event = DB::table('guest_events')
            ->where('guest_id', $guest->id)
            ->where('event_type', 'updated')
            ->first();
        $this->assertNotNull($event);
        $changes = json_decode($event->changes, true);
        $this->assertSame(['display_name', 'notes'], $changes);
    }

    public function test_admin_can_deactivate_guest(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $guest = Guest::forceCreate([
            'property_id' => $property->id,
            'display_name' => 'To Deactivate',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch("/properties/{$property->id}/guests/{$guest->id}/deactivate", ['reason' => 'Record is no longer active'])
            ->assertRedirect(route('properties.guests.index', $property));

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('guest_events', [
            'guest_id' => $guest->id,
            'event_type' => 'deactivated',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_cross_property_guest_access_denied(): void
    {
        $admin = $this->platformAdmin();
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();
        $guest = Guest::forceCreate([
            'property_id' => $propertyA->id,
            'display_name' => 'Property A Guest',
            'is_active' => true,
        ]);

        // Edit guest from wrong property
        $this->actingAs($admin)
            ->get("/properties/{$propertyB->id}/guests/{$guest->id}/edit")
            ->assertNotFound();

        // Update guest from wrong property
        $this->actingAs($admin)
            ->put("/properties/{$propertyB->id}/guests/{$guest->id}", [
                'display_name' => 'Hacked',
            ])
            ->assertNotFound();

        // Deactivate guest from wrong property
        $this->actingAs($admin)
            ->patch("/properties/{$propertyB->id}/guests/{$guest->id}/deactivate")
            ->assertNotFound();

        // Guest unchanged
        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'display_name' => 'Property A Guest',
            'is_active' => true,
        ]);
    }

    public function test_inactive_property_rejects_guest_creation(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests")
            ->assertNotFound();

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/guests", [
                'display_name' => 'Should fail',
            ])
            ->assertNotFound();
    }

    public function test_search_filters_guests_by_name(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Alice Smith', 'is_active' => true]);
        Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Bob Jones', 'is_active' => true]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests?q=Alice")
            ->assertOk()
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    }

    public function test_status_filter_works(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Active Guest', 'is_active' => true]);
        Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Inactive Guest', 'is_active' => false]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests?status=active")
            ->assertOk()
            ->assertSee('Active Guest')
            ->assertDontSee('Inactive Guest');

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests?status=inactive")
            ->assertOk()
            ->assertSee('Inactive Guest')
            ->assertDontSee('Active Guest');
    }

    public function test_invalid_status_filter_rejected(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests?status=hacked")
            ->assertStatus(422);
    }

    public function test_overlong_search_rejected(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests?q=".str_repeat('x', 101))
            ->assertStatus(422);
    }

    public function test_display_name_html_escaped(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        Guest::forceCreate([
            'property_id' => $property->id,
            'display_name' => '<script>alert("xss")</script>',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests");
        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_no_update_event_when_nothing_changes(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $guest = Guest::forceCreate([
            'property_id' => $property->id,
            'display_name' => 'Same Name',
            'notes' => 'Same notes',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put("/properties/{$property->id}/guests/{$guest->id}", [
                'display_name' => 'Same Name',
                'notes' => 'Same notes',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('guest_events', 0);
    }

    public function test_property_index_shows_guests_link(): void
    {
        $admin = $this->platformAdmin();
        Property::factory()->create(['name' => 'Test Hotel']);

        $this->actingAs($admin)
            ->get('/properties')
            ->assertOk()
            ->assertSee('Guests');
    }

    public function test_guest_audit_rows_reject_updates_and_deletes(): void
    {
        $actor = User::factory()->create();
        $property = Property::factory()->create();
        $guest = Guest::forceCreate(['property_id' => $property->id, 'display_name' => 'Audit Guest', 'is_active' => true]);
        $guestEventId = DB::table('guest_events')->insertGetId([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'event_type' => 'created',
            'changes' => null,
            'actor_id' => $actor->id,
            'created_at' => now(),
        ]);
        $accessEventId = DB::table('guest_access_events')->insertGetId([
            'property_id' => $property->id,
            'actor_id' => $actor->id,
            'event' => 'guest_list_viewed',
            'created_at' => now(),
        ]);

        foreach ([['guest_events', $guestEventId], ['guest_access_events', $accessEventId]] as [$table, $id]) {
            $original = (array) DB::table($table)->where('id', $id)->first();

            try {
                DB::table($table)->where('id', $id)->update(
                    $table === 'guest_events' ? ['event_type' => 'tampered'] : ['event' => 'tampered'],
                );
                $this->fail("{$table} accepted UPDATE");
            } catch (QueryException) {
                // Database trigger must reject changes to ordinary audit fields.
            }
            $this->assertSame($original, (array) DB::table($table)->where('id', $id)->first());

            try {
                DB::table($table)->where('id', $id)->delete();
                $this->fail("{$table} accepted DELETE");
            } catch (QueryException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
            $this->assertSame($original, (array) DB::table($table)->where('id', $id)->first());
        }
    }

    public function test_guest_export_and_sync_routes_do_not_exist(): void
    {
        $guestDataRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(function ($route): bool {
                $identifier = strtolower($route->uri().' '.$route->getName());

                return str_contains($identifier, 'guest')
                    && (str_contains($identifier, 'export') || str_contains($identifier, 'sync'));
            });

        $this->assertCount(0, $guestDataRoutes);
    }

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin->fresh();
    }
}
