<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use App\Services\Properties\EngineeringAssets;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EngineeringAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_member_can_create_edit_and_deactivate_property_asset_with_history(): void
    {
        [$property, $actor] = $this->setupProperty();
        $this->actingAs($actor)->post(route('properties.operations.assets.store', $property), [
            'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing', 'location' => 'Basement', 'status' => 'operational',
        ])->assertRedirect();
        $assetId = DB::table('engineering_assets')->value('id');

        $this->actingAs($actor)->get(route('properties.operations.assets.index', $property))->assertOk()->assertSee('Water pump');
        $this->actingAs($actor)->put(route('properties.operations.assets.update', [$property, $assetId]), [
            'code' => 'pump-1', 'name' => 'Water pump north', 'category' => 'Plumbing', 'location' => 'Basement', 'status' => 'under_repair',
        ])->assertRedirect();
        $this->assertDatabaseHas('engineering_assets', ['id' => $assetId, 'code' => 'PUMP-1', 'status' => 'under_repair', 'name' => 'Water pump north']);
        $this->assertSame(2, DB::table('engineering_asset_events')->where('asset_id', $assetId)->count());

        $this->actingAs($actor)->patch(route('properties.operations.assets.deactivate', [$property, $assetId]), ['reason' => 'Replaced'])->assertRedirect();
        $this->assertDatabaseHas('engineering_assets', ['id' => $assetId, 'is_active' => false]);
        $this->assertSame(3, DB::table('engineering_asset_events')->where('asset_id', $assetId)->count());
    }

    public function test_duplicate_code_and_cross_property_asset_mutation_are_rejected(): void
    {
        [$property, $actor] = $this->setupProperty();
        [$otherProperty] = $this->setupProperty();
        $this->actingAs($actor)->post(route('properties.operations.assets.store', $property), [
            'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertRedirect();
        $assetId = DB::table('engineering_assets')->value('id');

        $this->actingAs($actor)->post(route('properties.operations.assets.store', $property), [
            'code' => 'pump-1', 'name' => 'Duplicate lowercase', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('engineering_assets', 1);
        $this->actingAs($actor)->put(route('properties.operations.assets.update', [$otherProperty, $assetId]), [
            'code' => 'PUMP-1', 'name' => 'Wrong property', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertNotFound();
        $this->assertDatabaseMissing('engineering_assets', ['name' => 'Wrong property']);
    }

    public function test_asset_list_defaults_to_active_and_renders_property_scoped_event_history(): void
    {
        [$property, $actor] = $this->setupProperty();
        $this->actingAs($actor)->post(route('properties.operations.assets.store', $property), [
            'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertRedirect();
        $assetId = DB::table('engineering_assets')->value('id');
        $this->actingAs($actor)->patch(route('properties.operations.assets.deactivate', [$property, $assetId]), ['reason' => 'Replaced'])->assertRedirect();

        $this->actingAs($actor)->get(route('properties.operations.assets.index', $property))
            ->assertOk()->assertDontSee('Water pump')->assertSee('Replaced');
        $this->actingAs($actor)->get(route('properties.operations.assets.index', [$property, 'status' => 'inactive']))
            ->assertOk()->assertSee('Water pump')->assertSee('Inactive')->assertSee('Replaced');
        $this->assertDatabaseHas('engineering_asset_events', ['asset_id' => $assetId, 'reason' => 'Replaced', 'event_type' => 'deactivated']);
    }

    public function test_asset_status_filter_rejects_unknown_values(): void
    {
        [$property, $actor] = $this->setupProperty();

        $this->actingAs($actor)
            ->get(route('properties.operations.assets.index', [$property, 'status' => 'archived']))
            ->assertSessionHasErrors('status');
    }

    public function test_work_order_can_reference_only_active_asset_from_same_property(): void
    {
        [$property, $actor] = $this->setupProperty();
        [$otherProperty] = $this->setupProperty();
        $assetId = DB::table('engineering_assets')->insertGetId([
            'property_id' => $otherProperty->id, 'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing',
            'status' => 'operational', 'is_active' => true, 'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($actor)->post(route('properties.operations.work-orders.store', $property), [
            'title' => 'Repair pump', 'asset_id' => $assetId,
        ])->assertNotFound();
        $this->assertSame(0, DB::table('work_orders')->count());
    }

    public function test_operations_member_cannot_read_or_create_assets_for_another_property(): void
    {
        [$propertyA, $actor] = $this->setupProperty();
        [$propertyB] = $this->setupProperty();

        $this->actingAs($actor)->get(route('properties.operations.assets.index', $propertyB))->assertNotFound();
        $this->actingAs($actor)->post(route('properties.operations.assets.store', $propertyB), [
            'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertNotFound();
        $this->assertSame(0, DB::table('engineering_assets')->count());
    }

    public function test_asset_update_and_deactivation_reject_ids_from_another_property(): void
    {
        [$propertyA, $actorA] = $this->setupProperty();
        [$propertyB, $actorB] = $this->setupProperty();
        $assetId = app(EngineeringAssets::class)->create($propertyB->id, [
            'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing', 'status' => 'operational',
        ], $actorB);

        $this->actingAs($actorA)->put(route('properties.operations.assets.update', [$propertyA, $assetId]), [
            'code' => 'PUMP-1', 'name' => 'Tampered pump', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertNotFound();
        $this->actingAs($actorA)->patch(route('properties.operations.assets.deactivate', [$propertyA, $assetId]), ['reason' => 'Tampering'])
            ->assertNotFound();
        $this->assertDatabaseHas('engineering_assets', ['id' => $assetId, 'property_id' => $propertyB->id, 'name' => 'Water pump', 'is_active' => true]);
        $this->assertSame(1, DB::table('engineering_asset_events')->where('asset_id', $assetId)->count());
    }

    public function test_engineering_asset_event_history_is_immutable(): void
    {
        [$property, $actor] = $this->setupProperty();
        $this->actingAs($actor)->post(route('properties.operations.assets.store', $property), [
            'code' => 'PUMP-1', 'name' => 'Water pump', 'category' => 'Plumbing', 'status' => 'operational',
        ])->assertRedirect();
        $this->expectException(QueryException::class);
        DB::table('engineering_asset_events')->update(['reason' => 'tampered']);
    }

    private function setupProperty(): array
    {
        $property = Property::factory()->create();
        $user = User::factory()->create(['status' => 'active']);
        DB::table('property_user')->insert([
            'property_id' => $property->id, 'user_id' => $user->id, 'role' => 'operations',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$property, $user];
    }
}
