<?php

namespace Tests\Feature\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyAdminRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_platform_admin_routes_deny_active_non_admin_for_all_methods(): void
    {
        $staff = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();
        DB::table('property_user')->insert([
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $routes = [
            ['admin.audit.index', 'GET'],
            ['operator.health', 'GET'],
            ['admin.users.index', 'GET'],
            ['admin.users.accounts.index', 'GET'],
            ['admin.users.memberships.index', 'GET'],
            ['admin.users.approve', 'POST', [1]],
            ['admin.users.reject', 'POST', [1]],
            ['admin.users.memberships.store', 'POST', [1]],
            ['admin.users.memberships.destroy', 'DELETE', [1, 1]],
            ['admin.users.suspend', 'POST', [1]],
            ['admin.users.reactivate', 'POST', [1]],
            ['properties.index', 'GET'],
            ['properties.create', 'GET'],
            ['properties.store', 'POST'],
            ['properties.edit', 'GET', [1]],
            ['properties.update', 'PUT', [1]],
            ['properties.deactivate', 'PATCH', [1]],
            ['properties.outlets.index', 'GET', [1]],
            ['properties.outlets.store', 'POST', [1]],
            ['properties.outlets.edit', 'GET', [1, 1]],
            ['properties.outlets.update', 'PUT', [1, 1]],
            ['properties.outlets.deactivate', 'PATCH', [1, 1]],
            ['properties.room-types.index', 'GET', [1]],
            ['properties.room-types.store', 'POST', [1]],
            ['properties.room-types.edit', 'GET', [1, 1]],
            ['properties.room-types.update', 'PUT', [1, 1]],
            ['properties.room-types.deactivate', 'PATCH', [1, 1]],
            ['properties.rooms.index', 'GET', [1]],
            ['properties.rooms.history', 'GET', [1]],
            ['properties.rooms.store', 'POST', [1]],
            ['properties.rooms.edit', 'GET', [1, 1]],
            ['properties.rooms.update', 'PUT', [1, 1]],
            ['properties.rooms.deactivate', 'PATCH', [1, 1]],
            ['properties.rooms.status.update', 'PATCH', [1, 1]],
            ['properties.room-blocks.store', 'POST', [1]],
            ['properties.room-blocks.cancel', 'PATCH', [1, 1, 1]],
        ];

        $this->assertCount(36, $routes);

        foreach ($routes as $route) {
            [$name, $method, $parameters] = array_pad($route, 3, []);

            $this->actingAs($staff)
                ->call($method, route($name, $parameters ?? []), [
                    'name' => 'Denied test data',
                    'code' => 'DENY',
                    'reason' => 'Authorization regression test',
                    'status' => 'clean',
                    'start_date' => today()->toDateString(),
                    'end_date' => today()->addDay()->toDateString(),
                ])
                ->assertNotFound();
        }
    }
}
