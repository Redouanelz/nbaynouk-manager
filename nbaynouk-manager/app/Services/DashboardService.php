<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\PaymentStatus;
use App\Enums\ProjectExpenseStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Models\ActivityLog;
use App\Models\BillingPeriod;
use App\Models\CalendarEvent;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectExpense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function data(): array
    {
        $start = today()->startOfMonth();
        $end = today()->endOfMonth();
        $activeProjects = Project::active()->with([
            'business.client', 'payments', 'expenses', 'billingPeriods.payments',
            'activeProjectServices',
        ])->withCount(['activeProjectServices', 'activeProjectServices as completed_services_count' => fn ($query) => $query->where('status', 'completed')])->get();

        $projectRows = $activeProjects->map(fn (Project $project) => $this->projectRow($project, $start, $end));
        $monthlyValue = $activeProjects->where('billing_type', BillingType::Monthly)->sum(fn (Project $project) => (float) $project->amount);
        $received = (float) Payment::query()->whereBetween('payment_date', [$start, $end])->sum('amount');
        $expenseMetrics = ProjectExpense::query()->whereBetween('expense_date', [$start, $end])->selectRaw(
            'coalesce(sum(amount), 0) as total, coalesce(sum(case when status = ? then amount else 0 end), 0) as paid, coalesce(sum(case when status = ? then amount else 0 end), 0) as pending',
            [ProjectExpenseStatus::Paid->value, ProjectExpenseStatus::Pending->value]
        )->first();
        $monthExpenses = (float) $expenseMetrics->total;
        $paidExpenses = (float) $expenseMetrics->paid;
        $pendingExpensesAmount = (float) $expenseMetrics->pending;
        $margin = $monthlyValue - $monthExpenses;

        $currentPeriods = BillingPeriod::query()->whereHas('project')->with(['project.business', 'payments'])
            ->where(fn ($query) => $query->whereBetween('due_date', [$start, today()->addDays(14)])->orWhere('due_date', '<', today()))->get();
        $outstanding = $currentPeriods->filter(fn (BillingPeriod $period) => bccomp($period->remaining_amount, '0', 2) === 1)->sum(fn (BillingPeriod $period) => (float) $period->remaining_amount);
        $upcomingPayments = $currentPeriods->filter(fn (BillingPeriod $period) => bccomp($period->remaining_amount, '0', 2) === 1)->sortBy('due_date')->take(6);

        $pendingExpenses = ProjectExpense::query()->with('project.business')->whereHas('project')->where('status', ProjectExpenseStatus::Pending->value)->orderByDesc('amount')->take(5)->get();
        $events = CalendarEvent::query()->whereBetween('event_date', [today(), today()->addDays(14)])->orderBy('event_date')->take(8)->get();
        $activities = ActivityLog::query()->with('project.business')->whereHas('project')->latest('occurred_at')->take(10)->get();
        $statusCounts = Project::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return [
            'metrics' => ['active' => $activeProjects->count(), 'monthly_value' => $monthlyValue, 'received' => $received, 'outstanding' => $outstanding, 'expenses' => $monthExpenses, 'paid_expenses' => $paidExpenses, 'pending_expenses' => $pendingExpensesAmount, 'margin' => $margin, 'profitability' => $monthlyValue > 0 ? round($margin / $monthlyValue * 100, 1) : null],
            'projects' => $projectRows->sortBy(fn (array $row) => $row['health_order'])->take(8),
            'topProjects' => $projectRows->whereNotNull('profitability')->sortByDesc('profitability')->take(5),
            'weakProjects' => $projectRows->whereNotNull('profitability')->where('profitability', '<', 50)->sortBy('profitability')->take(4),
            'alerts' => $this->alerts($currentPeriods, $projectRows, $pendingExpenses),
            'upcomingPayments' => $upcomingPayments,
            'pendingExpensesList' => $pendingExpenses,
            'events' => $events,
            'activities' => $activities,
            'statusCounts' => $statusCounts,
            'chart' => $this->chart(),
        ];
    }

    private function projectRow(Project $project, Carbon $start, Carbon $end): array
    {
        $expenses = $project->billing_type === BillingType::Monthly
            ? $project->expenses->whereBetween('expense_date', [$start, $end])->sum(fn ($expense) => (float) $expense->amount)
            : $project->expenses->sum(fn ($expense) => (float) $expense->amount);
        $value = (float) $project->amount;
        $profit = $value - $expenses;
        $profitability = $value > 0 ? round($profit / $value * 100, 1) : null;
        $overdue = $project->billingPeriods->contains(fn (BillingPeriod $period) => $period->payment_status === PaymentStatus::Overdue);
        $pending = $project->expenses->where('status', ProjectExpenseStatus::Pending)->sum(fn ($expense) => (float) $expense->amount);
        $health = $this->health($project, $profitability, $overdue, $pending);

        return compact('project', 'expenses', 'value', 'profit', 'profitability', 'health', 'overdue', 'pending') + ['paid' => (float) $project->total_paid, 'health_order' => array_search($health, [ProjectHealth::Critical, ProjectHealth::Watch, ProjectHealth::Good, ProjectHealth::Excellent], true)];
    }

    /** Critical: overdue, margin <20%, or an old launch below 20%. Watch: margin <50%, progress <40%, or pending expenses. */
    public function health(Project $project, ?float $profitability, bool $overdue, float $pending): ProjectHealth
    {
        if ($overdue || ($profitability !== null && $profitability < 20) || ($project->status === ProjectStatus::Launch && $project->progress_percentage < 20 && $project->created_at->lt(now()->subDays(30)))) {
            return ProjectHealth::Critical;
        }
        if (($profitability !== null && $profitability < 50) || $project->progress_percentage < 40 || $pending > 0) {
            return ProjectHealth::Watch;
        }
        if ($profitability !== null && $profitability >= 70 && $project->progress_percentage >= 50) {
            return ProjectHealth::Excellent;
        }

        return ProjectHealth::Good;
    }

    private function alerts(Collection $periods, Collection $projects, Collection $pendingExpenses): Collection
    {
        $alerts = collect();
        foreach ($periods->filter(fn ($period) => bccomp($period->remaining_amount, '0', 2) === 1) as $period) {
            $overdue = $period->due_date?->isBefore(today());
            $alerts->push(['priority' => $overdue ? 1 : 2, 'title' => $period->project->business->name, 'text' => $overdue ? 'Paiement en retard' : 'Paiement à venir', 'amount' => $period->remaining_amount, 'note' => $period->due_date?->diffForHumans(), 'url' => route('projects.show', $period->project).'#billing']);
        }
        foreach ($projects->whereIn('health', [ProjectHealth::Critical, ProjectHealth::Watch]) as $row) {
            $alerts->push(['priority' => $row['health'] === ProjectHealth::Critical ? 3 : 6, 'title' => $row['project']->business->name, 'text' => $row['health']->label().' · avancement '.$row['project']->progress_percentage.' %', 'amount' => null, 'note' => $row['profitability'] === null ? null : 'Rentabilité '.number_format($row['profitability'], 1, ',', ' ').' %', 'url' => route('projects.show', $row['project'])]);
        }
        if ($pendingExpenses->isNotEmpty()) {
            $alerts->push(['priority' => 4, 'title' => 'Charges à payer', 'text' => $pendingExpenses->count().' charge(s) prioritaire(s)', 'amount' => $pendingExpenses->sum('amount'), 'note' => null, 'url' => route('expenses.index')]);
        }

        return $alerts->sortBy('priority')->take(8);
    }

    private function chart(): Collection
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => today()->subMonths($offset)->startOfMonth());
        $start = $months->first();
        $payments = Payment::query()->where('payment_date', '>=', $start)->get(['amount', 'payment_date'])->groupBy(fn ($item) => $item->payment_date->format('Y-m'));
        $expenses = ProjectExpense::query()->where('expense_date', '>=', $start)->get(['amount', 'expense_date'])->groupBy(fn ($item) => $item->expense_date->format('Y-m'));

        return $months->map(fn (Carbon $month) => ['label' => $month->translatedFormat('M'), 'payments' => (float) ($payments[$month->format('Y-m')] ?? collect())->sum('amount'), 'expenses' => (float) ($expenses[$month->format('Y-m')] ?? collect())->sum('amount')]);
    }
}
