<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reservation with state machine, property scope, and group support.
 * Guest ID is a technical reference; no PII fields (D-007 open).
 *
 * @property int $id
 * @property int $property_id
 * @property int $guest_id
 * @property int $room_id
 * @property int $rate_snapshot_id
 * @property string $status
 * @property bool $is_walk_in
 * @property int|null $group_id
 * @property string|null $source
 * @property string|null $notes
 */
class Reservation extends Model
{
    protected $guarded = ['id', 'property_id'];

    protected function casts(): array
    {
        return [
            'is_walk_in' => 'boolean',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<Guest, $this> */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /** @return BelongsTo<RateSnapshot, $this> */
    public function rateSnapshot(): BelongsTo
    {
        return $this->belongsTo(RateSnapshot::class, 'rate_snapshot_id');
    }

    /** @return HasMany<ReservationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ReservationEvent::class)->orderBy('id');
    }
}
