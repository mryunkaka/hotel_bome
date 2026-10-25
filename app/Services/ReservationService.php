<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * Reservation state machine per D-005 provisional decision.
 *
 * States: confirmed → checked_in → checked_out (terminal)
 *         confirmed → cancelled (terminal, requires reason)
 *         confirmed → no_show (terminal)
 *
 * Walk-in: creates confirmed + immediate check-in in single transaction.
 * All transitions are audited with immutable events.
 * Guest ID is a technical reference; no PII fields used (D-007 open).
 */
class ReservationService
{
    /** @var array<string, list<string>> Allowed transitions from each state */
    private const TRANSITIONS = [
        'confirmed' => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['checked_out'],
        // Terminal states
        'checked_out' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    private const MUTABLE_STATUSES = ['confirmed', 'checked_in'];

    public function create(
        Property $property,
        int $guestId,
        int $roomId,
        int $snapshotId,
        int $actorId,
        ?int $groupId = null,
        ?string $source = null,
    ): int {
        if ($groupId !== null) {
            $group = DB::table('reservation_groups')
                ->where('id', $groupId)
                ->first();

            if ($group === null || (int) $group->property_id !== $property->id) {
                throw new \DomainException('Group does not belong to this property.');
            }
        }

        return DB::transaction(function () use ($property, $guestId, $roomId, $snapshotId, $actorId, $groupId, $source): int {
            $resId = DB::table('reservations')->insertGetId([
                'property_id' => $property->id,
                'guest_id' => $guestId,
                'room_id' => $roomId,
                'rate_snapshot_id' => $snapshotId,
                'status' => 'confirmed',
                'is_walk_in' => false,
                'group_id' => $groupId,
                'source' => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recordEvent($resId, null, 'confirmed', $actorId);

            return $resId;
        });
    }

    public function walkIn(Property $property, int $guestId, int $roomId, int $snapshotId, int $actorId): int
    {
        return DB::transaction(function () use ($property, $guestId, $roomId, $snapshotId, $actorId): int {
            $resId = DB::table('reservations')->insertGetId([
                'property_id' => $property->id,
                'guest_id' => $guestId,
                'room_id' => $roomId,
                'rate_snapshot_id' => $snapshotId,
                'status' => 'checked_in',
                'is_walk_in' => true,
                'source' => 'walk_in',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recordEvent($resId, null, 'confirmed', $actorId);
            $this->recordEvent($resId, 'confirmed', 'checked_in', $actorId);

            return $resId;
        });
    }

    public function transition(int $reservationId, Property $property, string $toStatus, int $actorId, ?string $reason = null): void
    {
        DB::transaction(function () use ($reservationId, $property, $toStatus, $actorId, $reason): void {
            $res = DB::table('reservations')
                ->where('id', $reservationId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($res === null) {
                throw new \DomainException('Reservation not found in this property.');
            }

            $fromStatus = $res->status;
            $allowed = self::TRANSITIONS[$fromStatus] ?? [];

            if (! in_array($toStatus, $allowed, true)) {
                throw new \DomainException("Cannot transition from {$fromStatus} to {$toStatus}.");
            }

            DB::table('reservations')
                ->where('id', $reservationId)
                ->update([
                    'status' => $toStatus,
                    'updated_at' => now(),
                ]);

            $this->recordEvent($reservationId, $fromStatus, $toStatus, $actorId, $reason);
        });
    }

    public function reassignRoom(int $reservationId, Property $property, int $newRoomId, int $actorId, ?string $reason = null): void
    {
        DB::transaction(function () use ($reservationId, $property, $newRoomId, $actorId, $reason): void {
            $res = DB::table('reservations')
                ->where('id', $reservationId)
                ->where('property_id', $property->id)
                ->lockForUpdate()
                ->first();

            if ($res === null) {
                throw new \DomainException('Reservation not found in this property.');
            }

            if (! in_array($res->status, self::MUTABLE_STATUSES, true)) {
                throw new \DomainException("Cannot reassign room on {$res->status} reservation.");
            }

            $oldRoomId = (int) $res->room_id;

            DB::table('reservations')
                ->where('id', $reservationId)
                ->update([
                    'room_id' => $newRoomId,
                    'updated_at' => now(),
                ]);

            DB::table('reservation_events')->insert([
                'reservation_id' => $reservationId,
                'from_status' => $res->status,
                'to_status' => 'room_changed',
                'reason' => $reason,
                'changes' => json_encode(['room_id' => ['from' => $oldRoomId, 'to' => $newRoomId]]),
                'actor_id' => $actorId,
                'created_at' => now(),
            ]);
        });
    }

    public function createGroup(Property $property, string $name, int $createdBy): int
    {
        return DB::table('reservation_groups')->insertGetId([
            'property_id' => $property->id,
            'name' => $name,
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function recordEvent(int $reservationId, ?string $from, string $to, int $actorId, ?string $reason = null): void
    {
        DB::table('reservation_events')->insert([
            'reservation_id' => $reservationId,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'actor_id' => $actorId,
            'created_at' => now(),
        ]);
    }
}
