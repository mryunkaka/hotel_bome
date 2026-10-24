<?php

namespace Tests\Feature\Bootstrap;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstAdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_bootstrap_is_only_available_from_loopback(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.25'])
            ->get(route('setup.first-admin'))
            ->assertNotFound();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('setup.first-admin'))
            ->assertOk();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_loopback_proxy_headers_are_rejected_for_the_setup_wizard(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.25',
        ])
            ->get(route('setup.first-admin'))
            ->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_remote_requests_cannot_spoof_the_loopback_peer(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.25',
            'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
        ])
            ->get(route('setup.first-admin'))
            ->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_first_admin_is_created_once_and_bootstrap_locks(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('setup.first-admin'))
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/setup/first-admin', [
                'name' => 'Hotel Administrator',
                'email' => 'ADMIN@example.test',
                'password' => 'A-strong-test-password-928!',
                'password_confirmation' => 'A-strong-test-password-928!',
                'status' => 'pending',
                'is_platform_admin' => false,
            ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status', 'Initial administrator created. Sign in to continue.');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Initial administrator created. Sign in to continue.');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
            'status' => 'active',
            'is_platform_admin' => true,
            'email_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@example.test',
            'password' => 'A-strong-test-password-928!',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->get(route('dashboard'))->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('setup.first-admin'))
            ->assertNotFound();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/setup/first-admin', [
                'name' => 'Second Administrator',
                'email' => 'second@example.test',
                'password' => 'A-strong-test-password-929!',
                'password_confirmation' => 'A-strong-test-password-929!',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_admin_input_does_not_consume_bootstrap(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->from('/setup/first-admin')
            ->post(route('setup.first-admin.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'weak',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('setup.first-admin'))
            ->assertOk();
    }

    public function test_existing_platform_admin_locks_first_admin_bootstrap(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('setup.first-admin'))
            ->assertNotFound();
    }
}
