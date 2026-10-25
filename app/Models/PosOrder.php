<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Point of sale order.
 *
 * @property int $id
 * @property int $property_id
 * @property int $outlet_id
 * @property string $order_number
 * @property string|null $table_number
 * @property int|null $room_id
 * @property string $status
 * @property int $total_amount
 * @property string|null $tender_type
 * @property int|null $folio_id
 * @property int|null $folio_charge_id
 * @property string|null $idempotency_key
 * @property int $created_by
 */
class PosOrder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<PosOutlet, $this> */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(PosOutlet::class, 'outlet_id');
    }

    /** @return HasMany<PosOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class, 'order_id');
    }
}
