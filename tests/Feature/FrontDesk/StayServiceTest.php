<?php

namespace Tests\Feature\FrontDesk;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\ReservationService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FO-001-T02: Stay state transitions, assignment tracking, audit linkage.
 * Uses D-005 provisional: check-in requires eligible reservation + room ready;
 * check-out triggers dirty status; history preserved.
 */
class StayServiceTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $roomId;

    private int $roomId2;

    private int $roomTypeId;

    private int $resId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create();

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Stay Guest',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id, 'name' => 'Deluxe', 'code' => 'DLX',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '301', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '302', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'name' => 'Deluxe Rate', 'code' => 'DLX',
            'base_price_per_night' => 600000,
            'tax_percentage' => '10.00', 'is_active' => true,
        ]);

        // Create a confirmed reservation
        $booking = new BookingService;
        $this->resId = $booking->book(
            $this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-08-01'), Carbon::parse('2027-08-04'), 1,
        );
    }

    // --- Check-in creates stay ---

    public function test_check_in_creates_stay_record(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame($this->resId, (int) $stay->reservation_id);
        $this->assertSame($this->property->id, (int) $stay->property_id);
        $this->assertSame($this->guestId, (int) $stay->guest_id);
        $this->assertSame($this->roomId, (int) $stay->room_id);
        $this->assertSame('in_house', $stay->status);
        $this->assertNotNull($stay->checked_in_at);
        $this->assertNull($stay->checked_out_at);
    }

    public function test_check_in_transitions_reservation_to_checked_in(): void
    {
        $svc = new StayService;
        $svc->checkIn($this->resId, $this->property, 1);

        $res = DB::table('reservations')->where('id', $this->resId)->first();
        $this->assertSame('checked_in', $res->status);
    }

    public function test_check_in_records_assignment_event(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);

        $event = DB::table('stay_events')
            ->where('stay_id', $stayId)
            ->where('event_type', 'check_in')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame($this->roomId, (int) $event->room_id);
        $this->assertSame(1, (int) $event->actor_id);
    }

    public function test_check_in_rejected_for_non_confirmed_reservation(): void
    {
        // Cancel reservation first
        $resSvc = new ReservationService;
        $resSvc->transition($this->resId, $this->property, 'cancelled', 1, 'Test cancel');

        $svc = new StayService;
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not eligible');
        $svc->checkIn($this->resId, $this->property, 1);
    }

    public function test_check_in_rejected_cross_property(): void
    {
        $other = Property::factory()->create();

        $svc = new StayService;
        $this->expectException(\DomainException::class);
        $svc->checkIn($this->resId, $other, 1);
    }

    public function test_double_check_in_rejected(): void
    {
        $svc = new StayService;
        $svc->checkIn($this->resId, $this->property, 1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not eligible');
        $svc->checkIn($this->resId, $this->property, 1);
    }

    // --- Check-out ---

    public function test_check_out_completes_stay(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);
        $svc->checkOut($stayId, $this->property, 1);

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('checked_out', $stay->status);
        $this->assertNotNull($stay->checked_out_at);
    }

    public function test_check_out_transitions_reservation(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);
        $svc->checkOut($stayId, $this->property, 1);

        $res = DB::table('reservations')->where('id', $this->resId)->first();
        $this->assertSame('checked_out', $res->status);
    }

    public function test_check_out_records_event(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);
        $svc->checkOut($stayId, $this->property, 1);

        $event = DB::table('stay_events')
            ->where('stay_id', $stayId)
            ->where('event_type', 'check_out')
            ->first();
        $this->assertNotNull($event);
    }

    public function test_check_out_rejected_if_not_in_house(): void
    {
        // Create stay but don't check out yet — try checking out a non-existent stay
        $svc = new StayService;
        $this->expectException(\DomainException::class);
        $svc->checkOut(99999, $this->property, 1);
    }

    public function test_double_check_out_rejected(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);
        $svc->checkOut($stayId, $this->property, 1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not in_house');
        $svc->checkOut($stayId, $this->property, 1);
    }

    // --- Walk-in creates stay immediately ---

    public function test_walk_in_creates_stay(): void
    {
        $booking = new BookingService;
        $walkInResId = $booking->walkInBook(
            $this->property, $this->guestId, $this->roomId2, $this->roomTypeId,
            Carbon::parse('2027-09-01'), Carbon::parse('2027-09-03'), 1,
        );

        // Walk-in reservation is already checked_in, so StayService should accept
        $svc = new StayService;
        $stayId = $svc->checkInWalkIn($walkInResId, $this->property, 1);

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame('in_house', $stay->status);
        $this->assertSame($walkInResId, (int) $stay->reservation_id);
    }

    // --- Room move ---

    public function test_room_move_updates_stay_and_records_history(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);

        $svc->moveRoom($stayId, $this->property, $this->roomId2, 1, 'Upgrade');

        $stay = DB::table('stays')->where('id', $stayId)->first();
        $this->assertSame($this->roomId2, (int) $stay->room_id);

        $event = DB::table('stay_events')
            ->where('stay_id', $stayId)
            ->where('event_type', 'room_move')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame($this->roomId, (int) $event->room_id); // old room
        $changes = json_decode($event->changes, true);
        $this->assertSame($this->roomId, $changes['from_room']);
        $this->assertSame($this->roomId2, $changes['to_room']);
        $this->assertSame('Upgrade', $event->reason);
    }

    public function test_room_move_rejected_if_checked_out(): void
    {
        $svc = new StayService;
        $stayId = $svc->checkIn($this->resId, $this->property, 1);
        $svc->checkOut($stayId, $this->property, 1);

        $this->expectException(\DomainException::class);
        $svc->moveRoom($stayId, $this->property, $this->roomId2, 1);
    }
}
