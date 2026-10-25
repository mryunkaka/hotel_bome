<?php

namespace Tests\Feature\Reservations;

use App\Models\Property;
use App\Models\RatePlan;
use App\Services\RateQuoteService;
use App\Services\RateSnapshotService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $reservationService;

    private Property $property;

    private int $snapshotId;

    private int $guestId;

    private int $roomId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = new ReservationService;
        $this->property = Property::factory()->create();

        // Create guest stub (ID only, no PII per D-007 boundary)
        $this->guestId = DB::table('guests')->insertGetId([
            'property_id' => $this->property->id,
            'display_name' => 'Test Guest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create room type and room
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $this->property->id, 'name' => 'Standard', 'code' => 'STD',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->roomId = DB::table('rooms')->insertGetId([
            'property_id' => $this->property->id, 'room_type_id' => $roomTypeId,
            'code' => '101', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Create rate plan and capture snapshot
        RatePlan::forceCreate([
            'property_id' => $this->property->id,
            'room_type_id' => $roomTypeId,
            'name' => 'Standard Rate',
            'code' => 'STD',
            'base_price_per_night' => 500000,
            'tax_percentage' => '10.00',
            'is_active' => true,
        ]);

        $quote = (new RateQuoteService)->quote(
            $this->property, $roomTypeId,
            Carbon::parse('2027-01-10'), Carbon::parse('2027-01-13'),
        );
        $this->snapshotId = (new RateSnapshotService)->capture($this->property, $quote);
    }

    // --- State machine: normal flow ---

    public function test_create_confirmed_reservation(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('confirmed', $res->status);
        $this->assertSame($this->property->id, (int) $res->property_id);
        $this->assertSame($this->guestId, (int) $res->guest_id);
        $this->assertSame($this->roomId, (int) $res->room_id);
        $this->assertSame($this->snapshotId, (int) $res->rate_snapshot_id);

        // Event recorded
        $event = DB::table('reservation_events')->where('reservation_id', $resId)->first();
        $this->assertSame('confirmed', $event->to_status);
        $this->assertNull($event->from_status);
    }

    public function test_confirmed_to_checked_in(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );

        $this->reservationService->transition($resId, $this->property, 'checked_in', 1);

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('checked_in', $res->status);
    }

    public function test_checked_in_to_checked_out(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'checked_in', 1);
        $this->reservationService->transition($resId, $this->property, 'checked_out', 1);

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('checked_out', $res->status);
    }

    public function test_confirmed_to_cancelled(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'cancelled', 1, 'Guest requested');

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('cancelled', $res->status);

        $event = DB::table('reservation_events')
            ->where('reservation_id', $resId)
            ->where('to_status', 'cancelled')
            ->first();
        $this->assertSame('Guest requested', $event->reason);
    }

    public function test_confirmed_to_no_show(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'no_show', 1);

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('no_show', $res->status);
    }

    // --- Invalid transitions ---

    public function test_checked_in_cannot_cancel(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'checked_in', 1);

        $this->expectException(\DomainException::class);
        $this->reservationService->transition($resId, $this->property, 'cancelled', 1);
    }

    public function test_checked_out_is_terminal(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'checked_in', 1);
        $this->reservationService->transition($resId, $this->property, 'checked_out', 1);

        $this->expectException(\DomainException::class);
        $this->reservationService->transition($resId, $this->property, 'checked_in', 1);
    }

    public function test_cancelled_is_terminal(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'cancelled', 1, 'No show');

        $this->expectException(\DomainException::class);
        $this->reservationService->transition($resId, $this->property, 'confirmed', 1);
    }

    public function test_no_show_is_terminal(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'no_show', 1);

        $this->expectException(\DomainException::class);
        $this->reservationService->transition($resId, $this->property, 'confirmed', 1);
    }

    // --- Walk-in ---

    public function test_walk_in_creates_confirmed_and_checks_in(): void
    {
        $resId = $this->reservationService->walkIn(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );

        $res = DB::table('reservations')->where('id', $resId)->first();
        $this->assertSame('checked_in', $res->status);
        $this->assertTrue((bool) $res->is_walk_in);

        // Two events: confirmed + checked_in
        $events = DB::table('reservation_events')
            ->where('reservation_id', $resId)
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $events);
        $this->assertSame('confirmed', $events[0]->to_status);
        $this->assertSame('checked_in', $events[1]->to_status);
    }

    // --- Cross-property ---

    public function test_cross_property_transition_rejected(): void
    {
        $otherProperty = Property::factory()->create();

        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );

        $this->expectException(\DomainException::class);
        $this->reservationService->transition($resId, $otherProperty, 'checked_in', 1);
    }

    // --- Event immutability ---

    public function test_reservation_events_reject_update(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );

        $eventId = DB::table('reservation_events')
            ->where('reservation_id', $resId)->first()->id;

        try {
            DB::table('reservation_events')
                ->where('id', $eventId)
                ->update(['reason' => 'tampered']);
        } catch (\Exception) {
            // Expected
        }

        $event = DB::table('reservation_events')->where('id', $eventId)->first();
        $this->assertNull($event->reason);
    }

    public function test_reservation_events_reject_delete(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );

        $eventId = DB::table('reservation_events')
            ->where('reservation_id', $resId)->first()->id;

        try {
            DB::table('reservation_events')->where('id', $eventId)->delete();
        } catch (\Exception) {
            // Expected
        }

        $this->assertNotNull(DB::table('reservation_events')->where('id', $eventId)->first());
    }

    // --- Full lifecycle audit trail ---

    public function test_full_lifecycle_audit_trail(): void
    {
        $resId = $this->reservationService->create(
            $this->property, $this->guestId, $this->roomId, $this->snapshotId, 1,
        );
        $this->reservationService->transition($resId, $this->property, 'checked_in', 1);
        $this->reservationService->transition($resId, $this->property, 'checked_out', 1);

        $events = DB::table('reservation_events')
            ->where('reservation_id', $resId)
            ->orderBy('id')
            ->pluck('to_status')
            ->toArray();

        $this->assertSame(['confirmed', 'checked_in', 'checked_out'], $events);
    }
}
