<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\RateQuoteService;
use App\Services\RateSnapshotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RateSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private RateQuoteService $quoteService;

    private RateSnapshotService $snapshotService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quoteService = new RateQuoteService;
        $this->snapshotService = new RateSnapshotService;
    }

    public function test_snapshot_persists_quote_immutably(): void
    {
        $property = Property::factory()->create();

        $plan = RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Standard',
            'code' => 'STD',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $property, null, Carbon::parse('2027-01-10'), Carbon::parse('2027-01-13'),
        );

        $snapshotId = $this->snapshotService->capture($property, $quote);

        $this->assertGreaterThan(0, $snapshotId);

        $snapshot = DB::table('rate_snapshots')->where('id', $snapshotId)->first();
        $this->assertSame($property->id, (int) $snapshot->property_id);
        $this->assertSame($plan->id, (int) $snapshot->rate_plan_id);
        $this->assertSame('Standard', $snapshot->rate_plan_name);
        $this->assertSame('STD', $snapshot->rate_plan_code);
        $this->assertSame(500000, (int) $snapshot->rate_per_night);
        $this->assertSame(3, (int) $snapshot->nights);
        $this->assertSame(1500000, (int) $snapshot->subtotal);
        $this->assertEquals(10.00, (float) $snapshot->tax_percentage);
        $this->assertSame(150000, (int) $snapshot->tax_amount);
        $this->assertSame(1650000, (int) $snapshot->total);
        $this->assertSame('2027-01-10', $snapshot->check_in);
        $this->assertSame('2027-01-13', $snapshot->check_out);

        $breakdown = json_decode($snapshot->nightly_breakdown, true);
        $this->assertCount(3, $breakdown);
        $this->assertSame('2027-01-10', $breakdown[0]['date']);
    }

    public function test_snapshot_survives_master_rate_change(): void
    {
        $property = Property::factory()->create();

        $plan = RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Original Rate',
            'code' => 'ORI',
            'base_price_per_night' => 800000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $property, null, Carbon::parse('2027-03-01'), Carbon::parse('2027-03-03'),
        );

        $snapshotId = $this->snapshotService->capture($property, $quote);

        // Change the master rate plan
        $plan->update([
            'name' => 'Changed Rate',
            'base_price_per_night' => 1200000,
            'tax_percentage' => '5.00',
        ]);

        // Snapshot retains original values
        $snapshot = DB::table('rate_snapshots')->where('id', $snapshotId)->first();
        $this->assertSame('Original Rate', $snapshot->rate_plan_name);
        $this->assertSame(800000, (int) $snapshot->rate_per_night);
        $this->assertEquals(10.00, (float) $snapshot->tax_percentage);
        $this->assertSame(1600000, (int) $snapshot->subtotal);
        $this->assertSame(160000, (int) $snapshot->tax_amount);
        $this->assertSame(1760000, (int) $snapshot->total);
    }

    public function test_snapshot_survives_master_rate_deactivation(): void
    {
        $property = Property::factory()->create();

        $plan = RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Soon Inactive',
            'code' => 'SIN',
            'base_price_per_night' => 600000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $property, null, Carbon::parse('2027-04-01'), Carbon::parse('2027-04-02'),
        );

        $snapshotId = $this->snapshotService->capture($property, $quote);

        // Deactivate the master
        $plan->forceFill(['is_active' => false])->save();

        // Snapshot still exists with original values
        $snapshot = DB::table('rate_snapshots')->where('id', $snapshotId)->first();
        $this->assertNotNull($snapshot);
        $this->assertSame('Soon Inactive', $snapshot->rate_plan_name);
        $this->assertSame(600000, (int) $snapshot->rate_per_night);
    }

    public function test_snapshot_with_null_tax(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'No Tax',
            'code' => 'NTX',
            'base_price_per_night' => 400000,
            'tax_percentage' => null,
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $property, null, Carbon::parse('2027-05-01'), Carbon::parse('2027-05-02'),
        );

        $snapshotId = $this->snapshotService->capture($property, $quote);

        $snapshot = DB::table('rate_snapshots')->where('id', $snapshotId)->first();
        $this->assertNull($snapshot->tax_percentage);
        $this->assertSame(0, (int) $snapshot->tax_amount);
        $this->assertSame(400000, (int) $snapshot->total);
    }

    public function test_snapshot_property_scoped(): void
    {
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $propertyA->id,
            'room_type_id' => null,
            'name' => 'A Rate',
            'code' => 'ART',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $propertyA, null, Carbon::parse('2027-06-01'), Carbon::parse('2027-06-02'),
        );

        $snapshotId = $this->snapshotService->capture($propertyA, $quote);

        // Snapshot belongs to property A
        $snapshot = DB::table('rate_snapshots')->where('id', $snapshotId)->first();
        $this->assertSame($propertyA->id, (int) $snapshot->property_id);

        // No snapshots on property B
        $count = DB::table('rate_snapshots')->where('property_id', $propertyB->id)->count();
        $this->assertSame(0, $count);
    }

    public function test_snapshot_table_rejects_delete(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Immutable',
            'code' => 'IMM',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $property, null, Carbon::parse('2027-07-01'), Carbon::parse('2027-07-02'),
        );

        $snapshotId = $this->snapshotService->capture($property, $quote);

        // Attempt to delete should be rejected by trigger
        try {
            DB::table('rate_snapshots')->where('id', $snapshotId)->delete();
            // If no trigger, still check row exists
        } catch (\Exception) {
            // Expected — trigger prevents delete
        }

        $this->assertNotNull(DB::table('rate_snapshots')->where('id', $snapshotId)->first());
    }

    public function test_snapshot_table_rejects_update(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'No Update',
            'code' => 'NUP',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->quoteService->quote(
            $property, null, Carbon::parse('2027-08-01'), Carbon::parse('2027-08-02'),
        );

        $snapshotId = $this->snapshotService->capture($property, $quote);

        // Attempt to update should be rejected by trigger
        try {
            DB::table('rate_snapshots')
                ->where('id', $snapshotId)
                ->update(['rate_per_night' => 999999]);
        } catch (\Exception) {
            // Expected — trigger prevents update
        }

        $snapshot = DB::table('rate_snapshots')->where('id', $snapshotId)->first();
        $this->assertSame(500000, (int) $snapshot->rate_per_night);
    }
}
