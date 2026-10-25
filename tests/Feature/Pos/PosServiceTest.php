<?php

namespace Tests\Feature\Pos;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use App\Services\Pos\PosService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POS-001: Local POS Outlets, Orders, Direct Payments, and Idempotent Room Charges.
 */
class PosServiceTest extends TestCase
{
    use RefreshDatabase;

    private Property $propertyA;

    private Property $propertyB;

    private int $guestA;

    private int $roomId;

    private int $roomTypeId;

    private int $resId;

    private int $stayId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyA = Property::factory()->create(['name' => 'POS Hotel Alpha']);
        $this->propertyB = Property::factory()->create(['name' => 'POS Hotel Beta']);

        $this->guestA = DB::table('guests')->insertGetId([
            'property_id' => $this->propertyA->id,
            'display_name' => 'POS Guest',
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
            'code' => '201',
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
            Carbon::parse('2028-12-10'),
            Carbon::parse('2028-12-12'),
            1
        );

        $staySvc = new StayService;
        $this->stayId = $staySvc->checkIn($this->resId, $this->propertyA, 1);
    }

    public function test_outlet_creation_and_order_lifecycle(): void
    {
        $svc = new PosService;

        $outletId = $svc->createOutlet($this->propertyA, [
            'code' => 'RESTO-01',
            'name' => 'Alpha Restaurant & Lounge',
            'type' => 'restaurant',
        ]);
        $this->assertGreaterThan(0, $outletId);

        // Open order
        $orderId = $svc->openOrder($this->propertyA, $outletId, [
            'table_number' => 'T-05',
            'actor_id' => 1,
        ]);
        $this->assertGreaterThan(0, $orderId);

        // Add items
        $svc->addItemToOrder($this->propertyA, $orderId, [
            'item_name' => 'Nasi Goreng Special',
            'quantity' => 2,
            'unit_price' => 65000,
        ]);
        $svc->addItemToOrder($this->propertyA, $orderId, [
            'item_name' => 'Es Teh Manis',
            'quantity' => 2,
            'unit_price' => 15000,
        ]);

        $order = $svc->getOrder($this->propertyA, $orderId);
        $this->assertSame('open', $order['status']);
        $this->assertSame(160000, $order['total_amount']); // (2*65000) + (2*15000) = 130000 + 30000 = 160000
        $this->assertCount(2, $order['items']);
    }

    public function test_settle_order_via_direct_payment(): void
    {
        $svc = new PosService;
        $outletId = $svc->createOutlet($this->propertyA, [
            'code' => 'CAFE-01',
            'name' => 'Alpha Coffee Shop',
            'type' => 'cafe',
        ]);

        $orderId = $svc->openOrder($this->propertyA, $outletId, [
            'table_number' => 'C-01',
            'actor_id' => 1,
        ]);

        $svc->addItemToOrder($this->propertyA, $orderId, [
            'item_name' => 'Cappuccino',
            'quantity' => 1,
            'unit_price' => 45000,
        ]);

        $result = $svc->settleDirectPayment($this->propertyA, $orderId, 'cash', 1);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('cash', $result['tender_type']);

        $order = $svc->getOrder($this->propertyA, $orderId);
        $this->assertSame('paid', $order['status']);
    }

    public function test_settle_order_charge_to_room_atomically_updates_folio(): void
    {
        $svc = new PosService;
        $outletId = $svc->createOutlet($this->propertyA, [
            'code' => 'ROOM-SVC',
            'name' => 'Room Service Dining',
            'type' => 'room_service',
        ]);

        $orderId = $svc->openOrder($this->propertyA, $outletId, [
            'room_id' => $this->roomId,
            'actor_id' => 1,
        ]);

        $svc->addItemToOrder($this->propertyA, $orderId, [
            'item_name' => 'Club Sandwich',
            'quantity' => 1,
            'unit_price' => 85000,
        ]);

        $idempotencyKey = 'POS-ORD-ROOM-001';

        $settleResult = $svc->settleRoomCharge(
            $this->propertyA,
            $orderId,
            $this->roomId,
            $idempotencyKey,
            1
        );

        $this->assertSame('posted_to_room', $settleResult['status']);
        $this->assertGreaterThan(0, $settleResult['folio_charge_id']);

        // Verify folio charge
        $folioSvc = new FolioService;
        $folioId = $folioSvc->getOrCreateFolioForReservation($this->resId, $this->propertyA);
        $balance = $folioSvc->calculateBalance($folioId, $this->propertyA);
        $this->assertSame(85000, $balance['total_charges']);
        $this->assertSame(85000, $balance['balance']);

        // Idempotency: retry with same idempotency key returns same result without doubling charge
        $retryResult = $svc->settleRoomCharge(
            $this->propertyA,
            $orderId,
            $this->roomId,
            $idempotencyKey,
            1
        );
        $this->assertSame($settleResult['folio_charge_id'], $retryResult['folio_charge_id']);

        $balanceAfter = $folioSvc->calculateBalance($folioId, $this->propertyA);
        $this->assertSame(85000, $balanceAfter['total_charges']);
    }

    public function test_settle_room_charge_rejected_if_room_not_in_house(): void
    {
        $vacantRoomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeId,
            'code' => '202',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $svc = new PosService;
        $outletId = $svc->createOutlet($this->propertyA, [
            'code' => 'BAR-01',
            'name' => 'Rooftop Bar',
            'type' => 'bar',
        ]);

        $orderId = $svc->openOrder($this->propertyA, $outletId, [
            'room_id' => $vacantRoomId,
            'actor_id' => 1,
        ]);

        $svc->addItemToOrder($this->propertyA, $orderId, [
            'item_name' => 'Cocktail',
            'quantity' => 1,
            'unit_price' => 120000,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No in-house guest stay found in this room');
        $svc->settleRoomCharge($this->propertyA, $orderId, $vacantRoomId, 'KEY-VACANT', 1);
    }

    public function test_cross_property_outlet_access_rejected(): void
    {
        $svc = new PosService;
        $outletIdA = $svc->createOutlet($this->propertyA, [
            'code' => 'RESTO-A',
            'name' => 'Resto A',
            'type' => 'restaurant',
        ]);

        $this->expectException(\DomainException::class);
        $svc->openOrder($this->propertyB, $outletIdA, ['actor_id' => 1]);
    }
}
