<?php

namespace Tests\Feature\Inventory;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\Finance\FolioService;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * INV-001: Inventory movement ledger, stock balance calculation,
 * and minibar folio integration.
 */
class InventoryLedgerTest extends TestCase
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

        $this->propertyA = Property::factory()->create(['name' => 'Inventory Hotel A']);
        $this->propertyB = Property::factory()->create(['name' => 'Inventory Hotel B']);

        $this->guestA = DB::table('guests')->insertGetId([
            'property_id' => $this->propertyA->id,
            'display_name' => 'Inv Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->propertyA->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeId,
            'code' => '101',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propertyA->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Std Plan',
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
            Carbon::parse('2028-12-01'),
            Carbon::parse('2028-12-03'),
            1
        );

        $folioSvc = new FolioService;
        $this->folioId = $folioSvc->getOrCreateFolioForReservation($this->resId, $this->propertyA);
    }

    public function test_inventory_item_creation_and_property_isolation(): void
    {
        $svc = new InventoryService;

        $itemIdA = $svc->createItem($this->propertyA, [
            'code' => 'WATER-330',
            'name' => 'Mineral Water 330ml',
            'category' => 'minibar',
            'unit' => 'bottle',
            'par_level' => 50,
            'unit_cost' => 5000,
            'sale_price' => 15000,
        ]);

        $this->assertGreaterThan(0, $itemIdA);

        $itemA = DB::table('inventory_items')->where('id', $itemIdA)->first();
        $this->assertSame($this->propertyA->id, (int) $itemA->property_id);
        $this->assertSame('WATER-330', $itemA->code);
        $this->assertSame(15000, (int) $itemA->sale_price);

        // Cross-property access rejected
        $this->expectException(\DomainException::class);
        $svc->getStockBalance($this->propertyB, $itemIdA);
    }

    public function test_stock_movements_recalculate_balance(): void
    {
        $svc = new InventoryService;

        $itemId = $svc->createItem($this->propertyA, [
            'code' => 'SNACK-CHIP',
            'name' => 'Potato Chips',
            'category' => 'minibar',
            'unit' => 'pack',
            'par_level' => 30,
            'unit_cost' => 10000,
            'sale_price' => 25000,
        ]);

        // 1. Initial receive (+50)
        $svc->recordMovement($this->propertyA, $itemId, [
            'movement_type' => 'receive',
            'quantity' => 50,
            'reason' => 'PO-2028-001 delivery',
            'actor_id' => 1,
        ]);
        $this->assertSame(50, $svc->getStockBalance($this->propertyA, $itemId));

        // 2. Issue to floor (-10)
        $svc->recordMovement($this->propertyA, $itemId, [
            'movement_type' => 'issue',
            'quantity' => -10,
            'reason' => 'Restock 1st floor rooms',
            'actor_id' => 1,
        ]);
        $this->assertSame(40, $svc->getStockBalance($this->propertyA, $itemId));

        // 3. Stock count adjustment (-2 damaged)
        $svc->recordMovement($this->propertyA, $itemId, [
            'movement_type' => 'adjustment',
            'quantity' => -2,
            'reason' => 'Damaged packaging written off',
            'actor_id' => 1,
        ]);
        $this->assertSame(38, $svc->getStockBalance($this->propertyA, $itemId));
    }

    public function test_stock_movement_is_immutable_cannot_be_updated_or_deleted(): void
    {
        $svc = new InventoryService;

        $itemId = $svc->createItem($this->propertyA, [
            'code' => 'SOAP-BAR',
            'name' => 'Soap Bar 50g',
            'category' => 'amenity',
            'unit' => 'pcs',
            'par_level' => 100,
            'unit_cost' => 3000,
        ]);

        $mId = $svc->recordMovement($this->propertyA, $itemId, [
            'movement_type' => 'receive',
            'quantity' => 100,
            'reason' => 'Initial stock',
            'actor_id' => 1,
        ]);

        $this->expectException(QueryException::class);
        DB::table('stock_movements')->where('id', $mId)->update(['quantity' => 50]);
    }

    public function test_post_minibar_consumption_records_movement_and_folio_charge(): void
    {
        $svc = new InventoryService;

        $itemId = $svc->createItem($this->propertyA, [
            'code' => 'SODA-CAN',
            'name' => 'Cola Can 330ml',
            'category' => 'minibar',
            'unit' => 'can',
            'par_level' => 40,
            'unit_cost' => 8000,
            'sale_price' => 20000,
        ]);

        // Receive stock first
        $svc->recordMovement($this->propertyA, $itemId, [
            'movement_type' => 'receive',
            'quantity' => 20,
            'reason' => 'Stock replenishment',
            'actor_id' => 1,
        ]);

        // Guest consumes 2 cans from minibar
        $res = $svc->postMinibarConsumption($this->propertyA, $itemId, 2, $this->folioId, 1);

        $this->assertGreaterThan(0, $res['movement_id']);
        $this->assertGreaterThan(0, $res['charge_id']);
        $this->assertSame(40000, $res['total_charge']); // 2 * 20000

        // Stock decreased by 2
        $this->assertSame(18, $svc->getStockBalance($this->propertyA, $itemId));

        // Folio charge posted correctly
        $folioSvc = new FolioService;
        $bal = $folioSvc->calculateBalance($this->folioId, $this->propertyA);
        $this->assertSame(40000, $bal['total_charges']);
        $this->assertSame(40000, $bal['balance']);

        // Charge item verified
        $charge = DB::table('folio_charges')->where('id', $res['charge_id'])->first();
        $this->assertSame('food_beverage', $charge->item_type);
        $this->assertSame(40000, (int) $charge->amount);
    }
}
