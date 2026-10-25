<?php

namespace App\Services;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Room availability check against reservations.
 *
 * Rules:
 * - Only confirmed/checked_in reservations block availability.
 * - Cancelled/checked_out/no_show do not block.
 * - Same-day turnover allowed: check-out date = next check-in date is OK.
 * - Overbooking is OFF per D-005.
 */
class AvailabilityService
{
    /** Statuses that occupy a room */
    private const BLOCKING = ['confirmed', 'checked_in'];

    /**
     * Check if a specific room is available for given dates.
     * Uses rate_snapshot check_in/check_out for the reservation period.
     */
    public function isRoomAvailable(int $roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        // Find overlapping reservations with blocking status.
        // Overlap: existing.check_in < requested.check_out AND existing.check_out > requested.check_in
        // Same-day turnover: existing check_out == requested check_in is NOT an overlap.
        $count = DB::table('reservations')
            ->join('rate_snapshots', 'reservations.rate_snapshot_id', '=', 'rate_snapshots.id')
            ->where('reservations.room_id', $roomId)
            ->whereIn('reservations.status', self::BLOCKING)
            ->whereDate('rate_snapshots.check_in', '<', $checkOut->toDateString())
            ->whereDate('rate_snapshots.check_out', '>', $checkIn->toDateString())
            ->count();

        return $count === 0;
    }

    /**
     * List available rooms of a given type for the date range.
     *
     * @return array<int, \stdClass>
     */
    public function availableRooms(Property $property, int $roomTypeId, Carbon $checkIn, Carbon $checkOut): array
    {
        $occupiedRoomIds = DB::table('reservations')
            ->join('rate_snapshots', 'reservations.rate_snapshot_id', '=', 'rate_snapshots.id')
            ->where('reservations.property_id', $property->id)
            ->whereIn('reservations.status', self::BLOCKING)
            ->whereDate('rate_snapshots.check_in', '<', $checkOut->toDateString())
            ->whereDate('rate_snapshots.check_out', '>', $checkIn->toDateString())
            ->pluck('reservations.room_id');

        return DB::table('rooms')
            ->where('property_id', $property->id)
            ->where('room_type_id', $roomTypeId)
            ->where('is_active', true)
            ->whereNotIn('id', $occupiedRoomIds)
            ->orderBy('code')
            ->get()
            ->all();
    }
}
