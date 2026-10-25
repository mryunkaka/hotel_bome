<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EventBooking (BEO banquet event order).
 *
 * @property int $id
 * @property int $property_id
 * @property int $space_id
 * @property string $event_name
 * @property string $organizer_name
 * @property string $start_time
 * @property string $end_time
 * @property int|null $expected_attendees
 * @property string|null $setup_style
 * @property string $status
 * @property int $rental_amount
 * @property int $catering_amount
 * @property int $total_amount
 * @property int $deposit_required
 * @property int|null $folio_id
 * @property string|null $cancellation_reason
 * @property int $created_by
 */
class EventBooking extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expected_attendees' => 'integer',
            'rental_amount' => 'integer',
            'catering_amount' => 'integer',
            'total_amount' => 'integer',
            'deposit_required' => 'integer',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<EventSpace, $this> */
    public function space(): BelongsTo
    {
        return $this->belongsTo(EventSpace::class, 'space_id');
    }
}
