<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable rate snapshot — captured at booking time.
 * SQLite triggers prevent UPDATE/DELETE.
 *
 * @property int $id
 * @property int $property_id
 * @property int $rate_plan_id
 * @property string $rate_plan_name
 * @property string $rate_plan_code
 * @property int $rate_per_night
 * @property int $nights
 * @property int $subtotal
 * @property string|null $tax_percentage
 * @property int $tax_amount
 * @property int $total
 * @property string $check_in
 * @property string $check_out
 */
class RateSnapshot extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rate_per_night' => 'integer',
            'nights' => 'integer',
            'subtotal' => 'integer',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'check_in' => 'date',
            'check_out' => 'date',
            'nightly_breakdown' => 'array',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<RatePlan, $this> */
    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
