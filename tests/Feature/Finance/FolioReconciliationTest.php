<?php

namespace Tests\Feature\Finance;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FIN-001-T04: Test rounding, duplicate requests, reversals, snapshots,
 * access isolation and balance reconciliation.
 */
class FolioReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $roomId;

    private int $roomTypeId;

    private int $resId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create(['name' => 'Reconciliation Hotel']);

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Reconciliation Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id,
            'name' => 'Executive Suite',
            'code' => 'EXEC',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'code' => '701',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Exec Plan',
            'code' => 'EP',
            'base_price_per_night' => 1000000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $booking = new BookingService;
        $this->resId = $booking->book(
            $this->property,
            $this->guestId,
            $this->roomId,
            $this->roomTypeId,
            Carbon::parse('2028-10-01'),
            Carbon::parse('2028-10-04'),
            1,
            'phone'
        );
    }

    public function test_reconciliation_over_multiple_charges_and_partial_payments(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        // Multiple charges (room, restaurant, spa)
        $svc->postCharge($folioId, $this->property, [
            'amount' => 1100000,
            'description' => 'Room Night 1 (incl PBJT)',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
        $svc->postCharge($folioId, $this->property, [
            'amount' => 1100000,
            'description' => 'Room Night 2 (incl PBJT)',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
        $svc->postCharge($folioId, $this->property, [
            'amount' => 250000,
            'description' => 'Room Service Dinner',
            'item_type' => 'service',
            'actor_id' => 1,
        ]);

        // Partial payment 1 (deposit at checkin)
        $svc->postPayment($folioId, $this->property, [
            'amount' => 1000000,
            'tender_type' => 'card',
            'idempotency_key' => 'DEP-PARTIAL-1',
            'actor_id' => 1,
        ]);

        $bal1 = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(2450000, $bal1['total_charges']);
        $this->assertSame(1000000, $bal1['total_payments']);
        $this->assertSame(1450000, $bal1['balance']);

        // Partial payment 2
        $svc->postPayment($folioId, $this->property, [
            'amount' => 1450000,
            'tender_type' => 'transfer',
            'idempotency_key' => 'PAY-SETTLE-2',
            'actor_id' => 1,
        ]);

        $bal2 = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(2450000, $bal2['total_charges']);
        $this->assertSame(2450000, $bal2['total_payments']);
        $this->assertSame(0, $bal2['balance']);
    }

    public function test_invoice_snapshot_remains_intact_when_new_charges_added_later(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $svc->postCharge($folioId, $this->property, [
            'amount' => 500000,
            'description' => 'Deposit Charge',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
        $svc->postPayment($folioId, $this->property, [
            'amount' => 500000,
            'tender_type' => 'cash',
            'idempotency_key' => 'INV-KEY-1',
            'actor_id' => 1,
        ]);

        // Issue intermediate invoice
        $invoiceId = $svc->issueInvoice($folioId, $this->property, 1);
        $invoiceBefore = DB::table('invoices')->where('id', $invoiceId)->first();

        // Later: post another charge to the folio
        $svc->postCharge($folioId, $this->property, [
            'amount' => 300000,
            'description' => 'Minibar extra',
            'item_type' => 'service',
            'actor_id' => 1,
        ]);

        // Re-read first invoice; must remain unchanged
        $invoiceAfter = DB::table('invoices')->where('id', $invoiceId)->first();
        $this->assertSame((int) $invoiceBefore->total_charges, (int) $invoiceAfter->total_charges);
        $this->assertSame(500000, (int) $invoiceAfter->total_charges);
        $this->assertSame(0, (int) $invoiceAfter->balance_due);

        // Folio current balance reflects new charge
        $currentBal = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(800000, $currentBal['total_charges']);
        $this->assertSame(300000, $currentBal['balance']);
    }

    public function test_cannot_reverse_already_reversed_charge_twice(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $chargeId = $svc->postCharge($folioId, $this->property, [
            'amount' => 150000,
            'description' => 'Service item',
            'item_type' => 'service',
            'actor_id' => 1,
        ]);

        // Reverse charge
        $svc->reverseCharge($folioId, $this->property, $chargeId, 'Cancel 1', 1);

        // Attempt to reverse the reversal itself (reversal amount is negative)
        $reversalRecord = DB::table('folio_charges')->where('reversal_of_id', $chargeId)->first();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot reverse a non-positive or reversal charge');
        $svc->reverseCharge($folioId, $this->property, (int) $reversalRecord->id, 'Double reverse', 1);
    }
}
