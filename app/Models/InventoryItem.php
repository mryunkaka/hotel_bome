<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * InventoryItem master record.
 *
 * @property int $id
 * @property int $property_id
 * @property string $code
 * @property string $name
 * @property string $category
 * @property string $unit
 * @property int $par_level
 * @property int $unit_cost
 * @property int|null $sale_price
 * @property bool $is_active
 */
class InventoryItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'par_level' => 'integer',
            'unit_cost' => 'integer',
            'sale_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }
}
