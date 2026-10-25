<?php

namespace Tests\Feature\FrontDesk;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\BookingService;
use App\Services\StayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FO-001-T04: Room move with transactional revalidation and history.
 * Room share is deferred (D-005 has no share policy).
 */
class RoomMoveTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $guestId2;

    private int $roomId;

    private int $roomId2;

    private int $roomId3;

    private int $roomTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create();

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id, 'display_name' => 'Move Guest',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->guestId2 = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id, 'display_name' => 'Other Guest',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id, 'name' => 'Standard', 'code' => 'STD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '501', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomId2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '502', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomId3 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '503', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'name' => 'Rate', 'code' => 'R',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00', 'is_active' => true,
        ]);
    }

    public function test_room_move_checks_target_availability(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-03-01');

        // Guest 1 in room 501
        $res1 = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(3), 1);
        $stay1 = $staySvc->checkIn($res1, $this->property, 1);

        // Guest 2 in room 502
        $res2 = $booking->book($this->property, $this->guestId2, $this->roomId2, $this->roomTypeId,
            $today, $today->copy()->addDays(3), 1);
        $staySvc->checkIn($res2, $this->property, 1);

        // Try to move guest 1 to room 502 (occupied) — should fail
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('occupied');
        $staySvc->moveRoom($stay1, $this->property, $this->roomId2, 1, 'Upgrade attempt');
    }

    public function test_room_move_to_vacant_room_succeeds(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-03-10');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(3), 1);
        $stay = $staySvc->checkIn($res, $this->property, 1);

        // Move to vacant room 502
        $staySvc->moveRoom($stay, $this->property, $this->roomId2, 1, 'Upgrade');

        $stayRow = DB::table('stays')->where('id', $stay)->first();
        $this->assertSame($this->roomId2, (int) $stayRow->room_id);
    }

    public function test_room_move_to_inactive_room_rejected(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-03-15');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        $stay = $staySvc->checkIn($res, $this->property, 1);

        // Deactivate target
        DB::table('rooms')->where('id', $this->roomId2)->update(['is_active' => false]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not active');
        $staySvc->moveRoom($stay, $this->property, $this->roomId2, 1);
    }

    public function test_room_move_cross_property_room_rejected(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-03-20');
        $other = Property::factory()->create();

        $otherRoomType = DB::table('room_types')->insertGetId([
            'property_id' => $other->id, 'name' => 'X', 'code' => 'X',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherRoom = DB::table('rooms')->insertGetId([
            'property_id' => $other->id, 'room_type_id' => $otherRoomType,
            'code' => 'X01', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(2), 1);
        $stay = $staySvc->checkIn($res, $this->property, 1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not belong');
        $staySvc->moveRoom($stay, $this->property, $otherRoom, 1);
    }

    public function test_multiple_moves_preserve_full_history(): void
    {
        $booking = new BookingService;
        $staySvc = new StayService;
        $today = Carbon::parse('2028-04-01');

        $res = $booking->book($this->property, $this->guestId, $this->roomId, $this->roomTypeId,
            $today, $today->copy()->addDays(5), 1);
        $stay = $staySvc->checkIn($res, $this->property, 1);

        $staySvc->moveRoom($stay, $this->property, $this->roomId2, 1, 'First move');
        $staySvc->moveRoom($stay, $this->property, $this->roomId3, 1, 'Second move');

        $events = DB::table('stay_events')
            ->where('stay_id', $stay)
            ->where('event_type', 'room_move')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $events);

        $first = json_decode($events[0]->changes, true);
        $this->assertSame($this->roomId, $first['from_room']);
        $this->assertSame($this->roomId2, $first['to_room']);

        $second = json_decode($events[1]->changes, true);
        $this->assertSame($this->roomId2, $second['from_room']);
        $this->assertSame($this->roomId3, $second['to_room']);

        // Current room is 503
        $stayRow = DB::table('stays')->where('id', $stay)->first();
        $this->assertSame($this->roomId3, (int) $stayRow->room_id);
    }
}
