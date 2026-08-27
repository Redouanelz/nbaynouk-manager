<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectServiceAttachmentRequest;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\ProjectServiceAttachment;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectServiceAttachmentController extends Controller
{
    public function show(Project $project, ProjectService $projectService, ProjectServiceAttachment $attachment): BinaryFileResponse
    {
        $this->assertRelation($project, $projectService);
        abort_unless($attachment->project_service_id === $projectService->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return response()->file(Storage::disk('local')->path($attachment->file_path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.str_replace(['"', "\r", "\n"], '', $attachment->original_name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(StoreProjectServiceAttachmentRequest $request, Project $project, ProjectService $projectService, ActivityLogService $activity): JsonResponse
    {
        $this->assertRelation($project, $projectService);
        $created = [];
        try {
            DB::transaction(function () use ($request, $projectService, $activity, &$created): void {
                foreach ($request->file('files') as $file) {
                    $path = $file->store("project-services/{$projectService->id}", 'local');
                    $created[] = $projectService->attachments()->create(['file_path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize()]);
                }
                $name = $projectService->service->name;
                $activity->record($projectService->project, 'service_attachment_added', 'Une pièce jointe a été ajoutée à '.$name.'.', ['service_id' => $projectService->service_id, 'service_name' => $name]);
            });
        } catch (\Throwable $e) {
            foreach ($created as $attachment) {
                Storage::disk('local')->delete($attachment->file_path);
            }
            throw $e;
        }

        return response()->json(['success' => true, 'message' => count($created) > 1 ? 'Pièces jointes ajoutées.' : 'Pièce jointe ajoutée.', 'reload' => true], 201);
    }

    public function destroy(Project $project, ProjectService $projectService, ProjectServiceAttachment $attachment, ActivityLogService $activity): JsonResponse
    {
        $this->assertRelation($project, $projectService);
        abort_unless($attachment->project_service_id === $projectService->id, 404);
        $path = $attachment->file_path;
        DB::transaction(function () use ($attachment, $projectService, $activity): void {
            $attachment->delete();
            $name = $projectService->service->name;
            $activity->record($projectService->project, 'service_attachment_deleted', 'Une pièce jointe a été supprimée de '.$name.'.', ['service_id' => $projectService->service_id, 'service_name' => $name]);
        });
        Storage::disk('local')->delete($path);

        return response()->json(['success' => true, 'message' => 'Pièce jointe supprimée.']);
    }

    private function assertRelation(Project $project, ProjectService $projectService): void
    {
        abort_unless($projectService->project_id === $project->id && $projectService->is_active, 404);
    }
}
