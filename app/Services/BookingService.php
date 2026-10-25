<?php

namespace App\Services;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates quote → snapshot → reservation atomically with availability check.
 * Overbooking OFF per D-005. Guest ID reference only (D-007 open).
 */
class BookingService
{
    public function __construct(
        private readonly RateQuoteService $quoteService = new RateQuoteService,
        private readonly RateSnapshotService $snapshotService = new RateSnapshotService,
        private readonly ReservationService $reservationService = new ReservationService,
        private readonly AvailabilityService $availabilityService = new AvailabilityService,
    ) {}

    /**
     * Create a confirmed reservation with availability guard.
     */
    public function book(
        Property $property,
        int $guestId,
        int $roomId,
        int $roomTypeId,
        Carbon $checkIn,
        Carbon $checkOut,
        int $actorId,
        ?string $source = null,
        ?int $groupId = null,
    ): int {
        return DB::transaction(function () use ($property, $guestId, $roomId, $roomTypeId, $checkIn, $checkOut, $actorId, $source, $groupId): int {
            $this->guardRoom($roomId, $checkIn, $checkOut, $property);

            $quote = $this->quoteService->quote($property, $roomTypeId, $checkIn, $checkOut);
            $snapshotId = $this->snapshotService->capture($property, $quote);

            return $this->reservationService->create(
                $property, $guestId, $roomId, $snapshotId, $actorId, $groupId, $source,
            );
        });
    }

    /**
     * Walk-in booking: availability check → quote → snapshot → create + check-in.
     */
    public function walkInBook(
        Property $property,
        int $guestId,
        int $roomId,
        int $roomTypeId,
        Carbon $checkIn,
        Carbon $checkOut,
        int $actorId,
    ): int {
        return DB::transaction(function () use ($property, $guestId, $roomId, $roomTypeId, $checkIn, $checkOut, $actorId): int {
            $this->guardRoom($roomId, $checkIn, $checkOut, $property);

            $quote = $this->quoteService->quote($property, $roomTypeId, $checkIn, $checkOut);
            $snapshotId = $this->snapshotService->capture($property, $quote);

            return $this->reservationService->walkIn($property, $guestId, $roomId, $snapshotId, $actorId);
        });
    }

    private function guardRoom(int $roomId, Carbon $checkIn, Carbon $checkOut, Property $property): void
    {
        $room = DB::table('rooms')->where('id', $roomId)->first();
        if ($room === null || ! $room->is_active) {
            throw new \DomainException('Room is not active or does not exist.');
        }

        if ((int) $room->property_id !== $property->id) {
            throw new \DomainException('Room does not belong to this property.');
        }

        if (! $this->availabilityService->isRoomAvailable($roomId, $checkIn, $checkOut)) {
            throw new \DomainException('Room is not available for the requested dates.');
        }
    }
}
