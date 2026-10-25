<?php

namespace Tests\Feature\Finance;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use App\Services\Finance\NightAuditService;
use App\Services\FrontDeskService;
use App\Services\StayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FIN-002-T04: Complete reservation-to-close end-to-end integration test.
 * Exercises: Booking -> Check-in -> Room Move -> Folio Charges -> Cashier Shift
 * -> Payment -> Invoice -> Check-out -> Shift Close -> Night Audit Day Close.
 */
class ReservationToCloseE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_full_reservation_to_night_audit_close_lifecycle(): void
    {
        $property = Property::factory()->create(['name' => 'E2E Grand Hotel']);

        $guestId = DB::table('guests')->insertGetId([
            'property_id' => $property->id,
            'display_name' => 'E2E Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Premier King',
            'code' => 'PK',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room1Id = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '901',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $room2Id = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '902',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'name' => 'Premier Rate',
            'code' => 'PR',
            'base_price_per_night' => 800000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $auditSvc = new NightAuditService;
        $businessDate = $auditSvc->getCurrentBusinessDate($property);

        // 1. Booking
        $bookingSvc = new BookingService;
        $resId = $bookingSvc->book(
            $property,
            $guestId,
            $room1Id,
            $roomTypeId,
            $businessDate,
            $businessDate->copy()->addDays(2),
            1,
            'online'
        );
        $this->assertGreaterThan(0, $resId);

        // 2. Check-in
        $fdSvc = new FrontDeskService;
        $stayId = $fdSvc->performCheckIn($resId, $property, 1);
        $this->assertGreaterThan(0, $stayId);

        // 3. Room move
        $staySvc = new StayService;
        $staySvc->moveRoom($stayId, $property, $room2Id, 1, 'Guest preferred higher floor');
        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame($room2Id, (int) $stay->room_id);

        // 4. Folio charges
        $folioSvc = new FolioService;
        $folioId = $folioSvc->getOrCreateFolioForReservation($resId, $property);
        $folioSvc->postCharge($folioId, $property, [
            'amount' => 880000,
            'description' => 'Room Charge Night 1 (incl PBJT)',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
        $folioSvc->postCharge($folioId, $property, [
            'amount' => 120000,
            'description' => 'Room Service Breakfast',
            'item_type' => 'food_beverage',
            'actor_id' => 1,
        ]);

        $bal = $folioSvc->calculateBalance($folioId, $property);
        $this->assertSame(1000000, $bal['total_charges']);
        $this->assertSame(1000000, $bal['balance']);

        // 5. Open cashier shift
        $cashierId = 10;
        $shiftId = $auditSvc->openShift($property, $cashierId, 1000000);

        // 6. Post payment
        $folioSvc->postPayment($folioId, $property, [
            'amount' => 1000000,
            'tender_type' => 'cash',
            'idempotency_key' => 'E2E-PAY-001',
            'actor_id' => $cashierId,
        ]);

        $balSettled = $folioSvc->calculateBalance($folioId, $property);
        $this->assertSame(0, $balSettled['balance']);

        // 7. Issue invoice
        $invoiceId = $folioSvc->issueInvoice($folioId, $property, $cashierId);
        $this->assertGreaterThan(0, $invoiceId);

        // 8. Check-out
        $fdSvc->performCheckOut($stayId, $property, 1);
        $stayAfter = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('checked_out', $stayAfter->status);

        // 9. Close cashier shift with exact reconciliation
        $shiftResult = $auditSvc->closeShift($shiftId, $property, 2000000, $cashierId);
        $this->assertSame('closed', $shiftResult['status']);
        $this->assertSame(0, $shiftResult['variance']); // 1M opening + 1M cash payment = 2M counted

        // 10. Run Night Audit Day Close
        $snapshotId = $auditSvc->runNightAuditClose($property, 1);
        $this->assertGreaterThan(0, $snapshotId);

        // Verify closing snapshot metrics
        $snap = DB::table('day_closing_snapshots')->where('id', $snapshotId)->first();
        $this->assertSame($businessDate->toDateString(), $snap->business_date);
        $this->assertSame(1000000, (int) $snap->total_charges);
        $this->assertSame(1000000, (int) $snap->total_payments);
        $this->assertSame(0, (int) $snap->active_stays_count);

        // Verify business date advanced
        $nextDate = $auditSvc->getCurrentBusinessDate($property);
        $this->assertSame($businessDate->copy()->addDay()->toDateString(), $nextDate->toDateString());
    }
}
