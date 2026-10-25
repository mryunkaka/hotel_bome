<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockMovement append-only ledger entry.
 * SQLite triggers prevent UPDATE and DELETE.
 *
 * @property int $id
 * @property int $property_id
 * @property int $item_id
 * @property string $movement_type
 * @property int $quantity
 * @property int|null $unit_cost
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $reason
 * @property int $actor_id
 */
class StockMovement extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'integer',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
