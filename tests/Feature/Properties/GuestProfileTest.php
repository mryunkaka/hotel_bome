<?php

namespace Tests\Feature\Properties;

use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
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
            ])
            ->assertRedirect(route('properties.guests.index', $property));

        $this->assertDatabaseHas('guests', [
            'property_id' => $property->id,
            'display_name' => 'New Guest Person',
            'notes' => 'Some notes',
            'is_active' => true,
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
        ]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/guests/{$guest->id}/edit")
            ->assertOk()
            ->assertSee('Old Name');

        $this->actingAs($admin)
            ->put("/properties/{$property->id}/guests/{$guest->id}", [
                'display_name' => 'New Name',
                'notes' => 'New notes',
            ])
            ->assertRedirect(route('properties.guests.index', $property));

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'display_name' => 'New Name',
            'notes' => 'New notes',
        ]);

        $event = DB::table('guest_events')
            ->where('guest_id', $guest->id)
            ->where('event_type', 'updated')
            ->first();
        $this->assertNotNull($event);
        $changes = json_decode($event->changes, true);
        $this->assertSame('Old Name', $changes['display_name']['from']);
        $this->assertSame('New Name', $changes['display_name']['to']);
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
            ->patch("/properties/{$property->id}/guests/{$guest->id}/deactivate")
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

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin->fresh();
    }
}
