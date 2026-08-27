<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSidebarShortcutsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_sidebar_displays_counts_for_non_archived_projects(): void
    {
        Project::factory()->count(3)->create(['status' => ProjectStatus::Launch]);
        Project::factory()->count(5)->create(['status' => ProjectStatus::Suivi]);
        Project::factory()->create(['status' => ProjectStatus::Completed]);

        $archived = Project::factory()->create(['status' => ProjectStatus::Launch]);
        $archived->delete();

        $this->get('/projects')->assertOk()->assertSeeInOrder([
            'Tous les projets',
            '<span class="nav-count">9</span>',
            'En lancement',
            '<span class="nav-count">3</span>',
            'En suivi',
            '<span class="nav-count">5</span>',
        ], false);
    }

    public function test_launch_and_suivi_shortcuts_filter_projects_and_update_the_title(): void
    {
        Project::factory()->create(['name' => 'Lancement Alpha', 'status' => ProjectStatus::Launch]);
        Project::factory()->create(['name' => 'Suivi Alpha', 'status' => ProjectStatus::Suivi]);

        $this->get('/projects?status=launch')
            ->assertOk()
            ->assertSee('Projets en lancement')
            ->assertSee('Lancement Alpha')
            ->assertDontSee('Suivi Alpha');

        $this->get('/projects?status=suivi')
            ->assertOk()
            ->assertSee('Projets en suivi')
            ->assertSee('Suivi Alpha')
            ->assertDontSee('Lancement Alpha');
    }

    public function test_search_and_status_filters_can_be_combined(): void
    {
        Project::factory()->create(['name' => 'Lancement Alpha', 'status' => ProjectStatus::Launch]);
        Project::factory()->create(['name' => 'Lancement Beta', 'status' => ProjectStatus::Launch]);
        Project::factory()->create(['name' => 'Suivi Alpha', 'status' => ProjectStatus::Suivi]);

        $this->get('/projects?status=launch&search=Alpha')
            ->assertOk()
            ->assertSee('Lancement Alpha')
            ->assertDontSee('Lancement Beta')
            ->assertDontSee('Suivi Alpha');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->from('/projects')->get('/projects?status=invalid')
            ->assertRedirect('/projects')
            ->assertSessionHasErrors('status');
    }
}
