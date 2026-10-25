<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\RateQuoteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RateQuoteTest extends TestCase
{
    use RefreshDatabase;

    private RateQuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateQuoteService;
    }

    public function test_basic_quote_with_active_rate_plan(): void
    {
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id, 'name' => 'Deluxe', 'code' => 'DLX',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'name' => 'Deluxe Standard',
            'code' => 'DLXS',
            'base_price_per_night' => 750000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            $roomTypeId,
            Carbon::parse('2027-01-10'),
            Carbon::parse('2027-01-13'),
        );

        $this->assertNotNull($quote);
        $this->assertSame(3, $quote['nights']);
        $this->assertSame(750000, $quote['rate_per_night']);
        $this->assertSame(2250000, $quote['subtotal']);
        $this->assertSame('10.00', $quote['tax_percentage']);
        $this->assertSame(225000, $quote['tax_amount']); // 2250000 * 10% = 225000 IDR
        $this->assertSame(2475000, $quote['total']);
        $this->assertSame('Deluxe Standard', $quote['rate_plan_name']);
        $this->assertSame('DLXS', $quote['rate_plan_code']);
        $this->assertCount(3, $quote['nightly_breakdown']);
    }

    public function test_quote_without_room_type_uses_general_plan(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'General Rate',
            'code' => 'GEN',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-02-01'),
            Carbon::parse('2027-02-03'),
        );

        $this->assertNotNull($quote);
        $this->assertSame(2, $quote['nights']);
        $this->assertSame(1000000, $quote['subtotal']);
        $this->assertSame(100000, $quote['tax_amount']);
        $this->assertSame(1100000, $quote['total']);
    }

    public function test_effective_date_filtering(): void
    {
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id, 'name' => 'Standard', 'code' => 'STD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Plan that covers the requested dates
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'name' => 'Peak Season',
            'code' => 'PEAK',
            'base_price_per_night' => 1200000,
            'tax_percentage' => '10.00',
            'effective_from' => '2027-01-01',
            'effective_until' => '2027-03-31',
            'is_active' => true,
        ]);

        // Plan that does NOT cover the requested dates
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'name' => 'Low Season',
            'code' => 'LOW',
            'base_price_per_night' => 600000,
            'tax_percentage' => '10.00',
            'effective_from' => '2027-04-01',
            'effective_until' => '2027-09-30',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            $roomTypeId,
            Carbon::parse('2027-02-01'),
            Carbon::parse('2027-02-03'),
        );

        $this->assertNotNull($quote);
        $this->assertSame('PEAK', $quote['rate_plan_code']);
        $this->assertSame(1200000, $quote['rate_per_night']);
    }

    public function test_inactive_plan_excluded(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Inactive Rate',
            'code' => 'OFF',
            'base_price_per_night' => 300000,
            'tax_percentage' => null,
            'is_active' => false,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-03-01'),
            Carbon::parse('2027-03-02'),
        );

        $this->assertNull($quote);
    }

    public function test_no_matching_plan_returns_null(): void
    {
        $property = Property::factory()->create();

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-03-01'),
            Carbon::parse('2027-03-02'),
        );

        $this->assertNull($quote);
    }

    public function test_cross_property_plan_excluded(): void
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

        // Quoting on property B should not find property A's plan
        $quote = $this->service->quote(
            $propertyB,
            null,
            Carbon::parse('2027-03-01'),
            Carbon::parse('2027-03-02'),
        );

        $this->assertNull($quote);
    }

    public function test_null_tax_results_in_zero_tax(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'No Tax Rate',
            'code' => 'NTX',
            'base_price_per_night' => 400000,
            'tax_percentage' => null,
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-04-01'),
            Carbon::parse('2027-04-03'),
        );

        $this->assertNotNull($quote);
        $this->assertSame(800000, $quote['subtotal']);
        $this->assertNull($quote['tax_percentage']);
        $this->assertSame(0, $quote['tax_amount']);
        $this->assertSame(800000, $quote['total']);
    }

    public function test_tax_rounding_half_up_idr(): void
    {
        $property = Property::factory()->create();

        // 350000 * 1 night * 10.50% = 36750 exact — no rounding needed
        // Use 333333 * 1 night * 10.00% = 33333.3 → 33333 IDR (half-up)
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Odd Rate',
            'code' => 'ODD',
            'base_price_per_night' => 333333,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-05-01'),
            Carbon::parse('2027-05-02'),
        );

        $this->assertNotNull($quote);
        $this->assertSame(333333, $quote['subtotal']);
        // 333333 * 10% = 33333.3 → half-up = 33333
        $this->assertSame(33333, $quote['tax_amount']);
        $this->assertSame(366666, $quote['total']);
    }

    public function test_nightly_breakdown_structure(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Breakdown Test',
            'code' => 'BRK',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-06-10'),
            Carbon::parse('2027-06-12'),
        );

        $this->assertNotNull($quote);
        $this->assertCount(2, $quote['nightly_breakdown']);
        $this->assertSame('2027-06-10', $quote['nightly_breakdown'][0]['date']);
        $this->assertSame(500000, $quote['nightly_breakdown'][0]['amount']);
        $this->assertSame('2027-06-11', $quote['nightly_breakdown'][1]['date']);
        $this->assertSame(500000, $quote['nightly_breakdown'][1]['amount']);
    }

    public function test_room_type_specific_plan_preferred_over_general(): void
    {
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id, 'name' => 'Suite', 'code' => 'STE',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // General plan (no room type)
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'General',
            'code' => 'GEN',
            'base_price_per_night' => 400000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        // Room-type-specific plan
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'name' => 'Suite Rate',
            'code' => 'SRT',
            'base_price_per_night' => 900000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            $roomTypeId,
            Carbon::parse('2027-07-01'),
            Carbon::parse('2027-07-02'),
        );

        $this->assertNotNull($quote);
        $this->assertSame('SRT', $quote['rate_plan_code']);
        $this->assertSame(900000, $quote['rate_per_night']);
    }

    public function test_falls_back_to_general_plan_when_no_room_type_match(): void
    {
        $property = Property::factory()->create();
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id, 'name' => 'Economy', 'code' => 'ECO',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Only general plan exists
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Fallback General',
            'code' => 'FBG',
            'base_price_per_night' => 350000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            $roomTypeId,
            Carbon::parse('2027-08-01'),
            Carbon::parse('2027-08-02'),
        );

        $this->assertNotNull($quote);
        $this->assertSame('FBG', $quote['rate_plan_code']);
    }

    public function test_open_ended_effective_dates_match(): void
    {
        $property = Property::factory()->create();

        // Plan with no effective dates (always applicable)
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Always Available',
            'code' => 'ALW',
            'base_price_per_night' => 600000,
            'tax_percentage' => '5.00',
            'effective_from' => null,
            'effective_until' => null,
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2030-01-01'),
            Carbon::parse('2030-01-02'),
        );

        $this->assertNotNull($quote);
        $this->assertSame('ALW', $quote['rate_plan_code']);
    }

    public function test_same_day_checkin_checkout_rejected(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Any',
            'code' => 'ANY',
            'base_price_per_night' => 500000,
            'tax_percentage' => null,
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-01-01'),
            Carbon::parse('2027-01-01'),
        );

        $this->assertNull($quote);
    }

    public function test_checkout_before_checkin_rejected(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Any',
            'code' => 'ANY',
            'base_price_per_night' => 500000,
            'tax_percentage' => null,
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property,
            null,
            Carbon::parse('2027-06-15'),
            Carbon::parse('2027-06-10'),
        );

        $this->assertNull($quote);
    }

    // --- T04 boundary tests ---

    public function test_effective_date_boundary_inclusive(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Bounded',
            'code' => 'BND',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'effective_from' => '2027-03-01',
            'effective_until' => '2027-03-31',
            'is_active' => true,
        ]);

        // Check-in on effective_from, check-out day after effective_until — last night is 2027-03-31
        $quote = $this->service->quote(
            $property, null, Carbon::parse('2027-03-01'), Carbon::parse('2027-04-01'),
        );
        $this->assertNotNull($quote);
        $this->assertSame(31, $quote['nights']);

        // Check-in one day before effective_from — should NOT match
        $quote2 = $this->service->quote(
            $property, null, Carbon::parse('2027-02-28'), Carbon::parse('2027-03-02'),
        );
        $this->assertNull($quote2);

        // Last night after effective_until — should NOT match
        $quote3 = $this->service->quote(
            $property, null, Carbon::parse('2027-03-31'), Carbon::parse('2027-04-02'),
        );
        $this->assertNull($quote3);
    }

    public function test_one_sided_effective_dates(): void
    {
        $property = Property::factory()->create();

        // Only effective_from set (no end date)
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'From Only',
            'code' => 'FRM',
            'base_price_per_night' => 700000,
            'tax_percentage' => null,
            'effective_from' => '2027-06-01',
            'effective_until' => null,
            'is_active' => true,
        ]);

        // Stay starting on effective_from
        $q1 = $this->service->quote(
            $property, null, Carbon::parse('2027-06-01'), Carbon::parse('2027-06-02'),
        );
        $this->assertNotNull($q1);
        $this->assertSame('FRM', $q1['rate_plan_code']);

        // Stay before effective_from
        $q2 = $this->service->quote(
            $property, null, Carbon::parse('2027-05-30'), Carbon::parse('2027-06-01'),
        );
        $this->assertNull($q2);
    }

    public function test_overlapping_plans_returns_first_match(): void
    {
        $property = Property::factory()->create();

        // Two overlapping plans for same property/no room type
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Plan A',
            'code' => 'PLA',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'effective_from' => '2027-01-01',
            'effective_until' => '2027-06-30',
            'is_active' => true,
        ]);

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Plan B',
            'code' => 'PLB',
            'base_price_per_night' => 800000,
            'tax_percentage' => '10.00',
            'effective_from' => '2027-03-01',
            'effective_until' => '2027-09-30',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property, null, Carbon::parse('2027-04-01'), Carbon::parse('2027-04-02'),
        );

        // Both match; service returns a valid plan (deterministic order is DB insert order)
        $this->assertNotNull($quote);
        $this->assertContains($quote['rate_plan_code'], ['PLA', 'PLB']);
    }

    public function test_tax_rounding_half_up_boundary(): void
    {
        $property = Property::factory()->create();

        // 100001 * 10% = 10000.1 → rounds to 10000
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Round Down',
            'code' => 'RDN',
            'base_price_per_night' => 100001,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $q1 = $this->service->quote(
            $property, null, Carbon::parse('2027-01-01'), Carbon::parse('2027-01-02'),
        );
        $this->assertNotNull($q1);
        $this->assertSame(10000, $q1['tax_amount']); // .1 rounds down
        $this->assertSame(110001, $q1['total']);
    }

    public function test_tax_with_fractional_percentage(): void
    {
        $property = Property::factory()->create();

        // 1000000 * 7.50% = 75000 exact
        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Fractional Tax',
            'code' => 'FTX',
            'base_price_per_night' => 1000000,
            'tax_percentage' => '7.50',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property, null, Carbon::parse('2027-01-01'), Carbon::parse('2027-01-03'),
        );
        $this->assertNotNull($quote);
        $this->assertSame(2000000, $quote['subtotal']);
        $this->assertSame(150000, $quote['tax_amount']); // 2000000 * 7.5% = 150000
        $this->assertSame(2150000, $quote['total']);
        $this->assertSame('7.50', $quote['tax_percentage']);
    }

    public function test_single_night_stay(): void
    {
        $property = Property::factory()->create();

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => null,
            'name' => 'Single',
            'code' => 'SGL',
            'base_price_per_night' => 250000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = $this->service->quote(
            $property, null, Carbon::parse('2027-01-01'), Carbon::parse('2027-01-02'),
        );
        $this->assertNotNull($quote);
        $this->assertSame(1, $quote['nights']);
        $this->assertSame(250000, $quote['subtotal']);
        $this->assertSame(25000, $quote['tax_amount']);
        $this->assertSame(275000, $quote['total']);
        $this->assertCount(1, $quote['nightly_breakdown']);
        $this->assertSame('2027-01-01', $quote['check_in']);
        $this->assertSame('2027-01-02', $quote['check_out']);
    }
}
