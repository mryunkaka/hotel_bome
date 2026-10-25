<?php

namespace Tests\Feature\Reports;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use App\Services\Reports\ReportingService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RPT-001: Operational and Financial Reporting with tenant boundary
 * and CSV export sanitization.
 */
class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Property $propertyA;

    private Property $propertyB;

    private int $guestA;

    private int $guestB;

    private int $roomTypeA1;

    private int $roomTypeA2;

    private int $roomA1;

    private int $roomA2;

    private int $roomB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyA = Property::factory()->create(['name' => 'Report Hotel Alpha']);
        $this->propertyB = Property::factory()->create(['name' => 'Report Hotel Beta']);

        $this->guestA = DB::table('guests')->insertGetId([
            'property_id' => $this->propertyA->id,
            'display_name' => 'Alpha Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->guestB = DB::table('guests')->insertGetId([
            'property_id' => $this->propertyB->id,
            'display_name' => 'Beta Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeA1 = DB::table('room_types')->insertGetId([
            'property_id' => $this->propertyA->id,
            'name' => 'Standard Deluxe',
            'code' => 'STD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeA2 = DB::table('room_types')->insertGetId([
            'property_id' => $this->propertyA->id,
            'name' => 'Executive Suite',
            'code' => 'EXEC',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomTypeB = DB::table('room_types')->insertGetId([
            'property_id' => $this->propertyB->id,
            'name' => 'Standard Beta',
            'code' => 'STDB',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomA1 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeA1,
            'code' => '101',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomA2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeA2,
            'code' => '201',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomB1 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propertyB->id,
            'room_type_id' => $roomTypeB,
            'code' => 'B101',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeA1,
            'name' => 'Standard Plan',
            'code' => 'STD-P',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeA2,
            'name' => 'Executive Plan',
            'code' => 'EXEC-P',
            'base_price_per_night' => 1000000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propertyB->id,
            'room_type_id' => $roomTypeB,
            'name' => 'Beta Plan',
            'code' => 'BETA-P',
            'base_price_per_night' => 450000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);
    }

    public function test_daily_financial_report_reconciles_charges_and_payments_by_tender(): void
    {
        $today = Carbon::parse('2028-11-01');

        $bookingSvc = new BookingService;
        $resId = $bookingSvc->book(
            $this->propertyA,
            $this->guestA,
            $this->roomA1,
            $this->roomTypeA1,
            $today,
            $today->copy()->addDays(2),
            1
        );

        $folioSvc = new FolioService;
        $folioId = $folioSvc->getOrCreateFolioForReservation($resId, $this->propertyA);

        Carbon::setTestNow($today->copy()->addHours(10));

        $folioSvc->postCharge($folioId, $this->propertyA, [
            'amount' => 550000,
            'description' => 'Room Charge Night 1',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
        $folioSvc->postCharge($folioId, $this->propertyA, [
            'amount' => 150000,
            'description' => 'Laundry Service',
            'item_type' => 'service',
            'actor_id' => 1,
        ]);

        Carbon::setTestNow($today->copy()->addHours(11));

        $folioSvc->postPayment($folioId, $this->propertyA, [
            'amount' => 500000,
            'tender_type' => 'cash',
            'idempotency_key' => 'RPT-P1',
            'actor_id' => 1,
        ]);
        $folioSvc->postPayment($folioId, $this->propertyA, [
            'amount' => 200000,
            'tender_type' => 'card',
            'idempotency_key' => 'RPT-P2',
            'actor_id' => 1,
        ]);

        $rptSvc = new ReportingService;
        $report = $rptSvc->dailyFinancialReport($this->propertyA, $today);

        $this->assertSame(700000, $report['total_charges']);
        $this->assertSame(550000, $report['charges_by_type']['room']);
        $this->assertSame(150000, $report['charges_by_type']['service']);

        $this->assertSame(700000, $report['total_payments']);
        $this->assertSame(500000, $report['payments_by_tender']['cash']);
        $this->assertSame(200000, $report['payments_by_tender']['card']);
        $this->assertSame(0, $report['net_balance']);
    }

    public function test_occupancy_report_calculates_rates_and_type_breakdown(): void
    {
        $today = Carbon::parse('2028-11-05');

        $bookingSvc = new BookingService;
        $resId = $bookingSvc->book(
            $this->propertyA,
            $this->guestA,
            $this->roomA1,
            $this->roomTypeA1,
            $today,
            $today->copy()->addDays(2),
            1
        );

        $staySvc = new StayService;
        $staySvc->checkIn($resId, $this->propertyA, 1);

        $rptSvc = new ReportingService;
        $occ = $rptSvc->occupancyReport($this->propertyA);

        $this->assertSame(2, $occ['total_rooms']);
        $this->assertSame(1, $occ['occupied_rooms']);
        $this->assertSame(1, $occ['available_rooms']);
        $this->assertEquals(50.0, $occ['occupancy_percentage']);

        $this->assertArrayHasKey('Standard Deluxe', $occ['by_room_type']);
        $this->assertSame(1, $occ['by_room_type']['Standard Deluxe']['total']);
        $this->assertSame(1, $occ['by_room_type']['Standard Deluxe']['occupied']);

        $this->assertArrayHasKey('Executive Suite', $occ['by_room_type']);
        $this->assertSame(1, $occ['by_room_type']['Executive Suite']['total']);
        $this->assertSame(0, $occ['by_room_type']['Executive Suite']['occupied']);
    }

    public function test_reporting_strict_property_isolation(): void
    {
        $today = Carbon::parse('2028-11-10');

        $bookingSvc = new BookingService;
        $resA = $bookingSvc->book($this->propertyA, $this->guestA, $this->roomA1, $this->roomTypeA1, $today, $today->copy()->addDays(2), 1);
        $resB = $bookingSvc->book($this->propertyB, $this->guestB, $this->roomB1, DB::table('rooms')->where('id', $this->roomB1)->value('room_type_id'), $today, $today->copy()->addDays(2), 1);

        Carbon::setTestNow($today->copy()->addHours(8));

        $folioSvc = new FolioService;
        $folioA = $folioSvc->getOrCreateFolioForReservation($resA, $this->propertyA);
        $folioB = $folioSvc->getOrCreateFolioForReservation($resB, $this->propertyB);

        $folioSvc->postCharge($folioA, $this->propertyA, ['amount' => 500000, 'description' => 'A charge', 'actor_id' => 1]);
        $folioSvc->postCharge($folioB, $this->propertyB, ['amount' => 999999, 'description' => 'B charge', 'actor_id' => 1]);

        $rptSvc = new ReportingService;
        $reportA = $rptSvc->dailyFinancialReport($this->propertyA, $today);
        $reportB = $rptSvc->dailyFinancialReport($this->propertyB, $today);

        $this->assertSame(500000, $reportA['total_charges']);
        $this->assertSame(999999, $reportB['total_charges']);
    }

    public function test_csv_export_sanitizes_formula_injection(): void
    {
        $today = Carbon::parse('2028-11-15');

        $bookingSvc = new BookingService;
        $resId = $bookingSvc->book($this->propertyA, $this->guestA, $this->roomA1, $this->roomTypeA1, $today, $today->copy()->addDays(2), 1);

        $folioSvc = new FolioService;
        $folioId = $folioSvc->getOrCreateFolioForReservation($resId, $this->propertyA);

        Carbon::setTestNow($today->copy()->addHours(9));

        // Malicious description attempting CSV formula injection
        $folioSvc->postCharge($folioId, $this->propertyA, [
            'amount' => 300000,
            'description' => '=CMD|"/C calc"!A0',
            'item_type' => 'other',
            'actor_id' => 1,
        ]);

        $rptSvc = new ReportingService;
        $csv = $rptSvc->exportDailyFinancialCsv($this->propertyA, $today);

        $this->assertStringContainsString('Date,Item Type,Description,Amount', $csv);
        // Formula character '=' should be prepended with a single quote, and quotes inside escaped as double-quotes
        $this->assertStringContainsString('"\'=CMD|""/C calc""!A0"', $csv);
    }
}
