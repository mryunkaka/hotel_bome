<?php

namespace Tests\Feature\Events;

use App\Models\Property;
use App\Services\Events\EventService;
use App\Services\Finance\FolioService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * EVT-001: Event Spaces, BEO Bookings, Conflict Detection, and Folio Integration.
 */
class EventBookingTest extends TestCase
{
    use RefreshDatabase;

    private Property $propertyA;

    private Property $propertyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyA = Property::factory()->create(['name' => 'Event Grand Hotel']);
        $this->propertyB = Property::factory()->create(['name' => 'Event Resort Beta']);
    }

    public function test_event_space_creation_and_property_isolation(): void
    {
        $svc = new EventService;

        $spaceIdA = $svc->createSpace($this->propertyA, [
            'code' => 'BALLROOM-1',
            'name' => 'Grand Ballroom',
            'capacity' => 500,
            'hourly_rate' => 2000000,
            'day_rate' => 15000000,
        ]);

        $this->assertGreaterThan(0, $spaceIdA);

        $spaceA = DB::table('event_spaces')->where('id', $spaceIdA)->first();
        $this->assertSame($this->propertyA->id, (int) $spaceA->property_id);
        $this->assertSame('BALLROOM-1', $spaceA->code);
        $this->assertSame(500, (int) $spaceA->capacity);

        // Cross-property access rejected
        $this->expectException(\DomainException::class);
        $svc->bookEvent($this->propertyB, $spaceIdA, [
            'event_name' => 'Cross Property Conference',
            'organizer_name' => 'PT Test',
            'start_time' => '2028-12-01 09:00:00',
            'end_time' => '2028-12-01 17:00:00',
            'rental_amount' => 15000000,
            'actor_id' => 1,
        ]);
    }

    public function test_event_booking_confirmed_and_space_availability(): void
    {
        $svc = new EventService;

        $spaceId = $svc->createSpace($this->propertyA, [
            'code' => 'MEET-01',
            'name' => 'Meeting Room Lotus',
            'capacity' => 30,
            'hourly_rate' => 300000,
            'day_rate' => 2500000,
        ]);

        $start = Carbon::parse('2028-12-05 08:00:00');
        $end = Carbon::parse('2028-12-05 12:00:00');

        $this->assertTrue($svc->isSpaceAvailable($this->propertyA, $spaceId, $start, $end));

        $bookingId = $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Quarterly Review Meeting',
            'organizer_name' => 'PT Maju Bersama',
            'start_time' => $start->toDateTimeString(),
            'end_time' => $end->toDateTimeString(),
            'setup_style' => 'u_shape',
            'expected_attendees' => 20,
            'rental_amount' => 1200000,
            'actor_id' => 1,
        ]);

        $this->assertGreaterThan(0, $bookingId);

        $booking = DB::table('event_bookings')->where('id', $bookingId)->first();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame(1200000, (int) $booking->rental_amount);

        // Space is now unavailable during that time
        $this->assertFalse($svc->isSpaceAvailable($this->propertyA, $spaceId, $start, $end));
    }

    public function test_overlapping_event_booking_rejected(): void
    {
        $svc = new EventService;

        $spaceId = $svc->createSpace($this->propertyA, [
            'code' => 'HALL-A',
            'name' => 'Exhibition Hall A',
            'capacity' => 200,
            'hourly_rate' => 1000000,
            'day_rate' => 8000000,
        ]);

        // Booking 1: 09:00 - 13:00
        $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Morning Seminar',
            'organizer_name' => 'Organisasi A',
            'start_time' => '2028-12-10 09:00:00',
            'end_time' => '2028-12-10 13:00:00',
            'rental_amount' => 4000000,
            'actor_id' => 1,
        ]);

        // Booking 2: Overlapping 11:00 - 15:00 on same space
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Event space is already booked');
        $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Conflicting Event',
            'organizer_name' => 'Organisasi B',
            'start_time' => '2028-12-10 11:00:00',
            'end_time' => '2028-12-10 15:00:00',
            'rental_amount' => 4000000,
            'actor_id' => 1,
        ]);
    }

    public function test_adjacent_event_booking_allowed(): void
    {
        $svc = new EventService;

        $spaceId = $svc->createSpace($this->propertyA, [
            'code' => 'MEET-02',
            'name' => 'Meeting Room Melati',
            'capacity' => 25,
            'hourly_rate' => 250000,
            'day_rate' => 2000000,
        ]);

        // Event 1: 08:00 - 12:00
        $b1 = $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Morning Session',
            'organizer_name' => 'Client 1',
            'start_time' => '2028-12-15 08:00:00',
            'end_time' => '2028-12-15 12:00:00',
            'rental_amount' => 1000000,
            'actor_id' => 1,
        ]);

        // Event 2: 12:00 - 16:00 (exact adjacent boundary)
        $b2 = $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Afternoon Session',
            'organizer_name' => 'Client 2',
            'start_time' => '2028-12-15 12:00:00',
            'end_time' => '2028-12-15 16:00:00',
            'rental_amount' => 1000000,
            'actor_id' => 1,
        ]);

        $this->assertGreaterThan(0, $b1);
        $this->assertGreaterThan(0, $b2);
    }

    public function test_cancelled_event_releases_space(): void
    {
        $svc = new EventService;

        $spaceId = $svc->createSpace($this->propertyA, [
            'code' => 'MEET-03',
            'name' => 'Meeting Room Mawar',
            'capacity' => 40,
            'hourly_rate' => 350000,
            'day_rate' => 3000000,
        ]);

        $start = '2028-12-20 09:00:00';
        $end = '2028-12-20 17:00:00';

        $bookingId = $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Full Day Workshop',
            'organizer_name' => 'Workshop Hub',
            'start_time' => $start,
            'end_time' => $end,
            'rental_amount' => 2800000,
            'actor_id' => 1,
        ]);

        // Cancel the booking
        $svc->cancelEvent($this->propertyA, $bookingId, 'Client requested cancellation', 1);

        $booking = DB::table('event_bookings')->where('id', $bookingId)->first();
        $this->assertSame('cancelled', $booking->status);

        // Space is now available again for that exact time
        $this->assertTrue($svc->isSpaceAvailable($this->propertyA, $spaceId, Carbon::parse($start), Carbon::parse($end)));

        // New booking can now be confirmed
        $newBookingId = $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Replacement Training',
            'organizer_name' => 'Training Corp',
            'start_time' => $start,
            'end_time' => $end,
            'rental_amount' => 2800000,
            'actor_id' => 1,
        ]);
        $this->assertGreaterThan(0, $newBookingId);
    }

    public function test_event_folio_billing_integration(): void
    {
        $svc = new EventService;

        $spaceId = $svc->createSpace($this->propertyA, [
            'code' => 'BALLROOM-2',
            'name' => 'Diamond Ballroom',
            'capacity' => 600,
            'hourly_rate' => 2500000,
            'day_rate' => 20000000,
        ]);

        $bookingId = $svc->bookEvent($this->propertyA, $spaceId, [
            'event_name' => 'Annual Corporate Gala Dinner',
            'organizer_name' => 'Gala Organizers Ltd',
            'start_time' => '2028-12-25 18:00:00',
            'end_time' => '2028-12-25 23:00:00',
            'rental_amount' => 12500000,
            'catering_amount' => 25000000,
            'actor_id' => 1,
        ]);

        // Create dedicated folio and post rental & catering charges
        $folioId = $svc->createFolioForEvent($this->propertyA, $bookingId, 1);
        $this->assertGreaterThan(0, $folioId);

        $folioSvc = new FolioService;
        $balance = $folioSvc->calculateBalance($folioId, $this->propertyA);

        // Total charges: 12.5M rental + 25M catering = 37.5M
        $this->assertSame(37500000, $balance['total_charges']);
        $this->assertSame(37500000, $balance['balance']);

        // Check booking updated with folio_id
        $booking = DB::table('event_bookings')->where('id', $bookingId)->first();
        $this->assertSame($folioId, (int) $booking->folio_id);
    }
}
