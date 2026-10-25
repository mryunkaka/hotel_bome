<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * Stay lifecycle: check-in → in-house → check-out.
 * Links reservation, room, guest with full audit trail.
 *
 * D-005 provisional rules:
 * - Check-in requires confirmed reservation + property match.
 * - Check-out triggers dirty handoff (housekeeping).
 * - Room move records old/new assignment with reason.
 * - Guest ID reference only (D-007 open).
 */
class StayService
{
    /**
     * Check in a confirmed reservation — creates a stay record.
     */
    public function checkIn(int $reservationId, Property $property, int $actorId): int
    {
        return DB::transaction(function () use ($reservationId, $property, $actorId): int {
            $res = DB::table('reservations')
                ->where('id', $reservationId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($res === null) {
                throw new \DomainException('Reservation not found in this property.');
            }

            if ($res->status !== 'confirmed') {
                throw new \DomainException('Reservation is not eligible for check-in (status: '.$res->status.').');
            }

            // Transition reservation
            DB::table('reservations')
                ->where('id', $reservationId)
                ->update(['status' => 'checked_in', 'updated_at' => now()]);

            DB::table('reservation_events')->insert([
                'reservation_id' => $reservationId,
                'from_status' => 'confirmed',
                'to_status' => 'checked_in',
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);

            // Create stay
            $now = now();
            $stayId = DB::table('stays')->insertGetId([
                'property_id' => $property->id,
                'reservation_id' => $reservationId,
                'guest_id' => $res->guest_id,
                'room_id' => $res->room_id,
                'status' => 'in_house',
                'checked_in_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Record check-in event
            DB::table('stay_events')->insert([
                'stay_id' => $stayId,
                'event_type' => 'check_in',
                'room_id' => $res->room_id,
                'actor_id' => $actorId,
                'created_at' => $now,
            ]);

            return $stayId;
        });
    }

    /**
     * Check in a walk-in reservation (already checked_in status).
     */
    public function checkInWalkIn(int $reservationId, Property $property, int $actorId): int
    {
        return DB::transaction(function () use ($reservationId, $property, $actorId): int {
            $res = DB::table('reservations')
                ->where('id', $reservationId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($res === null) {
                throw new \DomainException('Reservation not found in this property.');
            }

            if ($res->status !== 'checked_in' || ! $res->is_walk_in) {
                throw new \DomainException('Reservation is not an eligible walk-in.');
            }

            $now = now();
            $stayId = DB::table('stays')->insertGetId([
                'property_id' => $property->id,
                'reservation_id' => $reservationId,
                'guest_id' => $res->guest_id,
                'room_id' => $res->room_id,
                'status' => 'in_house',
                'checked_in_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('stay_events')->insert([
                'stay_id' => $stayId,
                'event_type' => 'check_in',
                'room_id' => $res->room_id,
                'actor_id' => $actorId,
                'created_at' => $now,
            ]);

            return $stayId;
        });
    }

    /**
     * Check out — marks stay completed, transitions reservation, records event.
     */
    public function checkOut(int $stayId, Property $property, int $actorId): void
    {
        DB::transaction(function () use ($stayId, $property, $actorId): void {
            $stay = DB::table('stays')
                ->where('id', $stayId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($stay === null) {
                throw new \DomainException('Stay not found in this property.');
            }

            if ($stay->status !== 'in_house') {
                throw new \DomainException('Stay is not in_house (status: '.$stay->status.').');
            }

            $now = now();

            // Update stay
            DB::table('stays')
                ->where('id', $stayId)
                ->update([
                    'status' => 'checked_out',
                    'checked_out_at' => $now,
                    'updated_at' => $now,
                ]);

            // Transition reservation
            DB::table('reservations')
                ->where('id', $stay->reservation_id)
                ->update(['status' => 'checked_out', 'updated_at' => $now]);

            DB::table('reservation_events')->insert([
                'reservation_id' => $stay->reservation_id,
                'from_status' => 'checked_in',
                'to_status' => 'checked_out',
                'actor_id' => $actorId,
                'created_at' => $now,
            ]);

            // Record check-out event
            DB::table('stay_events')->insert([
                'stay_id' => $stayId,
                'event_type' => 'check_out',
                'room_id' => $stay->room_id,
                'actor_id' => $actorId,
                'created_at' => $now,
            ]);

            // ponytail: D-005 says checkout triggers dirty; add room_status_events
            // when housekeeping integration is wired. For now, the stay event
            // serves as the handoff signal.
        });
    }

    /**
     * Move guest to a different room during stay.
     */
    public function moveRoom(int $stayId, Property $property, int $newRoomId, int $actorId, ?string $reason = null): void
    {
        DB::transaction(function () use ($stayId, $property, $newRoomId, $actorId, $reason): void {
            $stay = DB::table('stays')
                ->where('id', $stayId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($stay === null) {
                throw new \DomainException('Stay not found in this property.');
            }

            if ($stay->status !== 'in_house') {
                throw new \DomainException('Cannot move room: stay is not in_house.');
            }

            $oldRoomId = (int) $stay->room_id;

            DB::table('stays')
                ->where('id', $stayId)
                ->update(['room_id' => $newRoomId, 'updated_at' => now()]);

            // Also update reservation room_id to keep consistency
            DB::table('reservations')
                ->where('id', $stay->reservation_id)
                ->update(['room_id' => $newRoomId, 'updated_at' => now()]);

            DB::table('stay_events')->insert([
                'stay_id' => $stayId,
                'event_type' => 'room_move',
                'room_id' => $oldRoomId,
                'reason' => $reason,
                'changes' => json_encode(['from_room' => $oldRoomId, 'to_room' => $newRoomId]),
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);
        });
    }
}
