<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillingPeriodRequest;
use App\Models\BillingPeriod;
use App\Models\Project;
use App\Services\BillingPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BillingPeriodController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['status' => ['nullable', 'string', 'in:paid,partial,unpaid,overdue,upcoming'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $periods = BillingPeriod::query()
            ->whereHas('project')
            ->with(['project.business.client', 'payments'])
            ->orderByDesc('due_date')
            ->get();
        if ($request->filled('status')) {
            $periods = $periods->filter(fn ($period) => match ($request->string('status')->toString()) {
                'upcoming' => $period->due_date?->isFuture() && $period->payment_status->value !== 'paid', default => $period->payment_status->value === $request->string('status')->toString()
            });
        }
        $page = max(1, $request->integer('page', 1));
        $periods = new LengthAwarePaginator($periods->forPage($page, 20), $periods->count(), 20, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('billing.index', compact('periods'));
    }

    public function create(Project $project): View
    {
        return view('billing.form', compact('project'));
    }

    public function store(BillingPeriodRequest $request, Project $project, BillingPeriodService $service): RedirectResponse
    {
        $service->create($project, $request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Période créée.');
    }

    public function next(Project $project, BillingPeriodService $service): RedirectResponse
    {
        $service->createNext($project);

        return back()->with('success', 'La période suivante a été générée.');
    }
}
