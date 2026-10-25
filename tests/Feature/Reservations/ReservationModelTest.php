<?php

namespace Tests\Feature\Reservations;

use App\Models\Guest;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Services\RateQuoteService;
use App\Services\RateSnapshotService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationModelTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private int $guestId;

    private int $roomId;

    private int $roomId2;

    private int $snapshotId;

    private int $roomTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->property = Property::factory()->create();

        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Test Guest',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id, 'name' => 'Standard', 'code' => 'STD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '101', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roomId2 = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $this->roomTypeId,
            'code' => '102', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        RatePlan::forceCreate([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomTypeId,
            'name' => 'Standard Rate', 'code' => 'STD',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00', 'is_active' => true,
        ]);

        $quote = (new RateQuoteService)->quote(
            $this->property, $this->roomTypeId,
            Carbon::parse('2027-01-10'), Carbon::parse('2027-01-13'),
        );
        $this->snapshotId = (new RateSnapshotService)->capture($this->property, $quote);
    }

    // --- Eloquent model ---

    public function test_reservation_model_relationships(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);

        $res = Reservation::find($resId);

        $this->assertInstanceOf(Property::class, $res->property);
        $this->assertSame($this->property->id, $res->property->id);
        $this->assertInstanceOf(Guest::class, $res->guest);
        $this->assertSame($this->guestId, $res->guest->id);
        $this->assertNotNull($res->rateSnapshot);
        $this->assertSame($this->snapshotId, (int) $res->rateSnapshot->id);
        $this->assertSame('confirmed', $res->status);
        $this->assertFalse($res->is_walk_in);
    }

    public function test_reservation_events_relationship(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);
        $svc->transition($resId, $this->property, 'checked_in', 1);

        $res = Reservation::find($resId);
        $events = $res->events;

        $this->assertCount(2, $events);
        $this->assertSame('confirmed', $events[0]->to_status);
        $this->assertSame('checked_in', $events[1]->to_status);
    }

    public function test_reservation_property_scope(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);

        $otherProperty = Property::factory()->create();

        // Scoped query returns reservation only for correct property
        $found = Reservation::where('property_id', $this->property->id)->find($resId);
        $this->assertNotNull($found);

        $notFound = Reservation::where('property_id', $otherProperty->id)->find($resId);
        $this->assertNull($notFound);
    }

    // --- Room reassignment with audit ---

    public function test_room_reassignment_records_change_event(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);

        $svc->reassignRoom($resId, $this->property, $this->roomId2, 1, 'Upgrade requested');

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame($this->roomId2, (int) $res->room_id);

        $event = DB::table('reservation_events')
            ->where('reservation_id', $resId)
            ->where('to_status', 'room_changed')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame('Upgrade requested', $event->reason);
        $changes = json_decode($event->changes, true);
        $this->assertSame($this->roomId, $changes['room_id']['from']);
        $this->assertSame($this->roomId2, $changes['room_id']['to']);
    }

    public function test_room_reassignment_rejected_on_terminal_status(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);
        $svc->transition($resId, $this->property, 'checked_in', 1);
        $svc->transition($resId, $this->property, 'checked_out', 1);

        $this->expectException(\DomainException::class);
        $svc->reassignRoom($resId, $this->property, $this->roomId2, 1);
    }

    public function test_room_reassignment_cross_property_rejected(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);

        $otherProperty = Property::factory()->create();

        $this->expectException(\DomainException::class);
        $svc->reassignRoom($resId, $otherProperty, $this->roomId2, 1);
    }

    // --- Group reservation ---

    public function test_group_reservation_links_multiple(): void
    {
        $svc = new ReservationService;

        $guest2Id = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Guest Two',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $groupId = $svc->createGroup($this->property, 'Corporate Meeting', 1);
        $this->assertGreaterThan(0, $groupId);

        $res1 = $svc->create($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1, $groupId);
        $res2 = $svc->create($this->property, $guest2Id, $this->roomId2, $this->snapshotId, 1, $groupId);

        $r1 = Reservation::find($res1);
        $r2 = Reservation::find($res2);

        $this->assertSame($groupId, $r1->group_id);
        $this->assertSame($groupId, $r2->group_id);

        // Group header
        $group = DB::table('reservation_groups')->where('id', $groupId)->first();
        $this->assertSame('Corporate Meeting', $group->name);
        $this->assertSame($this->property->id, (int) $group->property_id);
    }

    public function test_group_cross_property_rejected(): void
    {
        $svc = new ReservationService;
        $otherProperty = Property::factory()->create();

        $groupId = $svc->createGroup($this->property, 'My Group', 1);

        // Try to add reservation from different property to this group
        $otherGuestId = DB::table('guests')->insertGetId([
            'property_id' => $otherProperty->id,
            'display_name' => 'Other Guest',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\DomainException::class);
        $svc->create($otherProperty, $otherGuestId, $this->roomId, $this->snapshotId, 1, $groupId);
    }

    // --- Source tracking ---

    public function test_reservation_source_recorded(): void
    {
        $svc = new ReservationService;
        $resId = $svc->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
            null, 'phone',
        );

        $res = Reservation::find($resId);
        $this->assertSame('phone', $res->source);
    }

    public function test_walk_in_source_defaults(): void
    {
        $svc = new ReservationService;
        $resId = $svc->walkIn($this->property, $this->guestId, $this->roomId, $this->snapshotId, 1);

        $res = Reservation::find($resId);
        $this->assertSame('walk_in', $res->source);
    }
}
