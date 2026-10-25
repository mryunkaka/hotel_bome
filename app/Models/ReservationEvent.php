<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable reservation event — audit trail.
 * SQLite triggers prevent UPDATE/DELETE.
 *
 * @property int $id
 * @property int $reservation_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $reason
 * @property string|null $changes
 * @property int $actor_id
 */
class ReservationEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
