<?php

namespace Tests\Feature;

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_appearance(): void
    {
        $this->patchJson(route('settings.appearance.update'), ['theme' => Theme::DarkGold->value])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_switch_to_dark_gold_and_it_persists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson(route('settings.appearance.update'), ['theme' => Theme::DarkGold->value])
            ->assertOk()->assertExactJson(['success' => true, 'theme' => Theme::DarkGold->value]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'theme' => Theme::DarkGold->value]);
    }

    public function test_invalid_theme_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->patchJson(route('settings.appearance.update'), ['theme' => 'casino'])
            ->assertUnprocessable()->assertJsonValidationErrors('theme');
    }

    public function test_user_only_updates_their_own_preference(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create(['theme' => Theme::Light]);

        $this->actingAs($userA)->patchJson(route('settings.appearance.update'), ['theme' => Theme::DarkGold->value])->assertOk();

        $this->assertSame(Theme::Light, $userB->fresh()->theme);
        $this->assertSame(Theme::DarkGold, $userA->fresh()->theme);
    }

    public function test_layout_renders_the_users_theme_on_html_element(): void
    {
        $user = User::factory()->create(['theme' => Theme::DarkGold]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('data-theme="dark-gold"', false);
    }

    public function test_theme_defaults_to_light(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Theme::Light, $user->fresh()->theme);
        $this->actingAs($user)->get(route('dashboard'))->assertSee('data-theme="light"', false);
    }
}
