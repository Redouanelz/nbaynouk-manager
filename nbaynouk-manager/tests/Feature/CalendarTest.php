<?php

namespace Tests\Feature;

use App\Enums\CalendarEventColor;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_calendar_routes(): void
    {
        $this->get('/calendar')->assertRedirect(route('login'));
        $this->postJson('/calendar/events', [])->assertUnauthorized();
    }

    public function test_authenticated_user_can_open_calendar_and_navigate_past_and_future_months(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/calendar?month=2024-01')->assertOk()->assertSee('Janvier 2024');
        $this->get('/calendar?month=2030-12')->assertOk()->assertSee('Décembre 2030');
        $this->get('/calendar?month=not-a-month')->assertOk()->assertSee(ucfirst(now()->translatedFormat('F Y')));
    }

    public function test_user_can_create_multiple_events_on_the_same_day(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $payload = ['title' => 'Tournage Bayt Al Musk', 'event_date' => '2026-08-29', 'color' => 'green'];
        $this->postJson('/calendar/events', $payload)->assertCreated()->assertJsonPath('event.color_label', 'Vert');
        $this->postJson('/calendar/events', [...$payload, 'title' => 'Réunion Compass'])->assertCreated();
        $this->assertDatabaseCount('calendar_events', 2);
        $this->assertSame('2026-08-29', CalendarEvent::firstOrFail()->event_date->toDateString());
        $this->assertSame($user->id, CalendarEvent::firstOrFail()->created_by);
    }

    public function test_user_can_update_title_color_and_date(): void
    {
        $this->actingAs(User::factory()->create());
        $event = CalendarEvent::create(['title' => 'Ancien titre', 'event_date' => '2026-08-29', 'color' => CalendarEventColor::Black]);
        $this->patchJson("/calendar/events/{$event->id}", ['title' => 'Nouveau titre', 'event_date' => '2026-08-30', 'color' => 'purple'])
            ->assertOk()->assertJsonPath('event.date', '2026-08-30')->assertJsonPath('event.color', 'purple');
        $updated = $event->fresh();
        $this->assertSame('Nouveau titre', $updated->title);
        $this->assertSame('2026-08-30', $updated->event_date->toDateString());
        $this->assertSame(CalendarEventColor::Purple, $updated->color);
    }

    public function test_user_can_delete_event(): void
    {
        $this->actingAs(User::factory()->create());
        $event = CalendarEvent::create(['title' => 'À supprimer', 'event_date' => '2026-08-29', 'color' => CalendarEventColor::Red]);
        $this->deleteJson("/calendar/events/{$event->id}")->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    public function test_event_validation_rejects_empty_title_invalid_date_and_color(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/calendar/events', ['title' => '', 'event_date' => '2026-02-30', 'color' => 'pink'])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'event_date', 'color']);
    }

    public function test_calendar_only_renders_events_in_visible_grid_range(): void
    {
        $this->actingAs(User::factory()->create());
        CalendarEvent::create(['title' => 'Visible August', 'event_date' => '2026-08-29', 'color' => CalendarEventColor::Blue]);
        CalendarEvent::create(['title' => 'Hidden October', 'event_date' => '2026-10-10', 'color' => CalendarEventColor::Green]);
        $this->get('/calendar?month=2026-08')->assertOk()->assertSee('Visible August')->assertDontSee('Hidden October');
    }
}
