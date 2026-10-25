<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Minimum rate plan master data — no availability/booking logic yet.
 * Tax percentage is configurable per property per D-004 provisional decision.
 *
 * @property int $id
 * @property int $property_id
 * @property string $name
 * @property string $code
 * @property int $base_price_per_night
 * @property string|null $tax_percentage
 * @property string|null $effective_from
 * @property string|null $effective_until
 * @property bool $is_active
 */
#[Fillable(['name', 'code', 'base_price_per_night', 'tax_percentage', 'effective_from', 'effective_until'])]
class RatePlan extends Model
{
    protected function casts(): array
    {
        return [
            'base_price_per_night' => 'integer',
            'tax_percentage' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
