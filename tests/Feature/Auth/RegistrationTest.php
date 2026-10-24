<?php

namespace Tests\Feature\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('An administrator must approve your account before you can sign in.');
    }

    public function test_pending_users_cannot_log_in(): void
    {
        $user = User::factory()->create(['status' => 'pending']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_approved_users_can_log_in(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_password_policy_does_not_call_external_compromise_verifier(): void
    {
        $this->skipUnlessFortifyHas(Features::registration());
        app()['env'] = 'production';

        app()->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            public function verify($data)
            {
                throw new \RuntimeException('External password verifier must not be called.');
            }
        });

        app(CreateNewUser::class)->create([
            'name' => 'Offline Staff',
            'email' => 'offline@example.test',
            'password' => 'Local-Only-Strong-Password-385!',
            'password_confirmation' => 'Local-Only-Strong-Password-385!',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'offline@example.test',
            'status' => 'pending',
        ]);
    }

    public function test_json_registration_keeps_created_response_without_authentication(): void
    {
        $response = $this->postJson(route('register.store'), [
            'name' => 'API Pending',
            'email' => 'api-pending@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'api-pending@example.test',
            'status' => 'pending',
        ]);
    }

    public function test_pending_registration_does_not_send_email_synchronously(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Offline Pending',
            'email' => 'pending-offline@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'pending-offline@example.test',
            'status' => 'pending',
        ]);
        Notification::assertNothingSent();
    }

    public function test_registration_endpoint_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('register.store'), [
                'name' => 'Pending User '.$attempt,
                'email' => 'throttled-'.$attempt.'@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertRedirect(route('login', absolute: false));
        }

        $this->post(route('register.store'), [
            'name' => 'Pending User 6',
            'email' => 'throttled-6@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertTooManyRequests();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status', 'Your registration was submitted for approval. Sign in after an administrator approves your account.');

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'status' => 'pending']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Your registration was submitted for approval. Sign in after an administrator approves your account.');
    }
}
