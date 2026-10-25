<?php

namespace Tests\Feature\FrontDesk;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\FrontDeskService;
use App\Services\ReservationService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FO-001-T03: Arrivals/in-house/departures scoped by property+date,
 * and authorized check-in/check-out through FrontDeskService.
 */
class FrontDeskServiceTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private Property $otherProperty;

    private int $guestId;

    private int $guestId2;

    private int $roomId;

    private int $roomId2;

    private int $roomId3;

    private int $roomTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create(['name' => 'Front Desk Hotel']);
        $this->otherProperty = Property::factory()->create(['name' => 'Other Hotel']);

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id, 'display_name' => 'Guest One',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guestId2 = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id, 'display_name' => 'Guest Two',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id, 'name' => 'Standard', 'code' => 'STD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '401', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomId2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '402', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomId3 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '403', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'name' => 'Standard Rate', 'code' => 'STD',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00', 'is_active' => true,
        ]);
    }

    // --- Arrivals ---

    public function test_arrivals_returns_todays_confirmed_reservations(): void
    {
        $booking = new BookingService;
        $today = Carbon::parse('2027-10-15');

        // Arriving today
        $res1 = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        // Arriving tomorrow (not in today's arrivals)
        $booking->book($this->property, $this->guestId2, $this->roomId2, $this->roomTypeId,
            $today->copy()->addDay(), $today->copy()->addDays(3), 1);

        $svc = new FrontDeskService;
        $arrivals = $svc->arrivals($this->property, $today);

        $this->assertCount(1, $arrivals);
        $this->assertSame($res1, (int) $arrivals[0]->id);
    }

    public function test_arrivals_excludes_cancelled(): void
    {
        $booking = new BookingService;
        $today = Carbon::parse('2027-10-20');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);

        (new ReservationService)->transition($res, $this->property, 'cancelled', 1, 'Changed mind');

        $svc = new FrontDeskService;
        $this->assertCount(0, $svc->arrivals($this->property, $today));
    }

    public function test_arrivals_scoped_by_property(): void
    {
        $booking = new BookingService;
        $today = Carbon::parse('2027-10-25');

        $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);

        $svc = new FrontDeskService;
        $this->assertCount(0, $svc->arrivals($this->otherProperty, $today));
    }

    // --- In-house ---

    public function test_in_house_returns_active_stays(): void
    {
        $booking = new BookingService;
        $stay = new StayService;
        $today = Carbon::parse('2027-11-01');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(3), 1);
        $stay->checkIn($res, $this->property, 1);

        $svc = new FrontDeskService;
        $inHouse = $svc->inHouse($this->property);

        $this->assertCount(1, $inHouse);
        $this->assertSame('in_house', $inHouse[0]->status);
    }

    public function test_in_house_excludes_checked_out(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2027-11-05');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        $stayId = $staySvc->checkIn($res, $this->property, 1);
        $staySvc->checkOut($stayId, $this->property, 1);

        $svc = new FrontDeskService;
        $this->assertCount(0, $svc->inHouse($this->property));
    }

    public function test_in_house_scoped_by_property(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2027-11-10');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        $staySvc->checkIn($res, $this->property, 1);

        $svc = new FrontDeskService;
        $this->assertCount(0, $svc->inHouse($this->otherProperty));
    }

    // --- Departures ---

    public function test_departures_returns_todays_expected_checkouts(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $checkIn = Carbon::parse('2027-12-01');
        $checkOut = Carbon::parse('2027-12-03');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $checkIn, $checkOut, 1);
        $staySvc->checkIn($res, $this->property, 1);

        $svc = new FrontDeskService;
        $departures = $svc->departures($this->property, $checkOut);

        $this->assertCount(1, $departures);
    }

    public function test_departures_excludes_already_checked_out(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $checkIn = Carbon::parse('2027-12-05');
        $checkOut = Carbon::parse('2027-12-07');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $checkIn, $checkOut, 1);
        $stayId = $staySvc->checkIn($res, $this->property, 1);
        $staySvc->checkOut($stayId, $this->property, 1);

        $svc = new FrontDeskService;
        $this->assertCount(0, $svc->departures($this->property, $checkOut));
    }

    // --- Authorized check-in/out via FrontDeskService ---

    public function test_authorized_check_in_creates_stay(): void
    {
        $booking = new BookingService;
        $today = Carbon::parse('2028-01-10');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);

        $svc = new FrontDeskService;
        $stayId = $svc->performCheckIn($res, $this->property, 1);

        $this->assertGreaterThan(0, $stayId);
        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('in_house', $stay->status);
    }

    public function test_authorized_check_out_completes_stay(): void
    {
        $booking = new BookingService;
        $today = Carbon::parse('2028-01-15');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);

        $svc = new FrontDeskService;
        $stayId = $svc->performCheckIn($res, $this->property, 1);
        $svc->performCheckOut($stayId, $this->property, 1);

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('checked_out', $stay->status);
    }

    // --- Occupancy summary ---

    public function test_occupancy_summary_counts(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-02-01');

        // 2 bookings, 1 checked in
        $res1 = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        $booking->book($this->property, $this->guestId2, $this->roomId2, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        $staySvc->checkIn($res1, $this->property, 1);

        $svc = new FrontDeskService;
        $summary = $svc->occupancySummary($this->property, $today);

        $this->assertSame(3, $summary['total_rooms']); // 401, 402, 403
        $this->assertSame(1, $summary['occupied']);
        $this->assertSame(2, $summary['available']);
        $this->assertSame(1, $summary['arrivals_today']); // res2 still confirmed; res1 already checked in
        $this->assertSame(0, $summary['departures_today']);
    }
}
