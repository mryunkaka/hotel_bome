<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Double-entry journal entry header.
 * Protected by SQLite triggers against UPDATE and DELETE.
 *
 * @property int $id
 * @property int $property_id
 * @property string $entry_number
 * @property string $entry_date
 * @property string $description
 * @property string $source_type
 * @property int|null $source_id
 * @property bool $is_balanced
 * @property int $total_amount
 * @property int|null $reversal_of_id
 * @property string|null $reason
 * @property int $created_by
 */
class JournalEntry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_balanced' => 'boolean',
            'total_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'entry_id');
    }
}
