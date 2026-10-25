<?php

namespace App\Services;

use App\Models\Property;
use App\Models\RatePlan;
use Carbon\Carbon;

/**
 * Rate quote engine — finds applicable rate plan and calculates stay cost.
 *
 * Tax rounding: half-up to nearest IDR per D-004 provisional decision.
 * No booking/availability logic. No room assignment. Service only.
 */
class RateQuoteService
{
    /**
     * @return array{
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
     * }|null
     */
    public function quote(
        Property $property,
        ?int $roomTypeId,
        Carbon $checkIn,
        Carbon $checkOut,
    ): ?array {
        if ($checkOut->lte($checkIn)) {
            return null;
        }

        $nights = (int) $checkIn->diffInDays($checkOut);
        if ($nights < 1) {
            return null;
        }

        $plan = $this->findPlan($property, $roomTypeId, $checkIn, $checkOut);
        if ($plan === null) {
            return null;
        }

        $ratePerNight = (int) $plan->base_price_per_night;
        $subtotal = $ratePerNight * $nights;

        $taxPct = $plan->tax_percentage;
        $taxAmount = 0;
        if ($taxPct !== null && (float) $taxPct > 0) {
            // ponytail: per-line tax, half-up rounding per D-004; upgrade to per-night rounding if accountant requires
            $taxAmount = (int) round($subtotal * (float) $taxPct / 100, 0, PHP_ROUND_HALF_UP);
        }

        $breakdown = [];
        $cursor = $checkIn->copy();
        for ($i = 0; $i < $nights; $i++) {
            $breakdown[] = [
                'date' => $cursor->toDateString(),
                'amount' => $ratePerNight,
            ];
            $cursor->addDay();
        }

        return [
            'rate_plan_id' => $plan->id,
            'rate_plan_name' => $plan->name,
            'rate_plan_code' => $plan->code,
            'rate_per_night' => $ratePerNight,
            'nights' => $nights,
            'subtotal' => $subtotal,
            'tax_percentage' => $taxPct,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nightly_breakdown' => $breakdown,
        ];
    }

    private function findPlan(Property $property, ?int $roomTypeId, Carbon $checkIn, Carbon $checkOut): ?RatePlan
    {
        $query = RatePlan::query()
            ->where('property_id', $property->id)
            ->where('is_active', true);

        // Date overlap: plan effective range must cover the entire stay
        // null effective_from/until = open-ended
        // Use whereDate to avoid SQLite datetime-vs-date string comparison issues
        $checkInDate = $checkIn->toDateString();
        $lastNightDate = $checkOut->copy()->subDay()->toDateString();

        $query->where(function ($q) use ($checkInDate, $lastNightDate) {
            $q->where(function ($inner) use ($checkInDate) {
                $inner->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $checkInDate);
            });
            $q->where(function ($inner) use ($lastNightDate) {
                // effective_until must cover checkout-1 (last night)
                $inner->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $lastNightDate);
            });
        });

        if ($roomTypeId !== null) {
            // Prefer room-type-specific, fall back to general (null room_type_id)
            $specific = (clone $query)->where('room_type_id', $roomTypeId)->first();
            if ($specific !== null) {
                return $specific;
            }

            return (clone $query)->whereNull('room_type_id')->first();
        }

        return $query->whereNull('room_type_id')->first();
    }
}
