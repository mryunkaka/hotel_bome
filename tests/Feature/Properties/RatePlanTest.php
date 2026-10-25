<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\RatePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RatePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_rate_plans(): void
    {
        $property = Property::factory()->create();

        $this->get("/properties/{$property->id}/rate-plans")->assertRedirect(route('login'));
        $this->post("/properties/{$property->id}/rate-plans")->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_rate_plans(): void
    {
        $staff = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($staff)->get("/properties/{$property->id}/rate-plans")->assertNotFound();
        $this->actingAs($staff)->post("/properties/{$property->id}/rate-plans", [
            'name' => 'Standard', 'code' => 'STD', 'base_price_per_night' => 500000,
        ])->assertNotFound();
    }

    public function test_admin_can_list_rate_plans(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans")
            ->assertOk()
            ->assertSee('No rate plans yet.');

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'name' => 'Standard Room',
            'code' => 'STD',
            'base_price_per_night' => 750000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans")
            ->assertOk()
            ->assertSee('Standard Room')
            ->assertSee('STD')
            ->assertSee('Rp750.000');
    }

    public function test_admin_can_create_rate_plan(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans/create")
            ->assertOk();

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Deluxe Suite',
                'code' => 'DLX',
                'base_price_per_night' => 1500000,
                'tax_percentage' => '10.00',
                'effective_from' => '2026-11-01',
                'effective_until' => '2027-03-31',
            ])
            ->assertRedirect(route('properties.rate-plans.index', $property));

        $this->assertDatabaseHas('rate_plans', [
            'property_id' => $property->id,
            'name' => 'Deluxe Suite',
            'code' => 'DLX',
            'base_price_per_night' => 1500000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('rate_plan_events', [
            'property_id' => $property->id,
            'event_type' => 'created',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_rate_plan_validates_input(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        // Missing required fields
        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [])
            ->assertSessionHasErrors(['name', 'code', 'base_price_per_night']);

        // Name too long
        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => str_repeat('A', 101),
                'code' => 'X',
                'base_price_per_night' => 100,
            ])
            ->assertSessionHasErrors('name');

        // Invalid code format
        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Test',
                'code' => 'has spaces',
                'base_price_per_night' => 100,
            ])
            ->assertSessionHasErrors('code');

        // Negative price
        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Test',
                'code' => 'TST',
                'base_price_per_night' => -1,
            ])
            ->assertSessionHasErrors('base_price_per_night');

        // Tax out of range
        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Test',
                'code' => 'TST',
                'base_price_per_night' => 100,
                'tax_percentage' => 100,
            ])
            ->assertSessionHasErrors('tax_percentage');

        // effective_until before effective_from
        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Test',
                'code' => 'TST',
                'base_price_per_night' => 100,
                'effective_from' => '2027-01-01',
                'effective_until' => '2026-12-31',
            ])
            ->assertSessionHasErrors('effective_until');
    }

    public function test_code_unique_per_property(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'name' => 'Existing',
            'code' => 'STD',
            'base_price_per_night' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Duplicate',
                'code' => 'STD',
                'base_price_per_night' => 200,
            ])
            ->assertSessionHasErrors('code');

        // Same code on different property is OK
        $otherProperty = Property::factory()->create();
        $this->actingAs($admin)
            ->post("/properties/{$otherProperty->id}/rate-plans", [
                'name' => 'Same code, different property',
                'code' => 'STD',
                'base_price_per_night' => 200,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rate_plans', [
            'property_id' => $otherProperty->id,
            'code' => 'STD',
        ]);
    }

    public function test_admin_can_edit_rate_plan(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $plan = RatePlan::forceCreate([
            'property_id' => $property->id,
            'name' => 'Old Rate',
            'code' => 'OLD',
            'base_price_per_night' => 500000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans/{$plan->id}/edit")
            ->assertOk()
            ->assertSee('Old Rate');

        $this->actingAs($admin)
            ->put("/properties/{$property->id}/rate-plans/{$plan->id}", [
                'name' => 'New Rate',
                'code' => 'NEW',
                'base_price_per_night' => 600000,
                'tax_percentage' => '10.00',
            ])
            ->assertRedirect(route('properties.rate-plans.index', $property));

        $this->assertDatabaseHas('rate_plans', [
            'id' => $plan->id,
            'name' => 'New Rate',
            'code' => 'NEW',
            'base_price_per_night' => 600000,
        ]);

        $event = DB::table('rate_plan_events')
            ->where('rate_plan_id', $plan->id)
            ->where('event_type', 'updated')
            ->first();
        $this->assertNotNull($event);
    }

    public function test_admin_can_deactivate_rate_plan(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $plan = RatePlan::forceCreate([
            'property_id' => $property->id,
            'name' => 'To Deactivate',
            'code' => 'DEA',
            'base_price_per_night' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch("/properties/{$property->id}/rate-plans/{$plan->id}/deactivate")
            ->assertRedirect(route('properties.rate-plans.index', $property));

        $this->assertDatabaseHas('rate_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('rate_plan_events', [
            'rate_plan_id' => $plan->id,
            'event_type' => 'deactivated',
        ]);
    }

    public function test_cross_property_rate_plan_access_denied(): void
    {
        $admin = $this->platformAdmin();
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();
        $plan = RatePlan::forceCreate([
            'property_id' => $propertyA->id,
            'name' => 'A Only',
            'code' => 'AON',
            'base_price_per_night' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get("/properties/{$propertyB->id}/rate-plans/{$plan->id}/edit")
            ->assertNotFound();

        $this->actingAs($admin)
            ->put("/properties/{$propertyB->id}/rate-plans/{$plan->id}", [
                'name' => 'Hacked', 'code' => 'HCK', 'base_price_per_night' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch("/properties/{$propertyB->id}/rate-plans/{$plan->id}/deactivate")
            ->assertNotFound();

        $this->assertDatabaseHas('rate_plans', [
            'id' => $plan->id,
            'name' => 'A Only',
            'is_active' => true,
        ]);
    }

    public function test_inactive_property_rejects_rate_plan_creation(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans")
            ->assertNotFound();

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Nope', 'code' => 'NOP', 'base_price_per_night' => 100,
            ])
            ->assertNotFound();
    }

    public function test_search_filters_rate_plans(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        RatePlan::forceCreate(['property_id' => $property->id, 'name' => 'Deluxe Room', 'code' => 'DLX', 'base_price_per_night' => 1000, 'is_active' => true]);
        RatePlan::forceCreate(['property_id' => $property->id, 'name' => 'Standard', 'code' => 'STD', 'base_price_per_night' => 500, 'is_active' => true]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans?q=Deluxe")
            ->assertOk()
            ->assertSee('Deluxe Room')
            ->assertDontSee('Standard');

        // Search by code
        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans?q=STD")
            ->assertOk()
            ->assertSee('Standard')
            ->assertDontSee('Deluxe');
    }

    public function test_status_filter_works(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        RatePlan::forceCreate(['property_id' => $property->id, 'name' => 'Active Plan', 'code' => 'ACT', 'base_price_per_night' => 100, 'is_active' => true]);
        RatePlan::forceCreate(['property_id' => $property->id, 'name' => 'Dead Plan', 'code' => 'DEA', 'base_price_per_night' => 100, 'is_active' => false]);

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans?status=active")
            ->assertSee('Active Plan')
            ->assertDontSee('Dead Plan');

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans?status=inactive")
            ->assertSee('Dead Plan')
            ->assertDontSee('Active Plan');
    }

    public function test_invalid_status_filter_rejected(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans?status=evil")
            ->assertStatus(422);
    }

    public function test_overlong_search_rejected(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans?q=".str_repeat('x', 101))
            ->assertStatus(422);
    }

    public function test_html_escaped(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'name' => '<script>xss</script>',
            'code' => 'XSS',
            'base_price_per_night' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("/properties/{$property->id}/rate-plans");
        $response->assertOk();
        $response->assertDontSee('<script>xss</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_code_normalized_to_uppercase(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->post("/properties/{$property->id}/rate-plans", [
                'name' => 'Lowercase Code',
                'code' => 'low',
                'base_price_per_night' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rate_plans', [
            'property_id' => $property->id,
            'code' => 'LOW',
        ]);
    }

    public function test_property_index_shows_rates_link(): void
    {
        $admin = $this->platformAdmin();
        Property::factory()->create();

        $this->actingAs($admin)
            ->get('/properties')
            ->assertOk()
            ->assertSee('Rates');
    }

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin->fresh();
    }
}
