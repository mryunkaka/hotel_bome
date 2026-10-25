<?php

namespace App\Services\Events;

use App\Models\EventBooking;
use App\Models\EventSpace;
use App\Models\Property;
use App\Services\Finance\FolioService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * EventService manages event spaces, BEO bookings, conflict prevention,
 * and banquet folio charge integration.
 *
 * Implements EVT-001 requirements:
 * - Resource capacity and strict interval conflict validation.
 * - Multi-tenant isolation by property_id.
 * - Cancellation lifecycle releases space for new bookings.
 * - Folio billing integration for rental and catering charges via FolioService.
 */
class EventService
{
    public function __construct(
        private readonly FolioService $folioService = new FolioService,
    ) {}

    /**
     * Create an event space (function hall, meeting room).
     *
     * @param  array{code: string, name: string, capacity?: int, hourly_rate?: int, day_rate?: int}  $data
     */
    public function createSpace(Property $property, array $data): int
    {
        return (int) EventSpace::create([
            'property_id' => $property->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'capacity' => $data['capacity'] ?? 0,
            'hourly_rate' => $data['hourly_rate'] ?? 0,
            'day_rate' => $data['day_rate'] ?? 0,
            'is_active' => true,
        ])->id;
    }

    /**
     * Check if a space is available for the given time interval.
     */
    public function isSpaceAvailable(Property $property, int $spaceId, Carbon $startTime, Carbon $endTime): bool
    {
        $conflictCount = DB::table('event_bookings')
            ->where('property_id', $property->id)
            ->where('space_id', $spaceId)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->where('start_time', '<', $endTime->toDateTimeString())
            ->where('end_time', '>', $startTime->toDateTimeString())
            ->count();

        return $conflictCount === 0;
    }

    /**
     * Book an event space with conflict protection.
     *
     * @param  array{
     *     event_name: string,
     *     organizer_name: string,
     *     start_time: string,
     *     end_time: string,
     *     expected_attendees?: int|null,
     *     setup_style?: string|null,
     *     rental_amount?: int,
     *     catering_amount?: int,
     *     deposit_required?: int,
     *     actor_id: int
     * }  $data
     */
    public function bookEvent(Property $property, int $spaceId, array $data): int
    {
        return DB::transaction(function () use ($property, $spaceId, $data): int {
            $space = $this->guardSpace($property, $spaceId);

            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);

            if ($end->lessThanOrEqualTo($start)) {
                throw new \DomainException('End time must be after start time.');
            }

            // Conflict check
            if (! $this->isSpaceAvailable($property, $spaceId, $start, $end)) {
                throw new \DomainException('Event space is already booked during this time interval.');
            }

            $rentalAmount = $data['rental_amount'] ?? 0;
            $cateringAmount = $data['catering_amount'] ?? 0;
            $totalAmount = $rentalAmount + $cateringAmount;

            $booking = EventBooking::create([
                'property_id' => $property->id,
                'space_id' => $space->id,
                'event_name' => $data['event_name'],
                'organizer_name' => $data['organizer_name'],
                'start_time' => $start->toDateTimeString(),
                'end_time' => $end->toDateTimeString(),
                'expected_attendees' => $data['expected_attendees'] ?? null,
                'setup_style' => $data['setup_style'] ?? null,
                'status' => 'confirmed',
                'rental_amount' => $rentalAmount,
                'catering_amount' => $cateringAmount,
                'total_amount' => $totalAmount,
                'deposit_required' => $data['deposit_required'] ?? 0,
                'created_by' => $data['actor_id'],
            ]);

            return (int) $booking->id;
        });
    }

    /**
     * Cancel an event booking, releasing the space for other events.
     */
    public function cancelEvent(Property $property, int $bookingId, string $reason, int $actorId): void
    {
        DB::transaction(function () use ($property, $bookingId, $reason): void {
            $booking = $this->guardBooking($property, $bookingId);

            if ($booking->status === 'cancelled') {
                throw new \DomainException('Booking is already cancelled.');
            }

            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);
        });
    }

    /**
     * Create a dedicated folio for an event and post rental & catering charges.
     */
    public function createFolioForEvent(Property $property, int $bookingId, int $actorId): int
    {
        return DB::transaction(function () use ($property, $bookingId, $actorId): int {
            $booking = $this->guardBooking($property, $bookingId);

            $now = now();
            $folioId = (int) DB::table('folios')->insertGetId([
                'property_id' => $property->id,
                'reservation_id' => null,
                'guest_id' => null,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($booking->rental_amount > 0) {
                $this->folioService->postCharge($folioId, $property, [
                    'amount' => (int) $booking->rental_amount,
                    'description' => "Event Space Rental: {$booking->event_name}",
                    'item_type' => 'room',
                    'actor_id' => $actorId,
                ]);
            }

            if ($booking->catering_amount > 0) {
                $this->folioService->postCharge($folioId, $property, [
                    'amount' => (int) $booking->catering_amount,
                    'description' => "Event Catering & Banquet: {$booking->event_name}",
                    'item_type' => 'food_beverage',
                    'actor_id' => $actorId,
                ]);
            }

            $booking->update(['folio_id' => $folioId]);

            return $folioId;
        });
    }

    private function guardSpace(Property $property, int $spaceId): EventSpace
    {
        $space = EventSpace::where('id', $spaceId)
            ->where('property_id', $property->id)
            ->first();

        if ($space === null) {
            throw new \DomainException('Event space not found in this property.');
        }

        if (! $space->is_active) {
            throw new \DomainException('Event space is not active.');
        }

        return $space;
    }

    private function guardBooking(Property $property, int $bookingId): EventBooking
    {
        $booking = EventBooking::where('id', $bookingId)
            ->where('property_id', $property->id)
            ->first();

        if ($booking === null) {
            throw new \DomainException('Event booking not found in this property.');
        }

        return $booking;
    }
}
