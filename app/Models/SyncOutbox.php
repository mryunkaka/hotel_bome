<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SyncOutbox record for transactional outbox pattern.
 *
 * @property int $id
 * @property int $property_id
 * @property string $event_uuid
 * @property string $topic
 * @property string $payload
 * @property string $status
 * @property int $attempts
 * @property int $max_attempts
 * @property string|null $last_error
 * @property string|null $next_retry_at
 * @property string|null $delivered_at
 */
class SyncOutbox extends Model
{
    protected $table = 'sync_outbox';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
