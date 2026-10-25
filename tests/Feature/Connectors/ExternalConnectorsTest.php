<?php

namespace Tests\Feature\Connectors;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Connectors\NullOtaAdapter;
use App\Services\Connectors\PaymentWebhookHandler;
use App\Services\Connectors\SimulatorPaymentGateway;
use App\Services\Finance\FolioService;
use App\Services\StayService;
use App\Services\Sync\OutboxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * EXT-001: External OTA Adapter, Outage-Safe Payment Gateway,
 * and Idempotent Payment Webhook Reconciler.
 */
class ExternalConnectorsTest extends TestCase
{
    use RefreshDatabase;

    private Property $propertyA;

    private Property $propertyB;

    private int $guestA;

    private int $roomId;

    private int $roomTypeId;

    private int $resId;

    private int $folioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyA = Property::factory()->create(['name' => 'Connector Hotel Alpha']);
        $this->propertyB = Property::factory()->create(['name' => 'Connector Hotel Beta']);

        $this->guestA = DB::table('guests')->insertGetId([
            'property_id' => $this->propertyA->id,
            'display_name' => 'Connector Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->propertyA->id,
            'name' => 'Standard Deluxe',
            'code' => 'STD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeId,
            'code' => '301',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Standard Plan',
            'code' => 'STDP',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $booking = new BookingService;
        $this->resId = $booking->book(
            $this->propertyA,
            $this->guestA,
            $this->roomId,
            $this->roomTypeId,
            Carbon::parse('2028-12-20'),
            Carbon::parse('2028-12-22'),
            1
        );

        $staySvc = new StayService;
        $staySvc->checkIn($this->resId, $this->propertyA, 1);

        $folioSvc = new FolioService;
        $this->folioId = $folioSvc->getOrCreateFolioForReservation($this->resId, $this->propertyA);
        $folioSvc->postCharge($this->folioId, $this->propertyA, [
            'amount' => 1100000,
            'description' => 'Room Charge 2 Nights',
            'actor_id' => 1,
        ]);
    }

    public function test_null_ota_adapter_is_safe_disabled_fallback(): void
    {
        $ota = new NullOtaAdapter;

        $res = $ota->syncInventory($this->propertyA, ['room_type_id' => $this->roomTypeId, 'available' => 5]);
        $this->assertSame('disabled', $res['status']);
        $this->assertFalse($res['is_active']);

        $bookings = $ota->importBookings($this->propertyA);
        $this->assertSame('disabled', $bookings['status']);
        $this->assertEmpty($bookings['bookings']);
    }

    public function test_simulator_payment_gateway_online_charge_succeeds(): void
    {
        $gw = new SimulatorPaymentGateway(isWanDown: false);

        $result = $gw->charge($this->propertyA, 500000, 'EXT-PAY-KEY-1', ['method' => 'card']);

        $this->assertSame('succeeded', $result['status']);
        $this->assertNotEmpty($result['transaction_ref']);
        $this->assertSame(500000, $result['amount']);
    }

    public function test_simulator_payment_gateway_wan_down_queues_outbox_safely(): void
    {
        $outboxSvc = new OutboxService;
        $gw = new SimulatorPaymentGateway(isWanDown: true, outboxService: $outboxSvc);

        // In WAN-down mode, payment charge is queued to local outbox without crashing core
        $result = $gw->charge($this->propertyA, 500000, 'EXT-OFFLINE-KEY-2', ['method' => 'card']);

        $this->assertSame('offline_queued', $result['status']);
        $this->assertNotNull($result['outbox_id']);

        // Verify outbox entry created
        $outbox = DB::table('sync_outbox')->where('id', $result['outbox_id'])->first();
        $this->assertSame('queued', $outbox->status);
        $this->assertSame('payment.charge_requested', $outbox->topic);
    }

    public function test_webhook_handler_credits_folio_atomically(): void
    {
        $handler = new PaymentWebhookHandler;

        $payload = [
            'event' => 'payment.captured',
            'property_id' => $this->propertyA->id,
            'folio_id' => $this->folioId,
            'amount' => 600000,
            'idempotency_key' => 'WH-TX-2028-001',
            'tender_type' => 'card',
        ];

        $res = $handler->handleWebhook($this->propertyA, $payload);
        $this->assertSame('processed', $res['status']);
        $this->assertGreaterThan(0, $res['payment_id']);

        // Folio balance updated
        $folioSvc = new FolioService;
        $bal = $folioSvc->calculateBalance($this->folioId, $this->propertyA);
        $this->assertSame(1100000, $bal['total_charges']);
        $this->assertSame(600000, $bal['total_payments']);
        $this->assertSame(500000, $bal['balance']);
    }

    public function test_webhook_handler_idempotent_replay_does_not_double_post(): void
    {
        $handler = new PaymentWebhookHandler;

        $payload = [
            'event' => 'payment.captured',
            'property_id' => $this->propertyA->id,
            'folio_id' => $this->folioId,
            'amount' => 500000,
            'idempotency_key' => 'WH-IDEMPOTENT-002',
            'tender_type' => 'transfer',
        ];

        // First delivery
        $res1 = $handler->handleWebhook($this->propertyA, $payload);
        $this->assertSame('processed', $res1['status']);

        // Replayed delivery with exact same idempotency_key
        $res2 = $handler->handleWebhook($this->propertyA, $payload);
        $this->assertSame('reconciled_duplicate', $res2['status']);
        $this->assertSame($res1['payment_id'], $res2['payment_id']);

        // Folio balance reflects exactly one payment (not doubled)
        $folioSvc = new FolioService;
        $bal = $folioSvc->calculateBalance($this->folioId, $this->propertyA);
        $this->assertSame(500000, $bal['total_payments']);
    }

    public function test_webhook_handler_cross_property_rejection(): void
    {
        $handler = new PaymentWebhookHandler;

        // Webhook for property B attempting to credit property A's folio
        $payload = [
            'event' => 'payment.captured',
            'property_id' => $this->propertyB->id,
            'folio_id' => $this->folioId,
            'amount' => 100000,
            'idempotency_key' => 'WH-CROSS-PROP',
            'tender_type' => 'card',
        ];

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Folio not found in this property');
        $handler->handleWebhook($this->propertyB, $payload);
    }
}
