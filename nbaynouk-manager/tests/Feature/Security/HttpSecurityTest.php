<?php

namespace Tests\Feature\Security;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_include_security_headers(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_state_changing_request_without_csrf_token_is_rejected(): void
    {
        $this->withMiddleware(VerifyCsrfToken::class);
        $this->app['env'] = 'local';
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $originalNotes = $project->notes;

        $this->patch(route('projects.notes', $project), ['notes' => 'Ne doit pas être enregistré'])->assertStatus(419);
        $this->assertSame($originalNotes, $project->fresh()->notes);
    }

    public function test_destructive_actions_do_not_accept_get_requests(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $this->get('/projects/'.$project->id.'/delete')->assertNotFound();
        $this->get('/payments/1/delete')->assertNotFound();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    public function test_soft_deleted_project_is_not_route_bindable(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $project->delete();

        $this->get(route('projects.show', $project))->assertNotFound();
        $this->patch(route('projects.notes', $project), ['notes' => 'test'])->assertNotFound();
    }
}
