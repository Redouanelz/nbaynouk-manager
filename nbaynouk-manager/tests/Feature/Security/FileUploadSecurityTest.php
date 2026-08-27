<?php

namespace Tests\Feature\Security;

use App\Models\Project;
use App\Models\ProjectService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_executable_and_oversized_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        [$project, $item] = $this->projectService();
        $url = route('project-service-attachments.store', [$project, $item]);

        $files = [
            UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            UploadedFile::fake()->create('test.php.jpg', 1, 'application/x-php'),
            UploadedFile::fake()->create('malware.exe', 1, 'application/octet-stream'),
            UploadedFile::fake()->create('document.html', 1, 'text/html'),
            UploadedFile::fake()->create('script.js', 1, 'text/javascript'),
            UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ];

        foreach ($files as $file) {
            $this->postJson($url, ['files' => [$file]])->assertUnprocessable()->assertJsonValidationErrors('files.0');
        }

        $this->assertDatabaseCount('project_service_attachments', 0);
    }

    public function test_physical_filename_is_generated_and_path_traversal_name_is_only_metadata(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        [$project, $item] = $this->projectService();

        $this->postJson(route('project-service-attachments.store', [$project, $item]), [
            'files' => [UploadedFile::fake()->create('../../secret.png', 10, 'image/png')],
        ])->assertCreated();

        $attachment = $item->attachments()->firstOrFail();
        $this->assertStringNotContainsString('..', $attachment->file_path);
        $this->assertStringNotContainsString('secret.png', $attachment->file_path);
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    public function test_attachment_cannot_be_read_or_deleted_through_another_project(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        [$projectA, $item] = $this->projectService();
        $projectB = Project::factory()->create();
        $attachment = $item->attachments()->create(['file_path' => 'project-services/file.pdf', 'original_name' => 'file.pdf', 'mime_type' => 'application/pdf']);
        Storage::disk('local')->put($attachment->file_path, 'pdf');

        $this->get(route('project-service-attachments.show', [$projectB, $item, $attachment]))->assertNotFound();
        $this->deleteJson(route('project-service-attachments.destroy', [$projectB, $item, $attachment]))->assertNotFound();
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    private function projectService(): array
    {
        $project = Project::factory()->create();
        $service = Service::create(['name' => 'Sécurité', 'slug' => 'securite-'.uniqid()]);
        $item = ProjectService::create(['project_id' => $project->id, 'service_id' => $service->id]);

        return [$project, $item];
    }
}
