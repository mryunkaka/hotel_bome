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

    public function create(Property $property, int $guestId, int $roomId, int $snapshotId, int $actorId): int
    {
        return DB::transaction(function () use ($property, $guestId, $roomId, $snapshotId, $actorId): int {
            $resId = DB::table('reservations')->insertGetId([
                'property_id' => $property->id,
                'guest_id' => $guestId,
                'room_id' => $roomId,
                'rate_snapshot_id' => $snapshotId,
                'status' => 'confirmed',
                'is_walk_in' => false,
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
