<?php

namespace Tests\Feature\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesFailingDatabaseTriggers;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use CreatesFailingDatabaseTriggers;
    use RefreshDatabase;

    public function test_platform_admin_sees_pending_accounts_and_can_approve_with_scope_role_and_reason(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['status' => 'pending']);
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($pending->email);

        $this->actingAs($admin)
            ->post(route('admin.users.approve', $pending), [
                'property_id' => $property->id,
                'role' => 'front_desk',
                'reason' => 'Staff onboarding',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'active']);
        $this->assertDatabaseHas('property_user', [
            'user_id' => $pending->id,
            'property_id' => $property->id,
            'role' => 'front_desk',
        ]);
        $this->assertDatabaseHas('user_access_events', [
            'actor_id' => $admin->id,
            'user_id' => $pending->id,
            'property_id' => $property->id,
            'event' => 'approved',
            'role' => 'front_desk',
            'reason' => 'Staff onboarding',
        ]);
    }

    public function test_queue_only_lists_pending_users(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['name' => 'Pending Account', 'status' => 'pending']);
        $active = User::factory()->create(['name' => 'Active Account', 'status' => 'active']);
        $rejected = User::factory()->create(['name' => 'Rejected Account', 'status' => 'rejected']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($pending->email)
            ->assertDontSee($active->email)
            ->assertDontSee($rejected->email);
    }

    public function test_platform_admin_navigation_shows_approval_link_only_to_admin(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $staff = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.users.index'), false)
            ->assertSee(route('admin.users.memberships.index'), false)
            ->assertSee(route('admin.users.accounts.index'), false)
            ->assertSee(route('admin.audit.index'), false);
        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('my.properties.index'), false)
            ->assertDontSee(route('properties.index'), false)
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.users.memberships.index'), false)
            ->assertDontSee(route('admin.users.accounts.index'), false)
            ->assertDontSee(route('admin.audit.index'), false)
            ->assertDontSee('Properties and inventory');
    }

    public function test_rejected_account_cannot_log_in(): void
    {
        $rejected = User::factory()->create(['status' => 'rejected']);

        $this->post(route('login.store'), [
            'email' => $rejected->email,
            'password' => 'password',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_pending_account_list_escapes_user_supplied_markup(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create([
            'name' => '<script>alert(1)</script>',
            'email' => 'markup@example.test',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertSee($pending->email);
    }

    public function test_approval_requires_active_property_supported_role_and_reason(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['status' => 'pending']);
        $inactiveProperty = Property::query()->create(['name' => 'Inactive', 'code' => 'INACTIVE']);
        $inactiveProperty->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)
            ->post(route('admin.users.approve', $pending), [
                'property_id' => $inactiveProperty->id,
                'role' => 'superuser',
                'reason' => '',
            ])
            ->assertSessionHasErrors(['property_id', 'role', 'reason']);

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('property_user', ['user_id' => $pending->id]);
    }

    public function test_platform_admin_can_reject_pending_account_with_reason_and_audit_event(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.users.reject', $pending), ['reason' => 'Registration not verified'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('user_access_events', [
            'actor_id' => $admin->id,
            'user_id' => $pending->id,
            'property_id' => null,
            'event' => 'rejected',
            'role' => null,
            'reason' => 'Registration not verified',
        ]);
    }

    public function test_pending_users_cannot_access_approval_or_property_routes(): void
    {
        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($pending)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($pending)
            ->get(route('properties.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_non_admin_cannot_view_or_change_account_approval(): void
    {
        $user = User::factory()->create();
        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertNotFound();
        $this->post(route('admin.users.reject', $pending), ['reason' => 'No'])
            ->assertNotFound();
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);
        $this->post(route('admin.users.approve', $pending), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Unauthorized attempt',
        ])->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('property_user', ['user_id' => $pending->id]);
        $this->assertDatabaseCount('user_access_events', 0);
    }

    public function test_non_pending_user_cannot_be_approved_or_self_escalate(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);

        $this->actingAs($admin)
            ->post(route('admin.users.approve', $admin), [
                'property_id' => $property->id,
                'role' => 'hotel_admin',
                'reason' => 'Self approval',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('property_user', ['user_id' => $admin->id]);
    }

    public function test_hotel_membership_does_not_grant_unmapped_property_permissions(): void
    {
        $user = User::factory()->create();
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);
        DB::table('property_user')->insert([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'role' => 'front_desk',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('properties.index'))->assertNotFound();
    }

    public function test_failed_audit_write_rolls_back_membership_and_activation(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['status' => 'pending']);
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);
        $this->createFailingInsertTrigger('reject_user_access_event', 'user_access_events', 'simulated audit write failure');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post(route('admin.users.approve', $pending), [
                'property_id' => $property->id,
                'role' => 'front_desk',
                'reason' => 'Staff onboarding',
            ]);
            $this->fail('Audit failure must abort account approval.');
        } catch (QueryException) {
            $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'pending']);
            $this->assertDatabaseMissing('property_user', ['user_id' => $pending->id]);
            $this->assertDatabaseCount('user_access_events', 0);
        } finally {
            $this->dropTestTrigger('reject_user_access_event');
        }
    }

    public function test_membership_database_rejects_unsupported_role(): void
    {
        $user = User::factory()->create();
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);

        $this->expectException(QueryException::class);
        DB::table('property_user')->insert([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'role' => 'superuser',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_approved_account_cannot_be_approved_twice(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['status' => 'pending']);
        $property = Property::query()->create(['name' => 'Test Hotel', 'code' => 'TEST']);

        $this->actingAs($admin)->post(route('admin.users.approve', $pending), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Staff onboarding',
        ])->assertRedirect();

        $this->post(route('admin.users.approve', $pending), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Duplicate request',
        ])->assertNotFound();

        $this->assertDatabaseCount('property_user', 1);
        $this->assertDatabaseCount('user_access_events', 1);
    }

    public function test_user_access_audit_events_reject_update_and_delete(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.users.reject', $pending), ['reason' => 'Test reason'])
            ->assertRedirect();

        $eventId = (int) DB::table('user_access_events')->value('id');

        try {
            DB::table('user_access_events')->where('id', $eventId)->update(['reason' => 'Tampered']);
            $this->fail('User access audit updates must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('user_access_events', ['id' => $eventId, 'reason' => 'Test reason']);
        }

        try {
            DB::table('user_access_events')->where('id', $eventId)->delete();
            $this->fail('User access audit deletes must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('user_access_events', ['id' => $eventId]);
        }
    }
}
