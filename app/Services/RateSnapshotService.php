<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * Captures an immutable rate quote snapshot for booking.
 *
 * Snapshot survives master rate plan changes/deactivation.
 * Table has SQLite triggers preventing UPDATE/DELETE.
 */
class RateSnapshotService
{
    /**
     * Persist a rate quote as an immutable snapshot.
     *
     * @param  array{
     *   rate_plan_id: int,
     *   rate_plan_name: string,
     *   rate_plan_code: string,
     *   rate_per_night: int,
     *   nights: int,
     *   subtotal: int,
     *   tax_percentage: string|null,
     *   tax_amount: int,
     *   total: int,
     *   check_in: string,
     *   check_out: string,
     *   nightly_breakdown: list<array{date: string, amount: int}>,
     * }  $quote
     */
    public function capture(Property $property, array $quote): int
    {
        return DB::table('rate_snapshots')->insertGetId([
            'property_id' => $property->id,
            'rate_plan_id' => $quote['rate_plan_id'],
            'rate_plan_name' => $quote['rate_plan_name'],
            'rate_plan_code' => $quote['rate_plan_code'],
            'rate_per_night' => $quote['rate_per_night'],
            'nights' => $quote['nights'],
            'subtotal' => $quote['subtotal'],
            'tax_percentage' => $quote['tax_percentage'],
            'tax_amount' => $quote['tax_amount'],
            'total' => $quote['total'],
            'check_in' => $quote['check_in'],
            'check_out' => $quote['check_out'],
            'nightly_breakdown' => json_encode($quote['nightly_breakdown']),
            'captured_at' => now(),
        ]);
    }
}
