<?php

namespace Tests\Feature\Bootstrap;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_status_is_available_only_to_platform_admins(): void
    {
        $this->get('/operator/health')
            ->assertRedirect(route('login'));

        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get('/operator/health')
            ->assertNotFound();

        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->actingAs($admin)
            ->get('/operator/health')
            ->assertOk()
            ->assertExactJson([
                'application' => 'ok',
                'database' => 'ok',
                'storage' => 'ok',
                'schema' => 'ok',
            ]);
    }

    public function test_database_failure_returns_only_a_generic_unavailable_status(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andThrow(new RuntimeException('sensitive database diagnostic'));

        $this->actingAs($admin)
            ->get('/operator/health')
            ->assertServiceUnavailable()
            ->assertJsonPath('database', 'unavailable')
            ->assertJsonPath('schema', 'unavailable')
            ->assertDontSee('sensitive database diagnostic');
    }

    public function test_database_probe_can_succeed_while_missing_core_schema_still_returns_503(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        Schema::shouldReceive('hasTable')
            ->andReturnUsing(fn (string $table): bool => $table !== 'rooms');

        $this->actingAs($admin)
            ->get('/operator/health')
            ->assertServiceUnavailable()
            ->assertExactJson([
                'application' => 'ok',
                'database' => 'ok',
                'storage' => 'ok',
                'schema' => 'unavailable',
            ]);
    }

    public function test_database_sessions_table_is_required_for_readiness(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        config(['session.driver' => 'database']);

        Schema::shouldReceive('hasTable')
            ->andReturnUsing(fn (string $table): bool => $table !== 'sessions');

        $this->actingAs($admin)
            ->get('/operator/health')
            ->assertServiceUnavailable()
            ->assertJsonPath('database', 'ok')
            ->assertJsonPath('schema', 'unavailable');
    }

    public function test_health_response_does_not_include_environment_or_credentials(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $response = $this->actingAs($admin)->get('/operator/health');

        $this->assertStringNotContainsString('APP_KEY', $response->getContent());
        $this->assertStringNotContainsString('password', strtolower($response->getContent()));
        $this->assertStringNotContainsString('database.default', $response->getContent());
    }
}
