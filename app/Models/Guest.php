<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prototype guest profile. Display names and free-text notes may contain personal data.
 * D-007 approval is required before operational use with real guest data or added fields.
 *
 * @property int $id
 * @property int $property_id
 * @property string $display_name
 * @property string|null $notes
 * @property bool $is_active
 */
#[Fillable(['display_name', 'notes'])]
class Guest extends Model
{
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
}
