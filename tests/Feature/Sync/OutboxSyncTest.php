<?php

namespace Tests\Feature\Sync;

use App\Models\Property;
use App\Services\Sync\OutboxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SYNC-001: Transactional Outbox Pattern for WAN-Safe Local-First Sync,
 * Bounded Retries, and Guest PII Exclusion (D-007).
 */
class OutboxSyncTest extends TestCase
{
    use RefreshDatabase;

    private Property $propertyA;

    private Property $propertyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propertyA = Property::factory()->create(['name' => 'Sync Hotel Alpha']);
        $this->propertyB = Property::factory()->create(['name' => 'Sync Hotel Beta']);
    }

    public function test_enqueue_event_creates_queued_record(): void
    {
        $svc = new OutboxService;

        $event = $svc->enqueue($this->propertyA, 'reservation.created', [
            'reservation_id' => 101,
            'room_id' => 5,
            'status' => 'confirmed',
            'nights' => 2,
        ]);

        $this->assertNotNull($event);
        $this->assertSame($this->propertyA->id, (int) $event->property_id);
        $this->assertSame('reservation.created', $event->topic);
        $this->assertSame('queued', $event->status);
        $this->assertSame(0, (int) $event->attempts);
        $this->assertNotEmpty($event->event_uuid);

        $payload = json_decode($event->payload, true);
        $this->assertSame(101, $payload['reservation_id']);
    }

    public function test_enqueue_rejects_guest_pii_fields_per_d007(): void
    {
        $svc = new OutboxService;

        // Attempting to enqueue guest personal details without D-007 approval must be blocked
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Sync payload contains prohibited guest PII');

        $svc->enqueue($this->propertyA, 'guest.synced', [
            'guest_id' => 50,
            'phone' => '+628123456789', // Prohibited PII
            'id_card' => '3171012345670001', // Prohibited PII
        ]);
    }

    public function test_fetch_pending_events_respects_due_time_and_limit(): void
    {
        $svc = new OutboxService;

        $e1 = $svc->enqueue($this->propertyA, 'stay.checked_in', ['stay_id' => 1]);
        $e2 = $svc->enqueue($this->propertyA, 'stay.checked_out', ['stay_id' => 2]);

        // Enqueue event for Property B (must not leak)
        $svc->enqueue($this->propertyB, 'stay.checked_in', ['stay_id' => 3]);

        $pending = $svc->fetchPendingEvents($this->propertyA, 10);
        $this->assertCount(2, $pending);
        $this->assertEquals([$e1->id, $e2->id], $pending->pluck('id')->all());
    }

    public function test_mark_delivered_updates_status(): void
    {
        $svc = new OutboxService;
        $event = $svc->enqueue($this->propertyA, 'night_audit.closed', ['date' => '2028-12-01']);

        $svc->markDelivered($event->id, $this->propertyA);

        $fresh = DB::table('sync_outbox')->where('id', $event->id)->first();
        $this->assertSame('delivered', $fresh->status);
        $this->assertNotNull($fresh->delivered_at);
    }

    public function test_mark_failed_increments_attempts_and_schedules_backoff(): void
    {
        $svc = new OutboxService;
        $event = $svc->enqueue($this->propertyA, 'order.posted', ['order_id' => 99]);

        Carbon::setTestNow('2028-12-10 10:00:00');

        // First failure -> schedules next retry
        $svc->markFailed($event->id, $this->propertyA, 'Connection refused by central gateway');

        $fresh1 = DB::table('sync_outbox')->where('id', $event->id)->first();
        $this->assertSame('queued', $fresh1->status);
        $this->assertSame(1, (int) $fresh1->attempts);
        $this->assertNotNull($fresh1->next_retry_at);
        $this->assertSame('Connection refused by central gateway', $fresh1->last_error);

        // Fail until max_attempts reached (default 5)
        for ($i = 2; $i <= 5; $i++) {
            $svc->markFailed($event->id, $this->propertyA, "Attempt {$i} failed");
        }

        $freshFinal = DB::table('sync_outbox')->where('id', $event->id)->first();
        $this->assertSame('failed', $freshFinal->status);
        $this->assertSame(5, (int) $freshFinal->attempts);

        // Now event is permanently dead-lettered and not fetched as pending
        $pending = $svc->fetchPendingEvents($this->propertyA, 10);
        $this->assertCount(0, $pending);

        Carbon::setTestNow();
    }

    public function test_retry_failed_event_resets_status_to_queued(): void
    {
        $svc = new OutboxService;
        $event = $svc->enqueue($this->propertyA, 'test.event', ['val' => 1]);

        // Force fail
        for ($i = 1; $i <= 5; $i++) {
            $svc->markFailed($event->id, $this->propertyA, 'Error');
        }

        $failed = DB::table('sync_outbox')->where('id', $event->id)->first();
        $this->assertSame('failed', $failed->status);

        // Manual retry
        $svc->retryFailed($this->propertyA, $event->id);

        $requeued = DB::table('sync_outbox')->where('id', $event->id)->first();
        $this->assertSame('queued', $requeued->status);
        $this->assertSame(0, (int) $requeued->attempts);
        $this->assertNull($requeued->next_retry_at);
    }

    public function test_cross_property_outbox_operations_rejected(): void
    {
        $svc = new OutboxService;
        $event = $svc->enqueue($this->propertyA, 'test.event', ['val' => 1]);

        $this->expectException(\DomainException::class);
        $svc->markDelivered($event->id, $this->propertyB);
    }
}
