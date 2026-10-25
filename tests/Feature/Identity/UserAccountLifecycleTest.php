<?php

namespace Tests\Feature\Identity;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesFailingDatabaseTriggers;
use Tests\TestCase;

class UserAccountLifecycleTest extends TestCase
{
    use CreatesFailingDatabaseTriggers;
    use RefreshDatabase;

    public function test_platform_admin_can_suspend_and_reactivate_another_account_with_audit(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $target = User::factory()->create(['status' => 'active']);
        $property = Property::factory()->create();
        DB::table('property_user')->insert([
            'user_id' => $target->id,
            'property_id' => $property->id,
            'role' => 'housekeeping',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.accounts.index'))
            ->assertOk()
            ->assertSee('md:hidden', false)
            ->assertSee('md:block', false)
            ->assertSee($target->email);

        $this->actingAs($admin)->post(route('admin.users.suspend', $target), [
            'reason' => 'Staff leave',
        ])->assertRedirect(route('admin.users.accounts.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'inactive']);
        $this->assertDatabaseCount('user_account_events', 1);
        $this->assertDatabaseHas('user_account_events', [
            'actor_id' => $admin->id,
            'user_id' => $target->id,
            'event' => 'suspended',
            'reason' => 'Staff leave',
        ]);
        $this->assertDatabaseHas('property_user', ['user_id' => $target->id, 'property_id' => $property->id]);

        $this->post(route('admin.users.reactivate', $target), [
            'reason' => 'Returned to work',
        ])->assertRedirect(route('admin.users.accounts.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseCount('user_account_events', 2);
        $this->assertDatabaseHas('user_account_events', [
            'actor_id' => $admin->id,
            'user_id' => $target->id,
            'event' => 'reactivated',
            'reason' => 'Returned to work',
        ]);
    }

    public function test_suspension_invalidates_existing_sessions_after_reactivation(): void
    {
        $target = User::factory()->create(['status' => 'active']);
        $version = (int) $target->getAttribute('auth_version');
        $this->actingAs(User::factory()->create(['status' => 'active', 'is_platform_admin' => true]))
            ->post(route('admin.users.suspend', $target), ['reason' => 'Security incident']);

        $target->refresh();
        $this->assertGreaterThan($version, $target->getAttribute('auth_version'));
        $this->actingAs(User::factory()->create(['status' => 'active', 'is_platform_admin' => true]))
            ->post(route('admin.users.reactivate', $target), ['reason' => 'Investigation cleared']);

        $this->withSession([
            Auth::guard()->getName() => $target->getAuthIdentifier(),
            'auth_version_'.$target->id => $version,
        ])->actingAs($target->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_suspension_invalidates_a_previously_issued_remember_cookie(): void
    {
        $target = User::factory()->create(['status' => 'active']);
        $rememberToken = 'known-remember-token';
        $target->setRememberToken($rememberToken);
        $target->save();

        $guard = Auth::guard();
        $recaller = implode('|', [
            $target->getAuthIdentifier(),
            $rememberToken,
            $guard->hashPasswordForCookie($target->getAuthPassword()),
        ]);
        $cookieName = $guard->getRecallerName();

        $this->assertSame(
            $target->id,
            $guard->getProvider()->retrieveByToken($target->id, $rememberToken)->id,
        );

        $this->flushSession()
            ->actingAs(User::factory()->create(['status' => 'active', 'is_platform_admin' => true]))
            ->post(route('admin.users.suspend', $target), ['reason' => 'Security incident'])
            ->assertRedirect(route('admin.users.accounts.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id, 'remember_token' => $rememberToken]);
        $this->actingAs(User::factory()->create(['status' => 'active', 'is_platform_admin' => true]))
            ->post(route('admin.users.reactivate', $target), ['reason' => 'Investigation cleared']);

        $this->flushSession()
            ->actingAsGuest()
            ->withCookie($cookieName, $recaller)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_suspend_audit_failure_rolls_back_status_and_auth_version(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $target = User::factory()->create(['status' => 'active']);
        $target->setRememberToken('rollback-account-token');
        $target->save();
        $version = $target->auth_version;
        $this->createFailingInsertTrigger('fail_user_account_audit', 'user_account_events', 'forced audit failure');

        try {
            $this->actingAs($admin)
                ->post(route('admin.users.suspend', $target), ['reason' => 'Security incident'])
                ->assertServerError();
            $this->assertDatabaseHas('users', [
                'id' => $target->id,
                'status' => 'active',
                'auth_version' => $version,
                'remember_token' => 'rollback-account-token',
            ]);
            $this->assertDatabaseCount('user_account_events', 0);
        } finally {
            $this->dropTestTrigger('fail_user_account_audit');
        }
    }

    public function test_successful_login_stores_current_account_auth_version_in_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth_version_'.$user->id, $user->getAttribute('auth_version'));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_inactive_user_cannot_access_dashboard_or_personal_settings(): void
    {
        foreach (['pending', 'inactive'] as $status) {
            $user = User::factory()->create(['status' => $status]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertRedirect(route('login'));

            $this->actingAs($user)
                ->get(route('profile.edit'))
                ->assertRedirect(route('login'));
        }
    }

    public function test_account_manager_and_actions_are_platform_admin_only(): void
    {
        $actor = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $this->actingAs($actor)->get(route('admin.users.accounts.index'))->assertNotFound();
        $this->post(route('admin.users.suspend', $target), ['reason' => 'Not allowed'])->assertNotFound();
        $this->post(route('admin.users.reactivate', $target), ['reason' => 'Not allowed'])->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseCount('user_account_events', 0);
    }

    public function test_account_lifecycle_audit_view_escapes_names_and_reason(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $target = User::factory()->create(['status' => 'active', 'name' => '<script>target()</script>']);
        $this->actingAs($admin)->post(route('admin.users.suspend', $target), [
            'reason' => '<img src=x onerror=alert(1)>',
        ])->assertRedirect(route('admin.users.accounts.index'));

        $this->get(route('admin.users.accounts.index'))
            ->assertOk()
            ->assertDontSee('<script>target()</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;script&gt;target()&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_platform_admin_target_cannot_be_suspended(): void
    {
        $actor = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $target = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);

        $this->actingAs($actor)->post(route('admin.users.suspend', $target), ['reason' => 'Not a safe action'])->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseCount('user_account_events', 0);
    }

    public function test_self_suspension_and_blank_reason_are_rejected_without_state_change(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'is_platform_admin' => true]);
        $target = User::factory()->create(['status' => 'active']);
        $this->actingAs($admin)->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $admin), ['reason' => 'Self suspend'])
            ->assertNotFound();
        $this->post(route('admin.users.suspend', $target), ['reason' => '  '])->assertSessionHasErrors('reason');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'active']);
        $this->assertDatabaseCount('user_account_events', 0);
    }
}
