<?php

namespace Tests\Feature\Reservations;

use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\RateQuoteService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RES-001-T04: Integration tests — full lifecycle, tenant isolation,
 * concurrent booking, snapshot immutability, overbooking prevention.
 */
class ReservationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Property $propA;

    private Property $propB;

    private int $guestA;

    private int $guestB;

    private int $roomA1;

    private int $roomA2;

    private int $roomB1;

    private int $roomTypeA;

    private int $roomTypeB;

    protected function setUp(): void
    {
        parent::setUp();

        // Property A
        $this->propA = Property::factory()->create(['name' => 'Hotel Alpha']);
        $this->roomTypeA = DB::table('room_types')->insertGetId([
            'property_id' => $this->propA->id, 'name' => 'Standard', 'code' => 'STD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomA1 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propA->id, 'room_type_id' => $this->roomTypeA,
            'code' => 'A101', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomA2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propA->id, 'room_type_id' => $this->roomTypeA,
            'code' => 'A102', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guestA = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest Alpha',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propA->id, 'room_type_id' => $this->roomTypeA,
            'name' => 'Alpha Rate', 'code' => 'ALPHA',
            'base_price_per_night' => 500000, 'tax_percentage' => '10.00', 'is_active' => true,
        ]);

        // Property B
        $this->propB = Property::factory()->create(['name' => 'Hotel Beta']);
        $this->roomTypeB = DB::table('room_types')->insertGetId([
            'property_id' => $this->propB->id, 'name' => 'Suite', 'code' => 'STE',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomB1 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propB->id, 'room_type_id' => $this->roomTypeB,
            'code' => 'B201', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guestB = DB::table('guests')->insertGetId([
            'property_id' => $this->propB->id, 'display_name' => 'Guest Beta',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propB->id, 'room_type_id' => $this->roomTypeB,
            'name' => 'Beta Rate', 'code' => 'BETA',
            'base_price_per_night' => 1200000, 'tax_percentage' => '10.00', 'is_active' => true,
        ]);
    }

    // --- Full lifecycle end-to-end ---

    public function test_full_lifecycle_book_checkin_checkout(): void
    {
        $booking = new BookingService;

        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2027-08-01'), Carbon::parse('2027-08-04'), 1, 'online',
        );

        $res = Reservation::find($resId);
        $this->assertSame('confirmed', $res->status);

        // Check in
        $resSvc = new ReservationService;
        $resSvc->transition($resId, $this->propA, 'checked_in', 1);
        $res->refresh();
        $this->assertSame('checked_in', $res->status);

        // Check out
        $resSvc->transition($resId, $this->propA, 'checked_out', 1);
        $res->refresh();
        $this->assertSame('checked_out', $res->status);

        // Audit trail complete
        $events = $res->events;
        $this->assertCount(3, $events);
        $this->assertSame('confirmed', $events[0]->to_status);
        $this->assertSame('checked_in', $events[1]->to_status);
        $this->assertSame('checked_out', $events[2]->to_status);
    }

    public function test_full_lifecycle_book_cancel_with_reason(): void
    {
        $booking = new BookingService;
        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2027-08-10'), Carbon::parse('2027-08-12'), 1,
        );

        $resSvc = new ReservationService;
        $resSvc->transition($resId, $this->propA, 'cancelled', 1, 'Guest changed plans');

        $res = Reservation::find($resId);
        $this->assertSame('cancelled', $res->status);

        // Room freed
        $avail = new AvailabilityService;
        $this->assertTrue($avail->isRoomAvailable($this->roomA1, Carbon::parse('2027-08-10'), Carbon::parse('2027-08-12')));

        // Cancel reason recorded
        $cancelEvent = DB::table('reservation_events')
            ->where('reservation_id', $resId)
            ->where('to_status', 'cancelled')
            ->first();
        $this->assertSame('Guest changed plans', $cancelEvent->reason);
    }

    public function test_full_lifecycle_no_show(): void
    {
        $booking = new BookingService;
        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2027-09-01'), Carbon::parse('2027-09-03'), 1,
        );

        $resSvc = new ReservationService;
        $resSvc->transition($resId, $this->propA, 'no_show', 1);

        $res = Reservation::find($resId);
        $this->assertSame('no_show', $res->status);

        // Room freed
        $avail = new AvailabilityService;
        $this->assertTrue($avail->isRoomAvailable($this->roomA1, Carbon::parse('2027-09-01'), Carbon::parse('2027-09-03')));
    }

    public function test_full_lifecycle_walk_in(): void
    {
        $booking = new BookingService;

        $resId = $booking->walkInBook(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2027-10-01'), Carbon::parse('2027-10-03'), 1,
        );

        $res = Reservation::find($resId);
        $this->assertSame('checked_in', $res->status);
        $this->assertTrue($res->is_walk_in);
        $this->assertSame('walk_in', $res->source);

        // Can check out
        $resSvc = new ReservationService;
        $resSvc->transition($resId, $this->propA, 'checked_out', 1);
        $res->refresh();
        $this->assertSame('checked_out', $res->status);
    }

    // --- Tenant boundary isolation ---

    public function test_tenant_isolation_booking_cannot_use_cross_property_room(): void
    {
        $booking = new BookingService;

        // Room B201 belongs to propB; booking with propA should fail
        // because the rate plan lookup is property-scoped
        // The BookingService calls RateQuoteService which queries by property
        // Room belongs to propB but we're booking under propA
        $this->expectException(\DomainException::class);
        $booking->book(
            $this->propA, $this->guestA, $this->roomB1, $this->roomTypeA,
            Carbon::parse('2027-11-01'), Carbon::parse('2027-11-03'), 1,
        );
    }

    public function test_tenant_isolation_transition_cross_property_rejected(): void
    {
        $booking = new BookingService;
        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2027-11-05'), Carbon::parse('2027-11-07'), 1,
        );

        $resSvc = new ReservationService;
        $this->expectException(\DomainException::class);
        $resSvc->transition($resId, $this->propB, 'checked_in', 1);
    }

    public function test_tenant_isolation_availability_per_property(): void
    {
        $booking = new BookingService;

        // Book in propA
        $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2027-12-01'), Carbon::parse('2027-12-03'), 1,
        );

        // propB available rooms for propA's room type shouldn't return propA rooms
        $avail = new AvailabilityService;
        $rooms = $avail->availableRooms($this->propB, $this->roomTypeA, Carbon::parse('2027-12-01'), Carbon::parse('2027-12-03'));
        $this->assertCount(0, $rooms); // No rooms of that type in propB
    }

    // --- Overbooking prevention ---

    public function test_no_overbooking_same_room_same_dates(): void
    {
        $booking = new BookingService;

        $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-01-10'), Carbon::parse('2028-01-12'), 1,
        );

        $guest2 = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest Two',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not available');
        $booking->book(
            $this->propA, $guest2, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-01-10'), Carbon::parse('2028-01-12'), 1,
        );
    }

    public function test_no_overbooking_partial_overlap(): void
    {
        $booking = new BookingService;

        $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-02-05'), Carbon::parse('2028-02-10'), 1,
        );

        $guest2 = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest Two',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $booking->book(
            $this->propA, $guest2, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-02-08'), Carbon::parse('2028-02-12'), 1,
        );
    }

    public function test_same_day_turnover_allowed(): void
    {
        $booking = new BookingService;

        // Booking 1: Jan 20-22
        $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-03-20'), Carbon::parse('2028-03-22'), 1,
        );

        $guest2 = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest Two',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Booking 2: Jan 22-24 — adjacent, should succeed
        $resId = $booking->book(
            $this->propA, $guest2, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-03-22'), Carbon::parse('2028-03-24'), 1,
        );

        $this->assertGreaterThan(0, $resId);
    }

    // --- Snapshot immutability ---

    public function test_snapshot_unchanged_after_rate_master_update(): void
    {
        $booking = new BookingService;

        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-04-01'), Carbon::parse('2028-04-03'), 1,
        );

        $res = DB::table('reservations')->where('id', $resId)->first();
        $snapBefore = DB::table('rate_snapshots')->where('id', $res->rate_snapshot_id)->first();

        // Change master rate
        RatePlan::where('property_id', $this->propA->id)->update([
            'base_price_per_night' => 999999,
        ]);

        $snapAfter = DB::table('rate_snapshots')->where('id', $res->rate_snapshot_id)->first();

        $this->assertSame((int) $snapBefore->rate_per_night, (int) $snapAfter->rate_per_night);
        $this->assertSame((int) $snapBefore->total, (int) $snapAfter->total);
        $this->assertSame(500000, (int) $snapAfter->rate_per_night);
    }

    // --- Historical changes (room reassignment + audit) ---

    public function test_room_reassignment_preserves_full_history(): void
    {
        $booking = new BookingService;
        $resSvc = new ReservationService;

        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-05-01'), Carbon::parse('2028-05-03'), 1,
        );

        // Reassign twice
        $resSvc->reassignRoom($resId, $this->propA, $this->roomA2, 1, 'Upgrade');
        $resSvc->reassignRoom($resId, $this->propA, $this->roomA1, 1, 'Guest preference');

        $changes = DB::table('reservation_events')
            ->where('reservation_id', $resId)
            ->where('to_status', 'room_changed')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $changes);

        $first = json_decode($changes[0]->changes, true);
        $this->assertSame($this->roomA1, $first['room_id']['from']);
        $this->assertSame($this->roomA2, $first['room_id']['to']);
        $this->assertSame('Upgrade', $changes[0]->reason);

        $second = json_decode($changes[1]->changes, true);
        $this->assertSame($this->roomA2, $second['room_id']['from']);
        $this->assertSame($this->roomA1, $second['room_id']['to']);
    }

    // --- Multiple rooms capacity ---

    public function test_multiple_rooms_booked_independently(): void
    {
        $booking = new BookingService;

        $guest2 = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest Two',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $res1 = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-06-01'), Carbon::parse('2028-06-03'), 1,
        );
        $res2 = $booking->book(
            $this->propA, $guest2, $this->roomA2, $this->roomTypeA,
            Carbon::parse('2028-06-01'), Carbon::parse('2028-06-03'), 1,
        );

        $this->assertNotSame($res1, $res2);

        // Both rooms now unavailable
        $avail = new AvailabilityService;
        $rooms = $avail->availableRooms($this->propA, $this->roomTypeA, Carbon::parse('2028-06-01'), Carbon::parse('2028-06-03'));
        $this->assertCount(0, $rooms);
    }

    // --- Terminal state transitions blocked ---

    public function test_checked_out_cannot_transition(): void
    {
        $booking = new BookingService;
        $resSvc = new ReservationService;

        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-07-01'), Carbon::parse('2028-07-03'), 1,
        );
        $resSvc->transition($resId, $this->propA, 'checked_in', 1);
        $resSvc->transition($resId, $this->propA, 'checked_out', 1);

        $this->expectException(\DomainException::class);
        $resSvc->transition($resId, $this->propA, 'checked_in', 1);
    }

    public function test_cancelled_cannot_transition(): void
    {
        $booking = new BookingService;
        $resSvc = new ReservationService;

        $resId = $booking->book(
            $this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-07-10'), Carbon::parse('2028-07-12'), 1,
        );
        $resSvc->transition($resId, $this->propA, 'cancelled', 1, 'Test');

        $this->expectException(\DomainException::class);
        $resSvc->transition($resId, $this->propA, 'confirmed', 1);
    }
}
