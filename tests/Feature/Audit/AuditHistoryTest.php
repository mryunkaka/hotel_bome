<?php

namespace Tests\Feature\Audit;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_history_requires_active_platform_admin(): void
    {
        $this->get('/admin/audit')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['status' => 'active']))
            ->get('/admin/audit')
            ->assertNotFound();

        $this->actingAs(User::factory()->create(['status' => 'inactive', 'is_platform_admin' => true]))
            ->get('/admin/audit')
            ->assertRedirect(route('login'));
    }

    public function test_platform_admin_can_view_all_existing_audit_sources_without_mutating_them(): void
    {
        $actor = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $subject = User::factory()->create(['status' => 'active', 'name' => '<script>staff()</script>']);
        $property = Property::factory()->create(['name' => '<img src=x onerror=alert(1)>']);
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '101',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $now = now();

        DB::table('user_access_events')->insert([
            'actor_id' => $actor->id,
            'user_id' => $subject->id,
            'property_id' => $property->id,
            'event' => 'approved',
            'role' => 'housekeeping',
            'reason' => '<script>approval()</script>',
            'created_at' => $now,
        ]);
        DB::table('user_access_events')->insert([
            'actor_id' => $actor->id,
            'user_id' => $subject->id,
            'property_id' => null,
            'event' => 'rejected',
            'role' => null,
            'reason' => 'propertyless access history',
            'created_at' => $now,
        ]);
        DB::table('property_membership_events')->insert([
            'actor_id' => $actor->id,
            'user_id' => $subject->id,
            'property_id' => $property->id,
            'event' => 'assigned',
            'previous_role' => null,
            'role' => 'housekeeping',
            'reason' => 'assigned for shift',
            'created_at' => $now->copy()->subSecond(),
        ]);
        DB::table('user_account_events')->insert([
            'actor_id' => $actor->id,
            'user_id' => $subject->id,
            'event' => 'suspended',
            'reason' => 'access review',
            'created_at' => $now->copy()->subSeconds(2),
        ]);
        DB::table('room_status_events')->insert([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => 'dirty',
            'status' => 'clean',
            'actor_id' => null,
            'reason' => 'checked room',
            'created_at' => $now->copy()->subSeconds(3),
        ]);
        $blockId = DB::table('room_blocks')->insertGetId([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'reason' => 'maintenance',
            'created_by' => $actor->id,
            'created_at' => $now->copy()->subSeconds(4),
        ]);
        DB::table('room_block_cancellations')->insert([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'room_block_id' => $blockId,
            'actor_id' => $actor->id,
            'created_at' => $now->copy()->subSeconds(5),
            'reason' => 'maintenance completed',
        ]);

        $sourceTables = [
            'user_access_events',
            'property_membership_events',
            'user_account_events',
            'room_status_events',
            'room_block_cancellations',
        ];
        $before = collect($sourceTables)->mapWithKeys(
            fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all()],
        )->all();

        $this->assertDatabaseHas('user_access_events', ['reason' => 'propertyless access history']);
        $this->assertDatabaseHas('room_status_events', ['actor_id' => null, 'room_id' => $roomId]);

        $this->actingAs($actor)
            ->get('/admin/audit')
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee('propertyless access history')
            ->assertSee('Not applicable')
            ->assertSee('Unavailable')
            ->assertSee('User Access')
            ->assertSee('Membership')
            ->assertSee('Account')
            ->assertSee('Room Status')
            ->assertSee('Room Block Cancellation')
            ->assertSee('user_access:1')
            ->assertSee('user_access:2')
            ->assertSee('membership:1')
            ->assertSee('account:1')
            ->assertSee('room_status:1')
            ->assertSee('room_block_cancellation:1')
            ->assertSee('Approved')
            ->assertSee('Assigned')
            ->assertSee('Suspended')
            ->assertSee('Status Changed')
            ->assertSee('Cancelled')
            ->assertSee('&lt;script&gt;staff()&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertSee('&lt;script&gt;approval()&lt;/script&gt;', false)
            ->assertDontSee('<script>staff()</script>', false)
            ->assertDontSee('remember_token')
            ->assertDontSee('two_factor_secret');

        $after = collect($sourceTables)->mapWithKeys(
            fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all()],
        )->all();
        $this->assertSame($before, $after);
    }

    public function test_audit_filters_validate_dates_and_limit_date_range(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/audit?from=2025-01-01')
            ->assertSessionHasErrors('to');

        $this->actingAs($admin)
            ->get('/admin/audit?to=2025-01-01')
            ->assertSessionHasErrors('from');

        $this->actingAs($admin)
            ->get('/admin/audit?from=not-a-date')
            ->assertSessionHasErrors('from');

        $this->get('/admin/audit?from=2025-01-01&to=2026-01-02')
            ->assertSessionHasErrors('to');

        $this->get('/admin/audit?from=2026-02-02&to=2026-02-01')
            ->assertSessionHasErrors('to');

        $this->get('/admin/audit?source=secrets')
            ->assertSessionHasErrors('source');

        $this->get('/admin/audit?property_id=999999')
            ->assertSessionHasErrors('property_id');
    }

    public function test_date_filters_are_inclusive_and_pages_are_capped_at_fifty(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $subject = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();

        $rows = collect([
            '2026-06-01 00:00:00',
            '2026-06-02 23:59:59',
            '2026-05-31 23:59:59',
            '2026-06-03 00:00:00',
        ])->map(fn (string $createdAt): array => [
            'actor_id' => $admin->id,
            'user_id' => $subject->id,
            'property_id' => $property->id,
            'event' => 'approved',
            'role' => 'front_desk',
            'reason' => 'boundary '.$createdAt,
            'created_at' => $createdAt,
        ])->all();

        foreach (range(1, 50) as $i) {
            $rows[] = [
                'actor_id' => $admin->id,
                'user_id' => $subject->id,
                'property_id' => $property->id,
                'event' => 'approved',
                'role' => 'front_desk',
                'reason' => 'page row '.$i,
                'created_at' => '2026-06-02 12:00:'.str_pad((string) ($i % 60), 2, '0', STR_PAD_LEFT),
            ];
        }
        DB::table('user_access_events')->insert($rows);

        $url = '/admin/audit?source=user_access&from=2026-06-01&to=2026-06-02';
        $firstPage = $this->actingAs($admin)->get($url)->assertOk();
        $firstEvents = $firstPage->original->getData()['events'];
        $this->assertSame(50, $firstEvents->perPage());
        $this->assertSame(50, $firstEvents->count());
        $this->assertTrue($firstEvents->hasMorePages());
        $firstPage->assertSee('boundary 2026-06-02 23:59:59')
            ->assertDontSee('boundary 2026-05-31 23:59:59')
            ->assertDontSee('boundary 2026-06-03 00:00:00')
            ->assertSee('page=2');

        $secondPage = $this->get($url.'&page=2')->assertOk();
        $secondEvents = $secondPage->original->getData()['events'];
        $this->assertSame(2, $secondEvents->count());
        $this->assertFalse($secondEvents->hasMorePages());
        $secondPage->assertSee('boundary 2026-06-01 00:00:00');
    }

    public function test_events_with_matching_timestamps_use_source_and_id_tie_breakers(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $subject = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();
        $createdAt = '2026-06-02 12:00:00';

        $userAccessIds = [];
        foreach ([1, 2] as $i) {
            $userAccessIds[] = DB::table('user_access_events')->insertGetId([
                'actor_id' => $admin->id,
                'user_id' => $subject->id,
                'property_id' => $property->id,
                'event' => 'approved',
                'role' => 'front_desk',
                'reason' => 'access '.$i,
                'created_at' => $createdAt,
            ]);
        }
        $membershipId = DB::table('property_membership_events')->insertGetId([
            'actor_id' => $admin->id,
            'user_id' => $subject->id,
            'property_id' => $property->id,
            'event' => 'assigned',
            'previous_role' => null,
            'role' => 'front_desk',
            'reason' => 'membership',
            'created_at' => $createdAt,
        ]);

        $response = $this->actingAs($admin)->get('/admin/audit')->assertOk();
        $keys = $response->original->getData()['events']->getCollection()
            ->map(fn (object $event): string => $event->source.':'.$event->source_id)
            ->all();

        $this->assertSame(["membership:{$membershipId}", "user_access:{$userAccessIds[1]}", "user_access:{$userAccessIds[0]}"], $keys);
    }

    public function test_source_and_property_filters_select_only_matching_events(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $subject = User::factory()->create(['status' => 'active']);
        $propertyA = Property::factory()->create();
        $propertyB = Property::factory()->create();

        foreach ([$propertyA, $propertyB] as $property) {
            DB::table('user_access_events')->insert([
                'actor_id' => $admin->id,
                'user_id' => $subject->id,
                'property_id' => $property->id,
                'event' => 'approved',
                'role' => 'housekeeping',
                'reason' => 'test event '.$property->id,
                'created_at' => now()->addSeconds($property->id),
            ]);
        }
        DB::table('user_account_events')->insert([
            'actor_id' => $admin->id,
            'user_id' => $subject->id,
            'event' => 'suspended',
            'reason' => 'account event',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/audit?source=user_access')->assertOk();
        $events = $response->original->getData()['events'];
        $this->assertSame(2, $events->count(), json_encode($events->getCollection()->toArray()));
        $response->assertSee('test event '.$propertyA->id)
            ->assertSee('test event '.$propertyB->id)
            ->assertDontSee('account event');

        $propertyResponse = $this->get('/admin/audit?property_id='.$propertyA->id)->assertOk();
        $propertyEvents = $propertyResponse->original->getData()['events'];
        $this->assertSame(1, $propertyEvents->count());
        $propertyResponse->assertSee('test event '.$propertyA->id)
            ->assertDontSee('test event '.$propertyB->id);

        $this->get('/admin/audit?source=user_access&property_id='.$propertyA->id)
            ->assertOk()
            ->assertSee('test event '.$propertyA->id)
            ->assertDontSee('test event '.$propertyB->id)
            ->assertDontSee('account event');
    }
}
