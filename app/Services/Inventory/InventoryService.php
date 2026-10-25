<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\Property;
use App\Services\Finance\FolioService;
use Illuminate\Support\Facades\DB;

/**
 * InventoryService manages inventory master records, stock movement ledger,
 * balance calculations, and automatic minibar folio billing integration.
 *
 * Implements INV-001 requirements:
 * - Movement ledger is append-only (SQLite triggers prevent UPDATE/DELETE).
 * - Multi-tenant isolation by property_id.
 * - Stock balances are recomputed directly from movement records.
 * - Minibar sales atomically deduct inventory and post a charge to the guest folio.
 */
class InventoryService
{
    public function __construct(
        private readonly FolioService $folioService = new FolioService,
    ) {}

    /**
     * Create an inventory item for a property.
     *
     * @param  array{
     *     code: string,
     *     name: string,
     *     category?: string,
     *     unit?: string,
     *     par_level?: int,
     *     unit_cost?: int,
     *     sale_price?: int|null
     * }  $data
     */
    public function createItem(Property $property, array $data): int
    {
        $now = now();

        return (int) DB::table('inventory_items')->insertGetId([
            'property_id' => $property->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'category' => $data['category'] ?? 'amenity',
            'unit' => $data['unit'] ?? 'pcs',
            'par_level' => $data['par_level'] ?? 0,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Record a stock movement in the append-only ledger.
     *
     * @param  array{
     *     movement_type: string,
     *     quantity: int,
     *     unit_cost?: int|null,
     *     reference_type?: string|null,
     *     reference_id?: int|null,
     *     reason?: string|null,
     *     actor_id: int
     * }  $data
     */
    public function recordMovement(Property $property, int $itemId, array $data): int
    {
        return DB::transaction(function () use ($property, $itemId, $data): int {
            $this->guardItem($property, $itemId);

            return (int) DB::table('stock_movements')->insertGetId([
                'property_id' => $property->id,
                'item_id' => $itemId,
                'movement_type' => $data['movement_type'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'actor_id' => $data['actor_id'],
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Calculate current stock on hand by summing ledger movements.
     */
    public function getStockBalance(Property $property, int $itemId): int
    {
        $this->guardItem($property, $itemId);

        return (int) DB::table('stock_movements')
            ->where('property_id', $property->id)
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    /**
     * Post a minibar consumption:
     * - Verifies item is active and has a sale_price.
     * - Records negative stock movement in the ledger.
     * - Posts a charge to the specified guest folio.
     *
     * @return array{movement_id: int, charge_id: int, total_charge: int}
     */
    public function postMinibarConsumption(
        Property $property,
        int $itemId,
        int $quantity,
        int $folioId,
        int $actorId,
    ): array {
        return DB::transaction(function () use ($property, $itemId, $quantity, $folioId, $actorId): array {
            $item = $this->guardItem($property, $itemId);

            if (empty($item->sale_price) || (int) $item->sale_price <= 0) {
                throw new \DomainException('Item does not have a valid minibar sale price.');
            }

            $unitPrice = (int) $item->sale_price;
            $totalCharge = $unitPrice * $quantity;

            // 1. Record stock reduction in movement ledger
            $movementId = (int) DB::table('stock_movements')->insertGetId([
                'property_id' => $property->id,
                'item_id' => $itemId,
                'movement_type' => 'minibar_sale',
                'quantity' => -$quantity,
                'unit_cost' => $item->unit_cost,
                'reference_type' => 'folio',
                'reference_id' => $folioId,
                'reason' => "Minibar consumption: {$quantity}x {$item->name}",
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);

            // 2. Post charge to folio
            $chargeId = $this->folioService->postCharge($folioId, $property, [
                'amount' => $totalCharge,
                'description' => "Minibar: {$quantity}x {$item->name}",
                'item_type' => 'food_beverage',
                'actor_id' => $actorId,
            ]);

            return [
                'movement_id' => $movementId,
                'charge_id' => $chargeId,
                'total_charge' => $totalCharge,
            ];
        });
    }

    private function guardItem(Property $property, int $itemId): InventoryItem
    {
        /** @var InventoryItem|null $item */
        $item = InventoryItem::where('id', $itemId)
            ->where('property_id', $property->id)
            ->first();

        if ($item === null) {
            throw new \DomainException('Inventory item not found in this property.');
        }

        if (! $item->is_active) {
            throw new \DomainException('Inventory item is not active.');
        }

        return $item;
    }
}
