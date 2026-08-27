<?php

namespace Tests\Feature;

use App\Enums\ProjectServiceStatus;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_calculated_for_zero_sixty_and_one_hundred_percent(): void
    {
        $project = Project::factory()->create();
        $items = collect(range(1, 5))->map(fn (int $i) => $this->attach($project, "Service {$i}"));
        $this->assertSame(0, $project->progress_percentage);
        $items->take(3)->each->update(['status' => ProjectServiceStatus::Completed, 'completed_at' => now()]);
        $this->assertSame(60, $project->fresh()->progress_percentage);
        $items->each->update(['status' => ProjectServiceStatus::Completed, 'completed_at' => now()]);
        $this->assertSame(100, $project->fresh()->progress_percentage);
    }

    public function test_toggle_updates_status_timestamp_progress_and_activity(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $item = $this->attach($project, 'Site web');
        $url = route('project-services.toggle', [$project, $item]);

        $this->patchJson($url)->assertOk()->assertJson(['status' => 'completed', 'progress' => 100]);
        $this->assertNotNull($item->fresh()->completed_at);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'type' => 'service_completed']);
        $this->patchJson($url)->assertOk()->assertJson(['status' => 'pending', 'progress' => 0]);
        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_notes_can_be_updated_and_cross_project_access_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $item = $this->attach($project, 'Script');

        $this->patchJson(route('project-services.update', [$project, $item]), ['notes' => 'Version finale validée.'])->assertOk();
        $this->assertSame('Version finale validée.', $item->fresh()->notes);
        $this->patchJson(route('project-services.toggle', [$other, $item]))->assertNotFound();
    }

    public function test_attachment_upload_and_delete_manage_database_and_storage(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $item = $this->attach($project, 'Shooting');

        $this->postJson(route('project-service-attachments.store', [$project, $item]), ['files' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')]])->assertCreated();
        $attachment = $item->attachments()->firstOrFail();
        Storage::disk('public')->assertExists($attachment->file_path);
        $this->get(route('project-service-attachments.show', [$project, $item, $attachment]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->deleteJson(route('project-service-attachments.destroy', [$project, $item, $attachment]))->assertOk();
        Storage::disk('public')->assertMissing($attachment->file_path);
        $this->assertDatabaseMissing('project_service_attachments', ['id' => $attachment->id]);
    }

    public function test_invalid_and_unauthenticated_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $item = $this->attach($project, 'Design');
        $url = route('project-service-attachments.store', [$project, $item]);

        $this->post($url, ['files' => [UploadedFile::fake()->create('virus.php', 2, 'application/x-php')]])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->postJson($url, ['files' => [UploadedFile::fake()->create('virus.exe', 2, 'application/octet-stream')]])->assertUnprocessable()->assertJsonValidationErrors('files.0');
    }

    public function test_deactivated_services_are_excluded_and_reactivated_without_data_loss(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $service = Service::create(['name' => 'Scénario', 'slug' => 'scenario']);
        $item = ProjectService::create(['project_id' => $project->id, 'service_id' => $service->id, 'notes' => 'À conserver', 'status' => ProjectServiceStatus::Completed, 'completed_at' => now()]);
        $payload = $this->projectPayload($project);

        $this->put(route('projects.update', $project), $payload + ['services' => []])->assertRedirect();
        $this->assertSame(0, $project->fresh()->total_services_count);
        $this->put(route('projects.update', $project), $payload + ['services' => [$service->id]])->assertRedirect();
        $this->assertTrue($item->fresh()->is_active);
        $this->assertSame('À conserver', $item->notes);
    }

    private function attach(Project $project, string $name): ProjectService
    {
        $service = Service::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::random(6)]);

        return ProjectService::create(['project_id' => $project->id, 'service_id' => $service->id]);
    }

    private function projectPayload(Project $project): array
    {
        return ['business_id' => $project->business_id, 'name' => $project->name, 'status' => $project->status->value, 'billing_type' => $project->billing_type->value, 'amount' => $project->amount, 'currency' => $project->currency];
    }
}
