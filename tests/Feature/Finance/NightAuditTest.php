<?php

namespace Tests\Feature\Finance;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use App\Services\Finance\NightAuditService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FIN-002-T01..T03: Business date, cashier shift, night audit close/reopen.
 */
class NightAuditTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $roomId;

    private int $roomTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create(['name' => 'Audit Hotel']);

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Audit Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id,
            'name' => 'Superior',
            'code' => 'SUP',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'code' => '801',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Superior Plan',
            'code' => 'SP',
            'base_price_per_night' => 400000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);
    }

    public function test_get_current_business_date_initializes_if_absent(): void
    {
        $svc = new NightAuditService;
        $date = $svc->getCurrentBusinessDate($this->property);

        $this->assertInstanceOf(Carbon::class, $date);

        $row = DB::table('business_dates')
            ->where('property_id', $this->property->id)
            ->where('status', 'open')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($date->toDateString(), $row->business_date);
    }

    public function test_cashier_shift_lifecycle_open_and_close_with_variance(): void
    {
        $svc = new NightAuditService;
        $shiftId = $svc->openShift($this->property, 1, 500000);

        $this->assertGreaterThan(0, $shiftId);

        $shift = DB::table('cashier_shifts')->where('id', $shiftId)->first();
        $this->assertSame('open', $shift->status);
        $this->assertSame(500000, (int) $shift->opening_cash);

        // Close shift with counted cash
        $result = $svc->closeShift($shiftId, $this->property, 520000, 1);
        $this->assertSame('closed', $result['status']);
        $this->assertSame(520000, $result['closing_cash_counted']);
        $this->assertSame(20000, $result['variance']); // surplus 20k

        $shiftClosed = DB::table('cashier_shifts')->where('id', $shiftId)->first();
        $this->assertSame('closed', $shiftClosed->status);
        $this->assertNotNull($shiftClosed->closed_at);
    }

    public function test_night_audit_close_rejected_if_shifts_still_open(): void
    {
        $svc = new NightAuditService;
        $svc->getCurrentBusinessDate($this->property);

        // Open a shift and leave it open
        $svc->openShift($this->property, 1, 500000);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('open cashier shifts');
        $svc->runNightAuditClose($this->property, 1);
    }

    public function test_night_audit_close_creates_immutable_snapshot_and_advances_date(): void
    {
        $svc = new NightAuditService;
        $currentDate = $svc->getCurrentBusinessDate($this->property);

        // Have a booking and checkin
        $booking = new BookingService;
        $resId = $booking->book(
            $this->property,
            $this->guestId,
            $this->roomId,
            $this->roomTypeId,
            $currentDate,
            $currentDate->copy()->addDays(2),
            1,
            'walk_in'
        );
        $stay = new StayService;
        $stay->checkIn($resId, $this->property, 1);

        // Post charge and payment
        $folioSvc = new FolioService;
        $folioId = $folioSvc->getOrCreateFolioForReservation($resId, $this->property);
        $folioSvc->postCharge($folioId, $this->property, [
            'amount' => 440000,
            'description' => 'Room Charge',
            'actor_id' => 1,
        ]);
        $folioSvc->postPayment($folioId, $this->property, [
            'amount' => 440000,
            'tender_type' => 'cash',
            'idempotency_key' => 'AUD-CASH-1',
            'actor_id' => 1,
        ]);

        // Shift opened and closed properly
        $shiftId = $svc->openShift($this->property, 1, 500000);
        $svc->closeShift($shiftId, $this->property, 940000, 1); // 500k base + 440k payment

        // Run Night Audit Close
        $snapshotId = $svc->runNightAuditClose($this->property, 1);
        $this->assertGreaterThan(0, $snapshotId);

        $snapshot = DB::table('day_closing_snapshots')->where('id', $snapshotId)->first();
        $this->assertSame($currentDate->toDateString(), $snapshot->business_date);
        $this->assertSame(440000, (int) $snapshot->total_charges);
        $this->assertSame(440000, (int) $snapshot->total_payments);
        $this->assertSame(1, (int) $snapshot->active_stays_count);

        // Previous date is marked closed
        $oldDateRow = DB::table('business_dates')
            ->where('property_id', $this->property->id)
            ->where('business_date', $currentDate->toDateString())
            ->first();
        $this->assertSame('closed', $oldDateRow->status);

        // New date is open and advanced by 1 day
        $newDate = $svc->getCurrentBusinessDate($this->property);
        $this->assertSame($currentDate->copy()->addDay()->toDateString(), $newDate->toDateString());

        // Snapshot cannot be mutated (trigger protection)
        $this->expectException(QueryException::class);
        DB::table('day_closing_snapshots')->where('id', $snapshotId)->update(['total_charges' => 0]);
    }

    public function test_reopen_business_date_records_audit_trail(): void
    {
        $svc = new NightAuditService;
        $origDate = $svc->getCurrentBusinessDate($this->property);

        // Run close
        $svc->runNightAuditClose($this->property, 1);

        // Supervisor reopens the closed date
        $svc->reopenBusinessDate($this->property, $origDate->toDateString(), 'Emergency late posting correction', 1);

        $reopenRow = DB::table('business_date_reopens')
            ->where('property_id', $this->property->id)
            ->where('business_date', $origDate->toDateString())
            ->first();

        $this->assertNotNull($reopenRow);
        $this->assertSame('Emergency late posting correction', $reopenRow->reason);
        $this->assertSame(1, (int) $reopenRow->reopened_by);

        // Reopen event cannot be updated or deleted
        $this->expectException(QueryException::class);
        DB::table('business_date_reopens')->where('id', $reopenRow->id)->delete();
    }
}
