<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Point of sale outlet.
 *
 * @property int $id
 * @property int $property_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_active
 */
class PosOutlet extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return HasMany<PosOrder, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'outlet_id');
    }
}
