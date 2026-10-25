<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal line item.
 * Protected by SQLite triggers against UPDATE and DELETE.
 *
 * @property int $id
 * @property int $entry_id
 * @property int $account_id
 * @property int $debit
 * @property int $credit
 * @property string|null $description
 */
class JournalLine extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'debit' => 'integer',
            'credit' => 'integer',
        ];
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'entry_id');
    }

    /** @return BelongsTo<ChartOfAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
