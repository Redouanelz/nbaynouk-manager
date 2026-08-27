<?php

namespace App\Http\Controllers;

use App\Enums\ProjectServiceStatus;
use App\Http\Requests\UpdateProjectServiceRequest;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\Service;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectServiceController extends Controller
{
    public function toggle(Project $project, ProjectService $projectService, ActivityLogService $activity): JsonResponse
    {
        $this->assertBelongsToProject($project, $projectService);

        DB::transaction(function () use ($projectService, $activity): void {
            $completed = $projectService->status !== ProjectServiceStatus::Completed;
            $projectService->update([
                'status' => $completed ? ProjectServiceStatus::Completed : ProjectServiceStatus::Pending,
                'completed_at' => $completed ? now() : null,
            ]);
            $name = $projectService->service->name;
            $activity->record($projectService->project, $completed ? 'service_completed' : 'service_reopened', $completed ? "{$name} a été marqué comme terminé." : "{$name} a été remis à faire.", ['service_id' => $projectService->service_id, 'service_name' => $name]);
        });

        return response()->json($this->payload($projectService->fresh(['service']), $project));
    }

    public function update(UpdateProjectServiceRequest $request, Project $project, ProjectService $projectService, ActivityLogService $activity): JsonResponse
    {
        $this->assertBelongsToProject($project, $projectService);
        DB::transaction(function () use ($request, $projectService, $activity): void {
            $changed = $projectService->notes !== $request->validated('notes');
            $projectService->update($request->validated());
            if ($changed) {
                $activity->record($projectService->project, 'service_note_updated', "La note de {$projectService->service->name} a été mise à jour.", ['service_id' => $projectService->service_id, 'service_name' => $projectService->service->name]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Informations mises à jour.']);
    }

    public function storeCustom(Request $request, Project $project, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $projectService = DB::transaction(function () use ($data, $project, $activity): ProjectService {
            $service = Service::create(['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.$project->id.'-'.Str::lower(Str::random(8)), 'is_custom' => true, 'created_for_project_id' => $project->id]);
            $projectService = $project->projectServices()->create(['service_id' => $service->id]);
            $activity->record($project, 'service_updated', "Le service {$service->name} a été ajouté au projet.", ['service_id' => $service->id, 'service_name' => $service->name]);

            return $projectService;
        });

        return response()->json(['success' => true, 'message' => 'Service personnalisé ajouté.', 'reload' => true, 'id' => $projectService->id], 201);
    }

    private function assertBelongsToProject(Project $project, ProjectService $projectService): void
    {
        abort_unless($projectService->project_id === $project->id && $projectService->is_active, 404);
    }

    private function payload(ProjectService $projectService, Project $project): array
    {
        $project->unsetRelation('activeProjectServices')->refresh();

        return [
            'success' => true,
            'status' => $projectService->status->value,
            'status_label' => $projectService->status->label(),
            'progress' => $project->progress_percentage,
            'completed_count' => $project->completed_services_count,
            'total_count' => $project->total_services_count,
            'completed_at' => $projectService->completed_at?->translatedFormat('d F Y'),
            'message' => $projectService->status === ProjectServiceStatus::Completed ? 'Service marqué comme terminé.' : 'Service marqué comme à faire.',
        ];
    }
}
