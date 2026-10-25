<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EventSpace (Meeting rooms, ballrooms, function halls).
 *
 * @property int $id
 * @property int $property_id
 * @property string $code
 * @property string $name
 * @property int $capacity
 * @property int $hourly_rate
 * @property int $day_rate
 * @property bool $is_active
 */
class EventSpace extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'hourly_rate' => 'integer',
            'day_rate' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return HasMany<EventBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class, 'space_id');
    }
}
