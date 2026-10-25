<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_desktop_and_mobile_menus_offer_all_appearance_modes(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'));

        $response->assertOk();
        $html = $response->getContent();

        foreach (['light', 'dark', 'system'] as $mode) {
            $this->assertSame(2, substr_count($html, 'data-theme-mode="'.$mode.'"'));
        }

        $response->assertSee("\$flux.appearance = 'light'", false)
            ->assertSee("\$flux.appearance = 'dark'", false)
            ->assertSee("\$flux.appearance = 'system'", false);
    }

    public function test_appearance_settings_page_labels_system_mode_as_default(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertSee('Light')
            ->assertSee('Dark')
            ->assertSee('System default');
    }

    public function test_membership_notice_uses_dark_mode_colors(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.memberships.index'))
            ->assertOk()
            ->assertSee('dark:bg-amber-950 dark:text-amber-100', false);
    }
}
