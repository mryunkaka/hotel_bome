<?php

namespace Tests\Feature\Reservations;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\RateQuoteService;
use App\Services\RateSnapshotService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $roomId;

    private int $roomId2;

    private int $roomTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create();

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Booking Guest',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id, 'name' => 'Deluxe', 'code' => 'DLX',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '201', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '202', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Deluxe Rate', 'code' => 'DLX',
            'base_price_per_night' => 750000,
            'tax_percentage' => '10.00', 'is_active' => true,
        ]);
    }

    // --- Availability ---

    public function test_room_available_when_no_reservations(): void
    {
        $svc = new AvailabilityService;
        $available = $svc->isRoomAvailable(
            $this->roomId,
            Carbon::parse('2027-02-01'),
            Carbon::parse('2027-02-03'),
        );
        $this->assertTrue($available);
    }

    public function test_room_unavailable_when_overlapping_confirmed(): void
    {
        // Book room 201 for Feb 1-3
        $resSvc = new ReservationService;
        $quoteSvc = new RateQuoteService;
        $snapSvc = new RateSnapshotService;

        $quote = $quoteSvc->quote($this->property, $this->roomTypeId, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03'));
        $snapId = $snapSvc->capture($this->property, $quote);
        $resSvc->create($this->property, $this->guestId, $this->roomId, $snapId, 1);

        $svc = new AvailabilityService;

        // Exact overlap
        $this->assertFalse($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03')));
        // Partial overlap (starts during)
        $this->assertFalse($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-02-02'), Carbon::parse('2027-02-04')));
        // Partial overlap (ends during)
        $this->assertFalse($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-01-31'), Carbon::parse('2027-02-02')));

        // Adjacent — checkout date is free for new check-in (same-day turnover)
        $this->assertTrue($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-02-03'), Carbon::parse('2027-02-05')));
        // Before, no overlap
        $this->assertTrue($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-01-28'), Carbon::parse('2027-02-01')));
        // Different room always available
        $this->assertTrue($svc->isRoomAvailable($this->roomId2, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03')));
    }

    public function test_cancelled_reservation_does_not_block_room(): void
    {
        $resSvc = new ReservationService;
        $quoteSvc = new RateQuoteService;
        $snapSvc = new RateSnapshotService;

        $quote = $quoteSvc->quote($this->property, $this->roomTypeId, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03'));
        $snapId = $snapSvc->capture($this->property, $quote);
        $resId = $resSvc->create($this->property, $this->guestId, $this->roomId, $snapId, 1);
        $resSvc->transition($resId, $this->property, 'cancelled', 1, 'Guest cancelled');

        $svc = new AvailabilityService;
        $this->assertTrue($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03')));
    }

    public function test_checked_out_reservation_does_not_block_room(): void
    {
        $resSvc = new ReservationService;
        $quoteSvc = new RateQuoteService;
        $snapSvc = new RateSnapshotService;

        $quote = $quoteSvc->quote($this->property, $this->roomTypeId, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03'));
        $snapId = $snapSvc->capture($this->property, $quote);
        $resId = $resSvc->create($this->property, $this->guestId, $this->roomId, $snapId, 1);
        $resSvc->transition($resId, $this->property, 'checked_in', 1);
        $resSvc->transition($resId, $this->property, 'checked_out', 1);

        $svc = new AvailabilityService;
        $this->assertTrue($svc->isRoomAvailable($this->roomId, Carbon::parse('2027-02-01'), Carbon::parse('2027-02-03')));
    }

    // --- Available rooms list ---

    public function test_available_rooms_returns_unoccupied(): void
    {
        $resSvc = new ReservationService;
        $quoteSvc = new RateQuoteService;
        $snapSvc = new RateSnapshotService;

        $quote = $quoteSvc->quote($this->property, $this->roomTypeId, Carbon::parse('2027-03-01'), Carbon::parse('2027-03-03'));
        $snapId = $snapSvc->capture($this->property, $quote);
        // Book room 201
        $resSvc->create($this->property, $this->guestId, $this->roomId, $snapId, 1);

        $svc = new AvailabilityService;
        $available = $svc->availableRooms(
            $this->property, $this->roomTypeId,
            Carbon::parse('2027-03-01'), Carbon::parse('2027-03-03'),
        );

        $this->assertCount(1, $available);
        $this->assertSame($this->roomId2, (int) $available[0]->id);
    }

    // --- BookingService orchestration ---

    public function test_booking_service_creates_reservation_atomically(): void
    {
        $svc = new BookingService;

        $resId = $svc->book(
            property: $this->property,
            guestId: $this->guestId,
            roomId: $this->roomId,
            roomTypeId: $this->roomTypeId,
            checkIn: Carbon::parse('2027-04-01'),
            checkOut: Carbon::parse('2027-04-03'),
            actorId: 1,
            source: 'online',
        );

        $this->assertGreaterThan(0, $resId);

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('confirmed', $res->status);
        $this->assertSame('online', $res->source);
        $this->assertNotNull($res->rate_snapshot_id);

        // Snapshot exists and is immutable
        $snap = DB::table('rate_snapshots')->where('id', $res->rate_snapshot_id)->first();
        $this->assertNotNull($snap);
        $this->assertSame(750000, (int) $snap->rate_per_night);
    }

    public function test_booking_rejects_unavailable_room(): void
    {
        $svc = new BookingService;

        // First booking
        $svc->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-04-10'), Carbon::parse('2027-04-12'), 1);

        // Second booking same room overlapping dates
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not available');
        $svc->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-04-11'), Carbon::parse('2027-04-13'), 1);
    }

    public function test_booking_rejects_inactive_room(): void
    {
        // Deactivate room
        DB::table('rooms')->where('id', $this->roomId)->update(['is_active' => false]);

        $svc = new BookingService;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not active');
        $svc->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-05-01'), Carbon::parse('2027-05-03'), 1);
    }

    public function test_booking_walk_in_checks_availability(): void
    {
        $svc = new BookingService;

        // Book room
        $svc->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-06-01'), Carbon::parse('2027-06-03'), 1);

        // Walk-in same room same dates
        $this->expectException(\DomainException::class);
        $svc->walkInBook($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-06-01'), Carbon::parse('2027-06-03'), 1);
    }

    public function test_walk_in_booking_creates_checked_in(): void
    {
        $svc = new BookingService;

        $resId = $svc->walkInBook(
            $this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            Carbon::parse('2027-07-01'), Carbon::parse('2027-07-03'), 1,
        );

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('checked_in', $res->status);
        $this->assertTrue((bool) $res->is_walk_in);
        $this->assertSame('walk_in', $res->source);
    }
}
