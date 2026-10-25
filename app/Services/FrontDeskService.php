<?php

namespace App\Services;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Front desk query and action service.
 *
 * Provides arrivals/in-house/departures scoped by property+date,
 * authorized check-in/out, and occupancy summary.
 *
 * FR-001: views scoped by hotel and business date.
 * FR-002: check-in verifies reservation eligibility.
 * D-005 provisional decisions applied.
 */
class FrontDeskService
{
    public function __construct(
        private readonly StayService $stayService = new StayService,
    ) {}

    /**
     * Today's expected arrivals — confirmed reservations with check-in date matching.
     *
     * @return array<int, \stdClass>
     */
    public function arrivals(Property $property, Carbon $date): array
    {
        return DB::table('reservations')
            ->join('rate_snapshots', 'reservations.rate_snapshot_id', '=', 'rate_snapshots.id')
            ->join('guests', 'reservations.guest_id', '=', 'guests.id')
            ->where('reservations.property_id', $property->id)
            ->where('reservations.status', 'confirmed')
            ->whereDate('rate_snapshots.check_in', $date->toDateString())
            ->select([
                'reservations.*',
                'rate_snapshots.check_in',
                'rate_snapshots.check_out',
                'guests.display_name as guest_name',
            ])
            ->orderBy('guests.display_name')
            ->get()
            ->all();
    }

    /**
     * In-house stays — active stays (status = in_house).
     *
     * @return array<int, \stdClass>
     */
    public function inHouse(Property $property): array
    {
        return DB::table('stays')
            ->join('guests', 'stays.guest_id', '=', 'guests.id')
            ->join('rooms', 'stays.room_id', '=', 'rooms.id')
            ->where('stays.property_id', $property->id)
            ->where('stays.status', 'in_house')
            ->select([
                'stays.*',
                'guests.display_name as guest_name',
                'rooms.code as room_code',
            ])
            ->orderBy('rooms.code')
            ->get()
            ->all();
    }

    /**
     * Today's expected departures — in-house stays with check-out date matching.
     *
     * @return array<int, \stdClass>
     */
    public function departures(Property $property, Carbon $date): array
    {
        return DB::table('stays')
            ->join('reservations', 'stays.reservation_id', '=', 'reservations.id')
            ->join('rate_snapshots', 'reservations.rate_snapshot_id', '=', 'rate_snapshots.id')
            ->join('guests', 'stays.guest_id', '=', 'guests.id')
            ->join('rooms', 'stays.room_id', '=', 'rooms.id')
            ->where('stays.property_id', $property->id)
            ->where('stays.status', 'in_house')
            ->whereDate('rate_snapshots.check_out', $date->toDateString())
            ->select([
                'stays.*',
                'guests.display_name as guest_name',
                'rooms.code as room_code',
                'rate_snapshots.check_out as expected_checkout',
            ])
            ->orderBy('rooms.code')
            ->get()
            ->all();
    }

    /**
     * Authorized check-in — delegates to StayService.
     */
    public function performCheckIn(int $reservationId, Property $property, int $actorId): int
    {
        return $this->stayService->checkIn($reservationId, $property, $actorId);
    }

    /**
     * Authorized check-out — delegates to StayService.
     */
    public function performCheckOut(int $stayId, Property $property, int $actorId): void
    {
        $this->stayService->checkOut($stayId, $property, $actorId);
    }

    /**
     * Occupancy summary for a property on a given date.
     *
     * @return array{total_rooms: int, occupied: int, available: int, arrivals_today: int, departures_today: int}
     */
    public function occupancySummary(Property $property, Carbon $date): array
    {
        $totalRooms = DB::table('rooms')
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->count();

        $occupied = DB::table('stays')
            ->where('property_id', $property->id)
            ->where('status', 'in_house')
            ->count();

        $arrivalsToday = count($this->arrivals($property, $date));

        $departuresToday = count($this->departures($property, $date));

        return [
            'total_rooms' => $totalRooms,
            'occupied' => $occupied,
            'available' => $totalRooms - $occupied,
            'arrivals_today' => $arrivalsToday,
            'departures_today' => $departuresToday,
        ];
    }
}
