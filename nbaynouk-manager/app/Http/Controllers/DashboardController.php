<?php

namespace App\Http\Controllers;

use App\Enums\BillingType;
use App\Enums\ProjectStatus;
use App\Models\BillingPeriod;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeProjects = Project::active();
        $activeCount = (clone $activeProjects)->count();
        $monthlyValue = (clone $activeProjects)->where('billing_type', BillingType::Monthly->value)->sum('amount');
        $receivedThisMonth = Payment::query()->whereBetween('payment_date', [today()->startOfMonth(), today()->endOfMonth()])->sum('amount');
        // Exclude periods whose project was archived (soft deleted). Their
        // belongsTo relation resolves to null and they no longer belong in
        // current dashboard totals or links.
        $periods = BillingPeriod::query()
            ->whereHas('project')
            ->with(['payments', 'project.business.client'])
            ->get();
        $outstanding = $periods->reduce(fn (string $sum, BillingPeriod $period) => bcadd($sum, $period->remaining_amount, 2), '0.00');
        $pendingCount = $periods->filter(fn (BillingPeriod $period) => bccomp($period->remaining_amount, '0', 2) === 1)->count();
        $watchPeriods = $periods->filter(fn (BillingPeriod $period) => $period->payment_status->value === 'overdue' || ($period->due_date?->between(today(), today()->addDays(7)) && bccomp($period->remaining_amount, '0', 2) === 1))->sortBy('due_date')->take(5);
        $waitingProjects = Project::query()->with('business.client')->status(ProjectStatus::Waiting)->latest()->take(4)->get();
        $recentProjects = Project::query()->with(['business.client', 'payments'])
            ->withCount(['activeProjectServices', 'activeProjectServices as completed_services_count' => fn ($q) => $q->where('status', 'completed')])
            ->latest()->take(7)->get();
        $statusCounts = Project::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $createdThisMonth = Project::query()->whereBetween('created_at', [today()->startOfMonth(), today()->endOfMonth()])->count();

        return view('dashboard', compact('activeCount', 'monthlyValue', 'receivedThisMonth', 'outstanding', 'pendingCount', 'watchPeriods', 'waitingProjects', 'recentProjects', 'statusCounts', 'createdThisMonth'));
    }
}
