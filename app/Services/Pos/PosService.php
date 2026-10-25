<?php

namespace App\Services\Pos;

use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosOutlet;
use App\Models\Property;
use App\Services\Finance\FolioService;
use Illuminate\Support\Facades\DB;

/**
 * PosService handles local POS outlets, orders, direct payment settlement,
 * and atomic room-charge folio billing.
 *
 * Implements POS-001 requirements:
 * - Local-first offline operation (no external cloud dependency).
 * - Multi-tenant isolation by property_id.
 * - Settle direct payment or post room charge to in-house guest folio.
 * - Idempotency key protection against duplicate charge attempts on network retries.
 */
class PosService
{
    public function __construct(
        private readonly FolioService $folioService = new FolioService,
    ) {}

    /**
     * Create an outlet for a property.
     *
     * @param  array{code: string, name: string, type?: string}  $data
     */
    public function createOutlet(Property $property, array $data): int
    {
        return (int) PosOutlet::create([
            'property_id' => $property->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'type' => $data['type'] ?? 'restaurant',
            'is_active' => true,
        ])->id;
    }

    /**
     * Open an order in an outlet.
     *
     * @param  array{table_number?: string|null, room_id?: int|null, actor_id: int}  $data
     */
    public function openOrder(Property $property, int $outletId, array $data): int
    {
        $outlet = $this->guardOutlet($property, $outletId);

        $orderNumber = sprintf('ORD-%s-%s-%s', $outlet->code, date('Ymd'), uniqid());

        return (int) PosOrder::create([
            'property_id' => $property->id,
            'outlet_id' => $outlet->id,
            'order_number' => $orderNumber,
            'table_number' => $data['table_number'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'status' => 'open',
            'total_amount' => 0,
            'created_by' => $data['actor_id'],
        ])->id;
    }

    /**
     * Add an item to an open order and update the order total.
     *
     * @param  array{item_name: string, quantity: int, unit_price: int}  $data
     */
    public function addItemToOrder(Property $property, int $orderId, array $data): int
    {
        return DB::transaction(function () use ($property, $orderId, $data): int {
            $order = $this->guardOrder($property, $orderId);

            if ($order->status !== 'open') {
                throw new \DomainException('Cannot add items to non-open order.');
            }

            $quantity = $data['quantity'];
            $unitPrice = $data['unit_price'];
            $totalPrice = $quantity * $unitPrice;

            $item = PosOrderItem::create([
                'order_id' => $order->id,
                'item_name' => $data['item_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            $newTotal = (int) PosOrderItem::where('order_id', $order->id)->sum('total_price');
            $order->update(['total_amount' => $newTotal]);

            return (int) $item->id;
        });
    }

    /**
     * Get order details with items.
     *
     * @return array{
     *     id: int,
     *     order_number: string,
     *     status: string,
     *     total_amount: int,
     *     tender_type: string|null,
     *     items: list<array{item_name: string, quantity: int, unit_price: int, total_price: int}>
     * }
     */
    public function getOrder(Property $property, int $orderId): array
    {
        $order = $this->guardOrder($property, $orderId);
        $items = PosOrderItem::where('order_id', $order->id)->get();

        $itemList = [];
        foreach ($items as $item) {
            $itemList[] = [
                'item_name' => $item->item_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->unit_price,
                'total_price' => (int) $item->total_price,
            ];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total_amount' => (int) $order->total_amount,
            'tender_type' => $order->tender_type,
            'items' => $itemList,
        ];
    }

    /**
     * Settle an order via direct payment (cash, card, etc).
     *
     * @return array{status: string, tender_type: string, total_amount: int}
     */
    public function settleDirectPayment(Property $property, int $orderId, string $tenderType, int $actorId): array
    {
        return DB::transaction(function () use ($property, $orderId, $tenderType): array {
            $order = $this->guardOrder($property, $orderId);

            if ($order->status !== 'open') {
                throw new \DomainException('Order is not open.');
            }

            $order->update([
                'status' => 'paid',
                'tender_type' => $tenderType,
            ]);

            return [
                'status' => 'paid',
                'tender_type' => $tenderType,
                'total_amount' => (int) $order->total_amount,
            ];
        });
    }

    /**
     * Settle an order by posting it as a room charge to the guest's folio.
     *
     * @return array{status: string, folio_id: int, folio_charge_id: int, total_amount: int}
     */
    public function settleRoomCharge(
        Property $property,
        int $orderId,
        int $roomId,
        string $idempotencyKey,
        int $actorId,
    ): array {
        return DB::transaction(function () use ($property, $orderId, $roomId, $idempotencyKey, $actorId): array {
            $order = $this->guardOrder($property, $orderId);

            // Idempotency check: if order already posted with this key, return stored charge
            if ($order->status === 'posted_to_room' && $order->idempotency_key === $idempotencyKey) {
                return [
                    'status' => 'posted_to_room',
                    'folio_id' => (int) $order->folio_id,
                    'folio_charge_id' => (int) $order->folio_charge_id,
                    'total_amount' => (int) $order->total_amount,
                ];
            }

            if ($order->status !== 'open') {
                throw new \DomainException('Order is not open for settlement.');
            }

            // Find active in-house stay for this room in this property
            $stay = DB::table('stays')
                ->where('property_id', $property->id)
                ->where('room_id', $roomId)
                ->where('status', 'in_house')
                ->first();

            if ($stay === null) {
                throw new \DomainException('No in-house guest stay found in this room.');
            }

            // Get or create folio for reservation
            $folioId = $this->folioService->getOrCreateFolioForReservation((int) $stay->reservation_id, $property);

            // Load outlet name
            $outlet = PosOutlet::find($order->outlet_id);
            $outletName = $outlet !== null ? $outlet->name : 'POS Outlet';

            // Post charge to folio
            $chargeId = $this->folioService->postCharge($folioId, $property, [
                'amount' => (int) $order->total_amount,
                'description' => "{$outletName} Order #{$order->order_number}",
                'item_type' => 'food_beverage',
                'actor_id' => $actorId,
            ]);

            // Update order status
            $order->update([
                'status' => 'posted_to_room',
                'room_id' => $roomId,
                'folio_id' => $folioId,
                'folio_charge_id' => $chargeId,
                'idempotency_key' => $idempotencyKey,
                'tender_type' => 'room_charge',
            ]);

            return [
                'status' => 'posted_to_room',
                'folio_id' => $folioId,
                'folio_charge_id' => $chargeId,
                'total_amount' => (int) $order->total_amount,
            ];
        });
    }

    private function guardOutlet(Property $property, int $outletId): PosOutlet
    {
        $outlet = PosOutlet::where('id', $outletId)
            ->where('property_id', $property->id)
            ->first();

        if ($outlet === null) {
            throw new \DomainException('Outlet not found in this property.');
        }

        if (! $outlet->is_active) {
            throw new \DomainException('Outlet is not active.');
        }

        return $outlet;
    }

    private function guardOrder(Property $property, int $orderId): PosOrder
    {
        $order = PosOrder::where('id', $orderId)
            ->where('property_id', $property->id)
            ->first();

        if ($order === null) {
            throw new \DomainException('Order not found in this property.');
        }

        return $order;
    }
}
