<?php

namespace Tests\Feature\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserMembershipManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_platform_admin_can_open_membership_manager_and_assign_active_user(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.memberships.index'))
            ->assertOk()
            ->assertSee('md:grid-cols-4', false)
            ->assertSee($staff->email);

        $this->post(route('admin.users.memberships.store', $staff), [
            'property_id' => $property->id,
            'role' => 'housekeeping',
            'reason' => 'Assigned to housekeeping',
        ])->assertRedirect(route('admin.users.memberships.index'));

        $this->assertDatabaseHas('property_user', [
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
        ]);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'auth_version' => 1]);
        $this->assertDatabaseHas('property_membership_events', [
            'actor_id' => $admin->id,
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'event' => 'assigned',
            'previous_role' => null,
            'role' => 'housekeeping',
            'reason' => 'Assigned to housekeeping',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.users.memberships.index'))
            ->assertNotFound();
        $this->post(route('admin.users.memberships.store', $staff), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Privilege escalation attempt',
        ])->assertNotFound();
        $this->delete(route('admin.users.memberships.destroy', [$staff, $property]), [
            'reason' => 'Unauthorized revoke',
        ])->assertNotFound();
        $this->assertDatabaseHas('property_user', [
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
        ]);
        $this->assertDatabaseCount('property_membership_events', 1);
    }

    public function test_manager_lists_inactive_and_platform_admin_legacy_memberships_for_cleanup_only(): void
    {
        $admin = $this->platformAdmin();
        $legacyAdmin = $this->platformAdmin();
        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        $inactiveProperty = Property::factory()->create(['is_active' => false]);
        $legacyProperty = Property::factory()->create();
        $this->addMembership($inactiveUser, $inactiveProperty, 'housekeeping');
        $this->addMembership($legacyAdmin, $legacyProperty, 'finance');

        $response = $this->actingAs($admin)->get(route('admin.users.memberships.index'));
        $response->assertOk()
            ->assertSee($inactiveUser->email)
            ->assertSee($legacyAdmin->email)
            ->assertSee('action="'.route('admin.users.memberships.destroy', [$inactiveUser, $inactiveProperty]).'"', false)
            ->assertSee('action="'.route('admin.users.memberships.destroy', [$legacyAdmin, $legacyProperty]).'"', false)
            ->assertDontSee('action="'.route('admin.users.memberships.store', $legacyAdmin).'"', false);
    }

    public function test_platform_admin_cannot_manage_own_membership(): void
    {
        $admin = $this->platformAdmin();
        $property = Property::factory()->create();
        $this->addMembership($admin, $property, 'finance');

        $this->actingAs($admin)
            ->get(route('admin.users.memberships.index'))
            ->assertOk()
            ->assertDontSee('action="'.route('admin.users.memberships.store', $admin).'"', false)
            ->assertDontSee('action="'.route('admin.users.memberships.destroy', [$admin, $property]).'"', false);

        $this->post(route('admin.users.memberships.store', $admin), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Self escalation attempt',
        ])->assertNotFound();
        $this->delete(route('admin.users.memberships.destroy', [$admin, $property]), [
            'reason' => 'Self revoke attempt',
        ])->assertNotFound();
        $this->assertDatabaseHas('property_user', [
            'user_id' => $admin->id,
            'property_id' => $property->id,
            'role' => 'finance',
        ]);
    }

    public function test_membership_change_requires_reason_and_audits_old_and_new_roles(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active']);
        $staff->setRememberToken('initial-membership-token');
        $staff->save();
        $property = Property::factory()->create();
        $this->addMembership($staff, $property, 'housekeeping');

        $this->actingAs($admin)->from(route('admin.users.memberships.index'))
            ->post(route('admin.users.memberships.store', $staff), [
                'property_id' => $property->id,
                'role' => 'front_desk',
                'reason' => '   ',
            ])->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('property_user', [
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
        ]);

        $this->post(route('admin.users.memberships.store', $staff), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Temporary front desk assignment',
        ])->assertRedirect(route('admin.users.memberships.index'));

        $this->assertDatabaseHas('property_user', [
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'role' => 'front_desk',
        ]);
        $this->assertDatabaseHas('property_membership_events', [
            'event' => 'role_changed',
            'previous_role' => 'housekeeping',
            'role' => 'front_desk',
            'reason' => 'Temporary front desk assignment',
        ]);
        $this->assertDatabaseCount('property_membership_events', 1);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'auth_version' => 1]);
        $rememberTokenAfterRoleChange = $staff->fresh()->getRememberToken();
        $this->assertNotSame('initial-membership-token', $rememberTokenAfterRoleChange);
        $this->actingAs($staff)
            ->get(route('properties.housekeeping.board', $property))
            ->assertNotFound();

        $this->actingAs($admin)->post(route('admin.users.memberships.store', $staff), [
            'property_id' => $property->id,
            'role' => 'front_desk',
            'reason' => 'Duplicate submission',
        ])->assertRedirect(route('admin.users.memberships.index'));
        $this->assertDatabaseCount('property_membership_events', 1);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'auth_version' => 1]);
        $this->assertSame($rememberTokenAfterRoleChange, $staff->fresh()->getRememberToken());
    }

    public function test_membership_mutation_rolls_back_when_audit_insert_fails(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active']);
        $staff->setRememberToken('rollback-membership-token');
        $staff->save();
        $property = Property::factory()->create();
        DB::unprepared("CREATE TRIGGER fail_membership_audit BEFORE INSERT ON property_membership_events BEGIN SELECT RAISE(ABORT, 'forced audit failure'); END");
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post(route('admin.users.memberships.store', $staff), [
                'property_id' => $property->id,
                'role' => 'housekeeping',
                'reason' => 'Test rollback',
            ]);
            $this->fail('Expected audit insertion failure.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('property_user', [
                'user_id' => $staff->id,
                'property_id' => $property->id,
            ]);
            $this->assertDatabaseHas('users', ['id' => $staff->id, 'remember_token' => 'rollback-membership-token']);
            $this->assertDatabaseCount('property_membership_events', 0);
        }
    }

    public function test_revocation_rolls_back_when_audit_insert_fails(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active']);
        $staff->setRememberToken('rollback-revoke-token');
        $staff->save();
        $version = $staff->auth_version;
        $property = Property::factory()->create();
        $this->addMembership($staff, $property, 'housekeeping');

        $this->actingAs($admin)
            ->from(route('admin.users.memberships.index'))
            ->delete(route('admin.users.memberships.destroy', [$staff, $property]), ['reason' => '   '])
            ->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('property_user', [
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
        ]);

        DB::unprepared("CREATE TRIGGER fail_membership_audit BEFORE INSERT ON property_membership_events BEGIN SELECT RAISE(ABORT, 'forced audit failure'); END");
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->delete(route('admin.users.memberships.destroy', [$staff, $property]), [
                'reason' => 'Test rollback',
            ]);
            $this->fail('Expected audit insertion failure.');
        } catch (QueryException) {
            $this->assertDatabaseHas('property_user', [
                'user_id' => $staff->id,
                'property_id' => $property->id,
                'role' => 'housekeeping',
            ]);
            $this->assertDatabaseHas('users', [
                'id' => $staff->id,
                'auth_version' => $version,
                'remember_token' => 'rollback-revoke-token',
            ]);
            $this->assertDatabaseCount('property_membership_events', 0);
        }
    }

    public function test_revocation_invalidates_an_existing_remember_cookie(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active']);
        $staff->setRememberToken('active-membership-token');
        $staff->save();
        $property = Property::factory()->create();
        $this->addMembership($staff, $property, 'housekeeping');

        $guard = auth()->guard();
        $recaller = implode('|', [
            $staff->id,
            $staff->getRememberToken(),
            $guard->hashPasswordForCookie($staff->getAuthPassword()),
        ]);
        $this->assertSame($staff->id, $guard->getProvider()->retrieveByToken($staff->id, $staff->getRememberToken())->id);

        $this->actingAs($admin)->delete(route('admin.users.memberships.destroy', [$staff, $property]), [
            'reason' => 'Access removed',
        ])->assertRedirect(route('admin.users.memberships.index'));

        $this->flushSession()
            ->actingAsGuest()
            ->withCookie($guard->getRecallerName(), $recaller)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_membership_history_escapes_untrusted_names_and_reasons(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active', 'name' => '<script>alert(1)</script>']);
        $property = Property::factory()->create(['name' => '<img src=x onerror=alert(1)>']);

        $this->actingAs($admin)->post(route('admin.users.memberships.store', $staff), [
            'property_id' => $property->id,
            'role' => 'housekeeping',
            'reason' => '<script>alert(2)</script>',
        ])->assertRedirect(route('admin.users.memberships.index'));

        $this->get(route('admin.users.memberships.index'))
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(2)</script>', false);
    }

    public function test_revoked_membership_is_denied_on_next_property_request_and_audit_is_immutable(): void
    {
        $admin = $this->platformAdmin();
        $staff = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();
        $roomId = $this->createRoom($property);
        $this->actingAs($admin)->post(route('admin.users.memberships.store', $staff), [
            'property_id' => $property->id,
            'role' => 'housekeeping',
            'reason' => 'Assigned to housekeeping',
        ])->assertRedirect(route('admin.users.memberships.index'));
        $eventId = (int) DB::table('property_membership_events')->value('id');
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'auth_version' => 1]);

        $this->actingAs($staff)
            ->get(route('properties.housekeeping.board', $property))
            ->assertOk();

        $this->actingAs($admin)->delete(route('admin.users.memberships.destroy', [$staff, $property]), [
            'reason' => 'Housekeeping assignment ended',
        ])->assertRedirect(route('admin.users.memberships.index'));

        $this->assertDatabaseMissing('property_user', [
            'user_id' => $staff->id,
            'property_id' => $property->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'auth_version' => 2]);
        $this->assertDatabaseHas('property_membership_events', [
            'actor_id' => $admin->id,
            'user_id' => $staff->id,
            'property_id' => $property->id,
            'event' => 'revoked',
            'previous_role' => 'housekeeping',
            'role' => null,
            'reason' => 'Housekeeping assignment ended',
        ]);
        $this->actingAs($staff)
            ->get(route('properties.housekeeping.board', $property))
            ->assertNotFound();

        try {
            DB::table('property_membership_events')->where('id', $eventId)->update(['reason' => 'tampered']);
            $this->fail('Membership audit rows must reject updates.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('property_membership_events', ['id' => $eventId, 'reason' => 'tampered']);
        }
    }

    public function test_pending_users_inactive_properties_and_empty_reasons_cannot_gain_membership(): void
    {
        $admin = $this->platformAdmin();
        $pending = User::factory()->create(['status' => 'pending']);
        $active = User::factory()->create(['status' => 'active']);
        $inactiveProperty = Property::factory()->create(['is_active' => false]);

        $this->actingAs($admin)->post(route('admin.users.memberships.store', $pending), [
            'property_id' => Property::factory()->create()->id,
            'role' => 'housekeeping',
            'reason' => 'No access',
        ])->assertNotFound();

        $this->post(route('admin.users.memberships.store', $active), [
            'property_id' => $inactiveProperty->id,
            'role' => 'housekeeping',
            'reason' => '   ',
        ])->assertSessionHasErrors(['property_id', 'reason']);

        $this->assertDatabaseCount('property_user', 0);
        $this->assertDatabaseCount('property_membership_events', 0);
    }

    private function addMembership(User $user, Property $property, string $role): void
    {
        DB::table('property_user')->insert([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRoom(Property $property): int
    {
        $roomTypeId = DB::table('room_types')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_active' => true,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $property->id,
            'room_type_id' => $roomTypeId,
            'code' => '101',
            'is_active' => true,
        ]);
        DB::table('room_status_events')->insert([
            'property_id' => $property->id,
            'room_id' => $roomId,
            'previous_status' => null,
            'status' => 'dirty',
            'actor_id' => null,
            'created_at' => now(),
        ]);

        return $roomId;
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->forceFill(['is_platform_admin' => true])->save();

        return $user;
    }
}
