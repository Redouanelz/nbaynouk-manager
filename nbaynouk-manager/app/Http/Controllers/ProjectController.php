<?php

namespace App\Http\Controllers;

use App\Enums\BillingType;
use App\Enums\ProjectStatus;
use App\Http\Requests\ProjectRequest;
use App\Models\Business;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\Service;
use App\Models\TeamMember;
use App\Services\ActivityLogService;
use App\Services\BillingPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()->with(['business.client', 'payments'])
            ->withCount(['activeProjectServices', 'activeProjectServices as completed_services_count' => fn ($q) => $q->where('status', 'completed')])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)->orWhere('code', 'like', $term)
                    ->orWhereHas('business', fn ($q) => $q->where('name', 'like', $term)->orWhereHas('client', fn ($q) => $q->where('name', 'like', $term)));
            }))
            ->when($request->filled('status'), fn ($q) => $q->status($request->string('status')->toString()))
            ->when($request->filled('billing_type'), fn ($q) => $q->billingType($request->string('billing_type')->toString()))
            ->latest()->paginate(15)->withQueryString();

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.form', $this->formData());
    }

    public function store(ProjectRequest $request, ActivityLogService $activity): RedirectResponse
    {
        $project = DB::transaction(function () use ($request, $activity): Project {
            $project = Project::create($request->safe()->except(['services', 'team', 'create_first_period', 'due_date']));
            $this->syncServices($project, $request->input('services', []));
            $project->teamMembers()->sync($this->teamPayload($request));
            $activity->record($project, 'project_created', 'Projet créé.');

            if ($request->boolean('create_first_period') && $project->billing_type === BillingType::Monthly) {
                app(BillingPeriodService::class)->create($project, [
                    'period_start' => $project->start_date ?? today()->startOfMonth(),
                    'period_end' => ($project->start_date ?? today())->copy()->endOfMonth(),
                    'amount' => $project->amount,
                    'due_date' => $request->date('due_date') ?? today()->addDays(10),
                    'description' => 'Première période',
                ]);
            }

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('success', 'Projet créé avec succès.');
    }

    public function show(Project $project): View
    {
        $project->load(['business.client', 'activeProjectServices' => fn ($q) => $q->with(['service', 'attachments'])->orderBy('created_at'), 'teamMembers', 'billingPeriods.payments', 'payments.billingPeriod', 'activityLogs' => fn ($q) => $q->latest('occurred_at')]);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        return view('projects.form', $this->formData() + compact('project'));
    }

    public function update(ProjectRequest $request, Project $project, ActivityLogService $activity): RedirectResponse
    {
        DB::transaction(function () use ($request, $project, $activity): void {
            $oldStatus = $project->status;
            $oldServices = $project->activeProjectServices()->pluck('service_id')->all();
            $oldTeam = $project->teamMembers()->pluck('team_members.id')->all();
            $project->update($request->safe()->except(['services', 'team', 'create_first_period', 'due_date']));
            $this->syncServices($project, $request->input('services', []));
            $project->teamMembers()->sync($this->teamPayload($request));
            $activity->record($project, 'project_updated', 'Projet mis à jour.');
            if ($oldStatus !== $project->status) {
                $activity->record($project, 'status_changed', "Statut modifié : {$oldStatus->label()} → {$project->status->label()}.");
            }
            if ($oldServices !== $project->activeProjectServices()->pluck('service_id')->all()) {
                $activity->record($project, 'service_updated', 'Les services du projet ont été modifiés.');
            }
            if ($oldTeam !== $project->teamMembers()->pluck('team_members.id')->all()) {
                $activity->record($project, 'team_updated', 'L’équipe du projet a été modifiée.');
            }
        });

        return redirect()->route('projects.show', $project)->with('success', 'Projet mis à jour.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Projet archivé.');
    }

    public function updateNotes(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string']]);
        $project->update($data);
        app(ActivityLogService::class)->record($project, 'project_updated', 'Les notes internes ont été mises à jour.');

        return back()->with('success', 'Notes enregistrées.');
    }

    private function formData(): array
    {
        return ['clients' => Client::with('businesses')->orderBy('name')->get(), 'businesses' => Business::with('client')->orderBy('name')->get(), 'services' => Service::where('is_custom', false)->orderBy('name')->get(), 'teamMembers' => TeamMember::active()->orderBy('name')->get(), 'statuses' => ProjectStatus::cases(), 'billingTypes' => BillingType::cases()];
    }

    private function teamPayload(ProjectRequest $request): array
    {
        return collect($request->input('team', []))->filter(fn ($member) => ! empty($member['selected']))->mapWithKeys(fn ($member, $id) => [$id => ['role' => $member['role'] ?? null]])->all();
    }

    private function syncServices(Project $project, array $serviceIds): void
    {
        $serviceIds = collect($serviceIds)->map(fn ($id) => (int) $id)->unique();
        $project->projectServices()->whereNotIn('service_id', $serviceIds)->update(['is_active' => false]);
        foreach ($serviceIds as $serviceId) {
            ProjectService::query()->updateOrCreate(['project_id' => $project->id, 'service_id' => $serviceId], ['is_active' => true]);
        }
    }
}
