<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_platform_admins_can_manage_properties(): void
    {
        $this->get('/properties')->assertRedirect(route('login'));

        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get('/properties')
            ->assertNotFound();
    }

    public function test_property_list_provides_mobile_cards_and_desktop_table(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create(['name' => 'Bome Mobile Test', 'code' => 'BOME-MOB']);

        $this->actingAs($admin)
            ->get('/properties')
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee('Bome Mobile Test')
            ->assertSee('BOME-MOB')
            ->assertSee('Edit');
    }

    public function test_platform_admin_can_view_property_list_and_create_form(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->get('/properties')
            ->assertOk()
            ->assertSee('No properties yet.');

        $this->actingAs($admin)
            ->get('/properties/create')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false);
    }

    public function test_staff_cannot_mutate_properties(): void
    {
        $staff = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($staff)
            ->put("/properties/{$property->id}", ['name' => 'Attacker', 'code' => $property->code])
            ->assertNotFound();

        $this->actingAs($staff)
            ->patch("/properties/{$property->id}/deactivate")
            ->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => $property->name,
            'is_active' => true,
        ]);
    }

    public function test_platform_admin_can_create_and_update_a_property(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post('/properties', [
                'name' => 'Hotel Bome Jakarta',
                'code' => 'BOME-JKT',
            ])
            ->assertRedirect('/properties');

        $property = Property::query()->sole();
        $this->assertSame('Hotel Bome Jakarta', $property->name);
        $this->assertSame('BOME-JKT', $property->code);
        $this->assertTrue($property->is_active);

        $this->actingAs($admin)
            ->put("/properties/{$property->id}", [
                'name' => 'Hotel Bome Central',
                'code' => 'BOME-JKT',
            ])
            ->assertRedirect('/properties');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => 'Hotel Bome Central',
            'code' => 'BOME-JKT',
        ]);
    }

    public function test_property_validation_rejects_duplicate_or_invalid_codes(): void
    {
        $admin = $this->platformAdmin();
        Property::factory()->create(['code' => 'BOME-JKT']);

        $this->actingAs($admin)
            ->from('/properties/create')
            ->post('/properties', [
                'name' => 'Another hotel',
                'code' => 'BOME-JKT',
            ])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)
            ->from('/properties/create')
            ->post('/properties', [
                'name' => 'Another hotel',
                'code' => 'invalid code!',
            ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('properties', 1);
    }

    public function test_platform_admin_can_deactivate_without_deleting_property(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->patch("/properties/{$property->id}/deactivate")
            ->assertRedirect('/properties');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_active' => false,
        ]);

        $this->delete("/properties/{$property->id}")->assertMethodNotAllowed();
        $this->assertDatabaseCount('properties', 1);
    }

    public function test_platform_admin_dashboard_shows_property_navigation(): void
    {
        $this->actingAs($this->platformAdmin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Properties')
            ->assertSee(route('properties.index'), false)
            ->assertSee('Hotel operations are being built in verified slices.')
            ->assertDontSee('Rooms, reservations, and finance are ready to be added.');
    }

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin;
    }
}
