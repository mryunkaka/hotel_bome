<?php

namespace Tests\Feature\FrontDesk;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\FrontDeskService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FO-001-T05/T06: Property denial, occupancy race, stay integrity,
 * HK handoff signal, and end-to-end reservation-to-checkout integration.
 */
class FrontDeskIntegrationTest extends TestCase
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

        $this->propA = Property::factory()->create(['name' => 'Hotel A']);
        $this->propB = Property::factory()->create(['name' => 'Hotel B']);

        $this->guestA = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest A',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guestB = DB::table('guests')->insertGetId([
            'property_id' => $this->propB->id, 'display_name' => 'Guest B',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomTypeA = DB::table('room_types')->insertGetId([
            'property_id' => $this->propA->id, 'name' => 'Std', 'code' => 'S',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomTypeB = DB::table('room_types')->insertGetId([
            'property_id' => $this->propB->id, 'name' => 'Std', 'code' => 'S',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomA1 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propA->id, 'room_type_id' => $this->roomTypeA,
            'code' => 'A1', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomA2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propA->id, 'room_type_id' => $this->roomTypeA,
            'code' => 'A2', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomB1 = DB::table('rooms')->insertGetId([
            'property_id' => $this->propB->id, 'room_type_id' => $this->roomTypeB,
            'code' => 'B1', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->propA->id, 'room_type_id' => $this->roomTypeA,
            'name' => 'A Rate', 'code' => 'AR',
            'base_price_per_night' => 500000, 'tax_percentage' => '10.00', 'is_active' => true,
        ]);
        RatePlan::forceCreate([
            'property_id' => $this->propB->id, 'room_type_id' => $this->roomTypeB,
            'name' => 'B Rate', 'code' => 'BR',
            'base_price_per_night' => 800000, 'tax_percentage' => '10.00', 'is_active' => true,
        ]);
    }

    // --- Property denial ---

    public function test_check_in_cross_property_reservation_rejected(): void
    {
        $booking = new BookingService;
        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-05-01'), Carbon::parse('2028-05-03'), 1);

        $stay = new StayService;
        $this->expectException(\DomainException::class);
        $stay->checkIn($res, $this->propB, 1);
    }

    public function test_check_out_cross_property_stay_rejected(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;

        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-05-05'), Carbon::parse('2028-05-07'), 1);
        $stayId = $staySvc->checkIn($res, $this->propA, 1);

        $this->expectException(\DomainException::class);
        $staySvc->checkOut($stayId, $this->propB, 1);
    }

    public function test_room_move_cross_property_stay_rejected(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;

        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            Carbon::parse('2028-05-10'), Carbon::parse('2028-05-12'), 1);
        $stayId = $staySvc->checkIn($res, $this->propA, 1);

        $this->expectException(\DomainException::class);
        $staySvc->moveRoom($stayId, $this->propB, $this->roomB1, 1);
    }

    // --- Occupancy race ---

    public function test_double_booking_same_room_prevented(): void
    {
        $booking = new BookingService;
        $dates = [Carbon::parse('2028-06-01'), Carbon::parse('2028-06-03')];

        $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $dates[0], $dates[1], 1);

        $guest2 = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Racer',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $booking->book($this->propA, $guest2, $this->roomA1, $this->roomTypeA,
            $dates[0], $dates[1], 1);
    }

    public function test_move_to_occupied_room_prevented(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-06-10');

        $res1 = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $today, $today->copy()->addDays(3), 1);
        $stay1 = $staySvc->checkIn($res1, $this->propA, 1);

        $guest2 = DB::table('guests')->insertGetId([
            'property_id' => $this->propA->id, 'display_name' => 'Guest 2',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $res2 = $booking->book($this->propA, $guest2, $this->roomA2, $this->roomTypeA,
            $today, $today->copy()->addDays(3), 1);
        $staySvc->checkIn($res2, $this->propA, 1);

        // Try to move stay1 into room A2 (occupied)
        $this->expectException(\DomainException::class);
        $staySvc->moveRoom($stay1, $this->propA, $this->roomA2, 1);
    }

    // --- Stay integrity ---

    public function test_stay_links_to_reservation_and_snapshot(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-07-01');

        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $today, $today->copy()->addDays(2), 1, 'phone');
        $stayId = $staySvc->checkIn($res, $this->propA, 1);

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $reservation = DB::table('reservations')->where('id', $stay->reservation_id)->first();
        $snapshot = DB::table('rate_snapshots')->where('id', $reservation->rate_snapshot_id)->first();

        // Stay → Reservation → Snapshot chain intact
        $this->assertSame($res, (int) $stay->reservation_id);
        $this->assertSame('phone', $reservation->source);
        $this->assertSame(500000, (int) $snapshot->rate_per_night);
        $this->assertSame(2, (int) $snapshot->nights);
    }

    public function test_checkout_preserves_all_history(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-07-05');

        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $today, $today->copy()->addDays(3), 1);
        $stayId = $staySvc->checkIn($res, $this->propA, 1);
        $staySvc->moveRoom($stayId, $this->propA, $this->roomA2, 1, 'Noise complaint');
        $staySvc->checkOut($stayId, $this->propA, 1);

        // Stay record preserved
        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('checked_out', $stay->status);
        $this->assertNotNull($stay->checked_in_at);
        $this->assertNotNull($stay->checked_out_at);

        // Reservation record preserved
        $reservation = DB::table('reservations')->where('id', $res)->first();
        $this->assertSame('checked_out', $reservation->status);

        // All events preserved
        $stayEvents = DB::table('stay_events')->where('stay_id', $stayId)->orderBy('id')->get();
        $this->assertCount(3, $stayEvents); // check_in, room_move, check_out
        $this->assertSame('check_in', $stayEvents[0]->event_type);
        $this->assertSame('room_move', $stayEvents[1]->event_type);
        $this->assertSame('check_out', $stayEvents[2]->event_type);

        $resEvents = DB::table('reservation_events')->where('reservation_id', $res)->orderBy('id')->get();
        $this->assertGreaterThanOrEqual(3, $resEvents->count()); // confirmed, checked_in, checked_out
    }

    // --- HK handoff signal ---

    public function test_checkout_event_serves_as_hk_handoff(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-07-10');

        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $today, $today->copy()->addDays(2), 1);
        $stayId = $staySvc->checkIn($res, $this->propA, 1);
        $staySvc->checkOut($stayId, $this->propA, 1);

        // The check_out event on stay_events is the HK handoff signal
        $event = DB::table('stay_events')
            ->where('stay_id', $stayId)
            ->where('event_type', 'check_out')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame($this->roomA1, (int) $event->room_id);
        // ponytail: actual HK dirty-trigger wires here when HK integration built
    }

    // --- T06: Full end-to-end reservation-to-checkout ---

    public function test_end_to_end_reservation_to_checkout(): void
    {
        $fd = new FrontDeskService;
        $booking = new BookingService;
        $staySvc = new StayService;
        $checkIn = Carbon::parse('2028-08-01');
        $checkOut = Carbon::parse('2028-08-04');

        // 1. Book
        $res = $booking->book($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $checkIn, $checkOut, 1, 'online');

        // 2. Appears in arrivals
        $arrivals = $fd->arrivals($this->propA, $checkIn);
        $this->assertCount(1, $arrivals);
        $this->assertSame($res, (int) $arrivals[0]->id);

        // 3. Check in
        $stayId = $fd->performCheckIn($res, $this->propA, 1);

        // 4. Disappears from arrivals, appears in in-house
        $this->assertCount(0, $fd->arrivals($this->propA, $checkIn));
        $inHouse = $fd->inHouse($this->propA);
        $this->assertCount(1, $inHouse);

        // 5. Appears in departures on checkout date
        $departures = $fd->departures($this->propA, $checkOut);
        $this->assertCount(1, $departures);

        // 6. Room move during stay
        $staySvc->moveRoom($stayId, $this->propA, $this->roomA2, 1, 'Guest request');

        // 7. Check out
        $fd->performCheckOut($stayId, $this->propA, 1);

        // 8. Disappears from in-house and departures
        $this->assertCount(0, $fd->inHouse($this->propA));
        $this->assertCount(0, $fd->departures($this->propA, $checkOut));

        // 9. Occupancy freed
        $summary = $fd->occupancySummary($this->propA, $checkOut);
        $this->assertSame(0, $summary['occupied']);

        // 10. Complete audit trail
        $stayEvents = DB::table('stay_events')->where('stay_id', $stayId)->orderBy('id')->get();
        $this->assertCount(3, $stayEvents);
        $resEvents = DB::table('reservation_events')->where('reservation_id', $res)->orderBy('id')->get();
        $this->assertGreaterThanOrEqual(3, $resEvents->count());

        // 11. Snapshot immutable
        $reservation = DB::table('reservations')->where('id', $res)->first();
        $snap = DB::table('rate_snapshots')->where('id', $reservation->rate_snapshot_id)->first();
        $this->assertSame(500000, (int) $snap->rate_per_night);
        $this->assertSame(3, (int) $snap->nights);
    }

    public function test_end_to_end_walk_in_to_checkout(): void
    {
        $fd = new FrontDeskService;
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-08-10');

        // Walk-in book
        $res = $booking->walkInBook($this->propA, $this->guestA, $this->roomA1, $this->roomTypeA,
            $today, $today->copy()->addDays(2), 1);

        // Walk-in creates stay
        $stayId = $staySvc->checkInWalkIn($res, $this->propA, 1);

        // In house
        $this->assertCount(1, $fd->inHouse($this->propA));

        // Check out
        $fd->performCheckOut($stayId, $this->propA, 1);
        $this->assertCount(0, $fd->inHouse($this->propA));

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('checked_out', $stay->status);
    }
}
