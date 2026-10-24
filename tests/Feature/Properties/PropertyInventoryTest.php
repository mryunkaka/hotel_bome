<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_enforces_per_property_codes_and_room_type_tenant_foreign_key(): void
    {
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();

        DB::table('outlets')->insert([
            'property_id' => $propertyA->id,
            'name' => 'A Outlet',
            'code' => 'FOOD',
            'is_active' => true,
        ]);
        DB::table('outlets')->insert([
            'property_id' => $propertyB->id,
            'name' => 'B Outlet',
            'code' => 'FOOD',
            'is_active' => true,
        ]);

        try {
            DB::table('outlets')->insert([
                'property_id' => $propertyA->id,
                'name' => 'Duplicate Outlet',
                'code' => 'FOOD',
                'is_active' => true,
            ]);
            $this->fail('Database must reject duplicate outlet code within one property.');
        } catch (QueryException) {
            $this->assertDatabaseCount('outlets', 2);
        }

        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $propertyB->id,
            'name' => 'B Type',
            'code' => 'STD',
            'is_active' => true,
        ]);

        try {
            DB::table('rooms')->insert([
                'property_id' => $propertyA->id,
                'room_type_id' => $roomTypeId,
                'code' => '101',
                'is_active' => true,
            ]);
            $this->fail('Database must reject a room type owned by another property.');
        } catch (QueryException) {
            $this->assertDatabaseCount('rooms', 0);
        }
    }

    public function test_platform_admin_can_create_property_scoped_outlets_room_types_and_rooms(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get(route('properties.outlets.index', $property))
            ->assertOk()
            ->assertSee('Add outlet');

        $this->actingAs($admin)
            ->post(route('properties.outlets.store', $property), [
                'name' => 'Main Restaurant',
                'code' => 'MAIN',
            ])
            ->assertRedirect(route('properties.outlets.index', $property));

        $this->actingAs($admin)
            ->post(route('properties.room-types.store', $property), [
                'name' => 'Standard',
                'code' => 'STD',
            ])
            ->assertRedirect(route('properties.room-types.index', $property));

        $roomTypeId = (int) DB::table('room_types')->where('property_id', $property->id)->value('id');

        $this->actingAs($admin)
            ->post(route('properties.rooms.store', $property), [
                'room_type_id' => $roomTypeId,
                'code' => '101',
            ])
            ->assertRedirect(route('properties.rooms.index', $property));

        $this->assertDatabaseHas('outlets', ['property_id' => $property->id, 'code' => 'MAIN', 'is_active' => true]);
        $this->assertDatabaseHas('room_types', ['property_id' => $property->id, 'code' => 'STD', 'is_active' => true]);
        $this->assertDatabaseHas('rooms', ['property_id' => $property->id, 'room_type_id' => $roomTypeId, 'code' => '101', 'is_active' => true]);
    }

    public function test_inactive_property_rejects_inventory_creation_even_by_direct_post(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->post(route('properties.outlets.store', $property), [
                'name' => 'Closed Outlet',
                'code' => 'CLOSED',
            ])
            ->assertNotFound();
        $this->actingAs($admin)
            ->post(route('properties.room-types.store', $property), [
                'name' => 'Closed Type',
                'code' => 'CLOSED',
            ])
            ->assertNotFound();
        $this->actingAs($admin)
            ->post(route('properties.rooms.store', $property), [
                'room_type_id' => 1,
                'code' => '101',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('outlets', 0);
        $this->assertDatabaseCount('room_types', 0);
        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_room_type_selection_is_retained_when_room_code_validation_fails(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
        ]);
        DB::table('rooms')->insert([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '101',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('properties.rooms.index', $property))
            ->post(route('properties.rooms.store', $property), [
                'room_type_id' => $roomTypeId,
                'code' => '101',
            ])
            ->assertSessionHasErrors('code')
            ->assertRedirect(route('properties.rooms.index', $property));

        $this->actingAs($admin)
            ->get(route('properties.rooms.index', $property))
            ->assertOk()
            ->assertSee('value="'.$roomTypeId.'" selected', false);
    }

    public function test_inactive_property_inventory_remains_readable_but_has_no_mutation_forms(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create(['is_active' => false]);
        DB::table('outlets')->insert([
            'property_id' => $property->id,
            'name' => 'Archived Outlet',
            'code' => 'ARCHIVE',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('properties.outlets.index', $property))
            ->assertOk()
            ->assertSee('Archived Outlet')
            ->assertSee('Existing inventory remains visible')
            ->assertDontSee('Add outlet')
            ->assertDontSee('Deactivate');
    }

    public function test_room_cannot_use_an_inactive_room_type(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Closed Type',
            'code' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('properties.rooms.index', $property))
            ->post(route('properties.rooms.store', $property), [
                'room_type_id' => $roomTypeId,
                'code' => '101',
            ])
            ->assertSessionHasErrors('room_type_id');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_deactivated_property_item_ids_are_scoped(): void
    {
        $admin = $this->platformAdmin();
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();
        $outletId = DB::table('outlets')->insertGetId([
            'property_id' => $propertyB->id,
            'name' => 'Another outlet',
            'code' => 'OTHER',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('properties.outlets.deactivate', [$propertyA, $outletId]))
            ->assertNotFound();

        $this->assertDatabaseHas('outlets', ['id' => $outletId, 'property_id' => $propertyB->id, 'is_active' => true]);
    }

    public function test_platform_admin_sees_deactivation_controls_for_inventory(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        DB::table('room_types')->insert([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('properties.room-types.index', $property))
            ->assertOk()
            ->assertSee('Deactivate');
    }

    public function test_platform_admin_can_update_property_scoped_inventory(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $outletId = DB::table('outlets')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Original Outlet',
            'code' => 'OLD',
            'is_active' => true,
        ]);
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Original Type',
            'code' => 'OLD',
            'is_active' => true,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '101',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('properties.outlets.edit', [$property, $outletId]))
            ->assertOk()
            ->assertSee('Original Outlet');
        $this->actingAs($admin)
            ->get(route('properties.room-types.edit', [$property, $roomTypeId]))
            ->assertOk()
            ->assertSee('Original Type');
        $this->actingAs($admin)
            ->get(route('properties.rooms.edit', [$property, $roomId]))
            ->assertOk()
            ->assertSee('Original Type');

        $this->actingAs($admin)->put(route('properties.outlets.update', [$property, $outletId]), [
            'name' => 'Renamed Outlet',
            'code' => 'NEW',
        ])->assertRedirect(route('properties.outlets.index', $property));

        $this->actingAs($admin)->put(route('properties.room-types.update', [$property, $roomTypeId]), [
            'name' => 'Renamed Type',
            'code' => 'NEW',
        ])->assertRedirect(route('properties.room-types.index', $property));

        $this->actingAs($admin)->put(route('properties.rooms.update', [$property, $roomId]), [
            'code' => '102',
        ])->assertRedirect(route('properties.rooms.index', $property));

        $this->assertDatabaseHas('outlets', ['id' => $outletId, 'property_id' => $property->id, 'name' => 'Renamed Outlet', 'code' => 'NEW']);
        $this->assertDatabaseHas('room_types', ['id' => $roomTypeId, 'property_id' => $property->id, 'name' => 'Renamed Type', 'code' => 'NEW']);
        $this->assertDatabaseHas('rooms', ['id' => $roomId, 'property_id' => $property->id, 'room_type_id' => $roomTypeId, 'code' => '102']);
    }

    public function test_active_non_admin_memberships_cannot_read_or_mutate_property_master_inventory(): void
    {
        $property = Property::factory()->create();
        $otherProperty = Property::factory()->create();
        $outletId = DB::table('outlets')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Protected Outlet',
            'code' => 'SAFE',
            'is_active' => true,
        ]);
        DB::table('outlets')->insert([
            'property_id' => $otherProperty->id,
            'name' => 'Other Property Outlet',
            'code' => 'OTHER',
            'is_active' => true,
        ]);

        foreach (['hotel_admin', 'housekeeping', 'operations', 'front_desk', 'finance'] as $role) {
            $member = User::factory()->create(['status' => 'active']);
            DB::table('property_user')->insert([
                'user_id' => $member->id,
                'property_id' => $property->id,
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->actingAs($member)
                ->get(route('properties.outlets.index', $property))
                ->assertNotFound();

            $this->actingAs($member)
                ->put(route('properties.outlets.update', [$property, $outletId]), [
                    'name' => 'Unauthorized '.$role,
                    'code' => 'BAD',
                ])
                ->assertNotFound();
        }

        $this->assertDatabaseHas('outlets', [
            'id' => $outletId,
            'name' => 'Protected Outlet',
            'code' => 'SAFE',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('outlets', 2);
    }

    public function test_staff_cannot_update_inventory_by_direct_id(): void
    {
        $staff = User::factory()->create();
        $property = Property::factory()->create();
        $outletId = DB::table('outlets')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Protected Outlet',
            'code' => 'SAFE',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->put(route('properties.outlets.update', [$property, $outletId]), ['name' => 'Attacker', 'code' => 'OWNED'])
            ->assertNotFound();

        $this->assertDatabaseHas('outlets', ['id' => $outletId, 'name' => 'Protected Outlet', 'code' => 'SAFE']);
    }

    public function test_inventory_deactivation_retains_rows_and_protects_active_room_references(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $outletId = DB::table('outlets')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Main Restaurant',
            'code' => 'MAIN',
            'is_active' => true,
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

        $this->actingAs($admin)
            ->patch(route('properties.room-types.deactivate', [$property, $roomTypeId]))
            ->assertStatus(409);

        $this->actingAs($admin)
            ->patch(route('properties.rooms.deactivate', [$property, $roomId]))
            ->assertRedirect(route('properties.rooms.index', $property));

        $this->actingAs($admin)
            ->patch(route('properties.room-types.deactivate', [$property, $roomTypeId]))
            ->assertRedirect(route('properties.room-types.index', $property));

        $this->actingAs($admin)
            ->patch(route('properties.outlets.deactivate', [$property, $outletId]))
            ->assertRedirect(route('properties.outlets.index', $property));

        $this->assertDatabaseHas('outlets', ['id' => $outletId, 'is_active' => false]);
        $this->assertDatabaseHas('room_types', ['id' => $roomTypeId, 'is_active' => false]);
        $this->assertDatabaseHas('rooms', ['id' => $roomId, 'is_active' => false]);
        $this->assertDatabaseCount('outlets', 1);
        $this->assertDatabaseCount('room_types', 1);
        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_room_cannot_use_room_type_from_another_property(): void
    {
        $admin = $this->platformAdmin();
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();

        $this->actingAs($admin)->post(route('properties.room-types.store', $propertyB), [
            'name' => 'Standard',
            'code' => 'STD',
        ])->assertRedirect();

        $roomTypeId = (int) DB::table('room_types')->where('property_id', $propertyB->id)->value('id');

        $this->actingAs($admin)
            ->from(route('properties.rooms.index', $propertyA))
            ->post(route('properties.rooms.store', $propertyA), [
                'room_type_id' => $roomTypeId,
                'code' => '101',
            ])
            ->assertSessionHasErrors('room_type_id');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_room_board_rejects_invalid_filters_and_array_search_input(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();

        $cases = [
            [['housekeeping_status' => 'archived'], 'housekeeping_status'],
            [['active' => 'deleted'], 'active'],
            [['q' => ['101']], 'q'],
            [['q' => str_repeat('x', 41)], 'q'],
        ];

        foreach ($cases as [$query, $field]) {
            $this->actingAs($admin)
                ->get(route('properties.rooms.index', $property).'?'.http_build_query($query))
                ->assertSessionHasErrors($field);
        }
    }

    public function test_room_inventory_list_has_mobile_cards_and_desktop_table(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
        ]);
        DB::table('rooms')->insert([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => 'QA-101',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('properties.rooms.index', $property))
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee('QA-101')
            ->assertSee('Standard')
            ->assertSee('Housekeeping status')
            ->assertSee('maxlength="40"', false);

        $this->actingAs($admin)
            ->get(route('properties.rooms.index', $property).'?housekeeping_status=all&active=all&q=QA')
            ->assertOk()
            ->assertSee('QA-101');
    }

    public function test_outlet_and_room_type_lists_have_mobile_cards_and_desktop_table(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        DB::table('outlets')->insert(['property_id' => $property->id, 'name' => 'Lobby Cafe', 'code' => 'CAFE', 'is_active' => true]);
        DB::table('room_types')->insert(['property_id' => $property->id, 'name' => 'Deluxe', 'code' => 'DLX', 'is_active' => true]);

        foreach ([
            ['route' => 'properties.outlets.index', 'name' => 'Lobby Cafe', 'code' => 'CAFE'],
            ['route' => 'properties.room-types.index', 'name' => 'Deluxe', 'code' => 'DLX'],
        ] as $page) {
            $this->actingAs($admin)
                ->get(route($page['route'], $property))
                ->assertOk()
                ->assertSee('md:hidden', false)
                ->assertSee('md:block', false)
                ->assertSee($page['name'])
                ->assertSee($page['code']);
        }
    }

    public function test_inventory_lists_are_property_scoped_and_escape_untrusted_names(): void
    {
        $admin = $this->platformAdmin();
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();
        DB::table('outlets')->insert([
            ['property_id' => $propertyA->id, 'name' => '<script>alert(1)</script>', 'code' => 'SAFE', 'is_active' => true],
            ['property_id' => $propertyB->id, 'name' => 'Secret Property B Outlet', 'code' => 'OTHER', 'is_active' => true],
        ]);

        $this->actingAs($admin)
            ->get(route('properties.outlets.index', $propertyA))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Secret Property B Outlet');
    }

    public function test_staff_cannot_view_or_mutate_another_property_inventory(): void
    {
        $staff = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($staff)
            ->get(route('properties.rooms.index', $property))
            ->assertNotFound();

        $this->actingAs($staff)
            ->post(route('properties.outlets.store', $property), ['name' => 'Unauthorized', 'code' => 'BAD'])
            ->assertNotFound();

        $this->assertDatabaseCount('outlets', 0);
    }

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin;
    }
}
