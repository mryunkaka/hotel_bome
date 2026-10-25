<?php

namespace Tests\Feature\Finance;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FolioLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $roomId;

    private int $roomTypeId;

    private int $resId;

    private int $stayId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create(['name' => 'Finance Test Hotel']);

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Finance Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id,
            'name' => 'Deluxe Suite',
            'code' => 'DLX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'code' => '601',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Deluxe Daily',
            'code' => 'DD',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $booking = new BookingService;
        $this->resId = $booking->book(
            $this->property,
            $this->guestId,
            $this->roomId,
            $this->roomTypeId,
            Carbon::parse('2028-09-01'),
            Carbon::parse('2028-09-03'),
            1,
            'online'
        );

        $staySvc = new StayService;
        $this->stayId = $staySvc->checkIn($this->resId, $this->property, 1);
    }

    public function test_folio_created_for_reservation_or_stay(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $this->assertGreaterThan(0, $folioId);

        $folio = DB::table('folios')->where('id', $folioId)->first();
        $this->assertSame($this->property->id, (int) $folio->property_id);
        $this->assertSame($this->resId, (int) $folio->reservation_id);
        $this->assertSame('open', $folio->status);

        // Calling again returns same folio (idempotent getOrCreate)
        $sameFolioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);
        $this->assertSame($folioId, $sameFolioId);
    }

    public function test_post_charge_updates_balance(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $chargeId = $svc->postCharge($folioId, $this->property, [
            'amount' => 550000,
            'description' => 'Room Charge Night 1',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);

        $this->assertGreaterThan(0, $chargeId);

        $balance = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(550000, $balance['total_charges']);
        $this->assertSame(0, $balance['total_payments']);
        $this->assertSame(550000, $balance['balance']);
    }

    public function test_post_payment_updates_balance_and_enforces_idempotency(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $svc->postCharge($folioId, $this->property, [
            'amount' => 550000,
            'description' => 'Room Charge',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);

        $idempotencyKey = 'PAY-REQ-20280901-0001';

        $payId1 = $svc->postPayment($folioId, $this->property, [
            'amount' => 550000,
            'tender_type' => 'cash',
            'idempotency_key' => $idempotencyKey,
            'actor_id' => 1,
        ]);

        $this->assertGreaterThan(0, $payId1);

        $balance = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(550000, $balance['total_charges']);
        $this->assertSame(550000, $balance['total_payments']);
        $this->assertSame(0, $balance['balance']);

        // Duplicate payment with same idempotency key returns existing payment ID without doubling
        $payId2 = $svc->postPayment($folioId, $this->property, [
            'amount' => 550000,
            'tender_type' => 'cash',
            'idempotency_key' => $idempotencyKey,
            'actor_id' => 1,
        ]);

        $this->assertSame($payId1, $payId2);

        $balanceAfter = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(550000, $balanceAfter['total_payments']);
        $this->assertSame(0, $balanceAfter['balance']);
    }

    public function test_posted_charge_immutable_cannot_be_updated_or_deleted(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $chargeId = $svc->postCharge($folioId, $this->property, [
            'amount' => 100000,
            'description' => 'Laundry',
            'item_type' => 'service',
            'actor_id' => 1,
        ]);

        $this->expectException(QueryException::class);
        DB::table('folio_charges')->where('id', $chargeId)->update(['amount' => 50000]);
    }

    public function test_charge_correction_via_reversal(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $chargeId = $svc->postCharge($folioId, $this->property, [
            'amount' => 100000,
            'description' => 'Minibar mistaken charge',
            'item_type' => 'service',
            'actor_id' => 1,
        ]);

        // Reversal of mistaken charge
        $reversalId = $svc->reverseCharge($folioId, $this->property, $chargeId, 'Guest did not consume minibar', 1);

        $this->assertGreaterThan(0, $reversalId);

        $balance = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(0, $balance['total_charges']);
        $this->assertSame(0, $balance['balance']);

        $revRow = DB::table('folio_charges')->where('id', $reversalId)->first();
        $this->assertSame(-100000, (int) $revRow->amount);
        $this->assertSame($chargeId, (int) $revRow->reversal_of_id);
    }

    public function test_refund_payment_via_compensating_record(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $payId = $svc->postPayment($folioId, $this->property, [
            'amount' => 200000,
            'tender_type' => 'cash',
            'idempotency_key' => 'DEP-001',
            'actor_id' => 1,
        ]);

        $refundId = $svc->refundPayment($folioId, $this->property, $payId, 'Overpayment deposit return', 1);
        $this->assertGreaterThan(0, $refundId);

        $balance = $svc->calculateBalance($folioId, $this->property);
        $this->assertSame(0, $balance['total_payments']);

        $refRow = DB::table('folio_payments')->where('id', $refundId)->first();
        $this->assertSame(-200000, (int) $refRow->amount);
        $this->assertSame($payId, (int) $refRow->refund_of_id);
    }

    public function test_issue_invoice_creates_immutable_snapshot(): void
    {
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $svc->postCharge($folioId, $this->property, [
            'amount' => 500000,
            'description' => 'Room Charge',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
        $svc->postPayment($folioId, $this->property, [
            'amount' => 500000,
            'tender_type' => 'transfer',
            'idempotency_key' => 'INV-PAY-01',
            'actor_id' => 1,
        ]);

        $invoiceId = $svc->issueInvoice($folioId, $this->property, 1);
        $this->assertGreaterThan(0, $invoiceId);

        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        $this->assertSame($this->property->id, (int) $invoice->property_id);
        $this->assertSame($folioId, (int) $invoice->folio_id);
        $this->assertSame(500000, (int) $invoice->total_charges);
        $this->assertSame(500000, (int) $invoice->total_payments);
        $this->assertSame(0, (int) $invoice->balance_due);
        $this->assertNotEmpty($invoice->invoice_number);

        $items = json_decode($invoice->line_items_snapshot, true);
        $this->assertCount(1, $items);
        $this->assertSame('Room Charge', $items[0]['description']);

        // Check trigger prevents invoice update
        $this->expectException(QueryException::class);
        DB::table('invoices')->where('id', $invoiceId)->update(['total_charges' => 999999]);
    }

    public function test_cross_property_folio_operations_rejected(): void
    {
        $otherProperty = Property::factory()->create();
        $svc = new FolioService;
        $folioId = $svc->getOrCreateFolioForReservation($this->resId, $this->property);

        $this->expectException(\DomainException::class);
        $svc->postCharge($folioId, $otherProperty, [
            'amount' => 10000,
            'description' => 'Cross property attempt',
            'item_type' => 'room',
            'actor_id' => 1,
        ]);
    }
}
