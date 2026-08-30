<?php

namespace App\Http\Controllers;

use App\Enums\BillingType;
use App\Enums\ProjectExpenseStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseOverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'profitability' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'sort' => ['nullable', Rule::in(['project', 'expenses', 'profit', 'profitability'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $expenseSql = $this->expenseAggregateSql();
        $query = Project::query()
            ->with('business.client')
            ->select('projects.*')
            ->selectRaw("({$expenseSql}) as overview_expenses")
            ->selectRaw("projects.amount - ({$expenseSql}) as overview_profit")
            ->selectRaw("case when projects.amount > 0 then ((projects.amount - ({$expenseSql})) * 100.0 / projects.amount) else null end as overview_margin")
            ->selectRaw('(select count(*) from project_expenses where project_expenses.project_id = projects.id and '.$this->expenseScopeSql().') as overview_expenses_count')
            ->selectRaw('(select coalesce(sum(amount), 0) from project_expenses where project_expenses.project_id = projects.id and status = ? and '.$this->expenseScopeSql().') as overview_paid', [ProjectExpenseStatus::Paid->value])
            ->selectRaw('(select coalesce(sum(amount), 0) from project_expenses where project_expenses.project_id = projects.id and status = ? and '.$this->expenseScopeSql().') as overview_pending', [ProjectExpenseStatus::Pending->value])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $query->where(fn (Builder $query) => $query->where('projects.name', 'like', $term)->orWhere('projects.code', 'like', $term)
                    ->orWhereHas('business', fn (Builder $query) => $query->where('name', 'like', $term)->orWhereHas('client', fn (Builder $query) => $query->where('name', 'like', $term))));
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->status($request->string('status')->toString()));

        $this->applyProfitabilityFilter($query, $validated['profitability'] ?? null, $expenseSql);

        $sort = $validated['sort'] ?? null;
        $direction = $validated['direction'] ?? 'desc';
        match ($sort) {
            'project' => $query->orderBy('projects.name', $direction),
            'expenses' => $query->orderBy('overview_expenses', $direction),
            'profit' => $query->orderBy('overview_profit', $direction),
            'profitability' => $query->orderBy('overview_margin', $direction),
            default => $query->orderByRaw("case when projects.status in ('onboarding','launch','suivi') then 0 else 1 end")->latest('projects.created_at'),
        };

        $all = (clone $query)->reorder()->get();
        $projects = $query->paginate(15)->withQueryString();
        $kpis = [
            'total' => $all->sum(fn (Project $project) => (float) $project->overview_expenses),
            'paid' => $all->sum(fn (Project $project) => (float) $project->overview_paid),
            'pending' => $all->sum(fn (Project $project) => (float) $project->overview_pending),
            'profit' => $all->sum(fn (Project $project) => (float) $project->overview_profit),
        ];

        return view('expenses.index', ['projects' => $projects, 'kpis' => $kpis, 'statuses' => ProjectStatus::cases()]);
    }

    private function expenseAggregateSql(): string
    {
        return '(select coalesce(sum(amount), 0) from project_expenses where project_expenses.project_id = projects.id and '.$this->expenseScopeSql().')';
    }

    private function expenseScopeSql(): string
    {
        $start = today()->startOfMonth()->toDateString();
        $end = today()->endOfMonth()->toDateString();

        return "(projects.billing_type != '".BillingType::Monthly->value."' or ((project_expenses.billing_period_id is null and project_expenses.expense_date between '{$start}' and '{$end}') or exists (select 1 from billing_periods where billing_periods.id = project_expenses.billing_period_id and billing_periods.period_start <= '{$end}' and billing_periods.period_end >= '{$start}')))";
    }

    private function applyProfitabilityFilter(Builder $query, ?string $level, string $expenseSql): void
    {
        if ($level === null) {
            return;
        }
        $marginSql = "((projects.amount - ({$expenseSql})) * 100.0 / nullif(projects.amount, 0))";
        match ($level) {
            'high' => $query->whereRaw("{$marginSql} >= 70"),
            'medium' => $query->whereRaw("{$marginSql} >= 40 and {$marginSql} < 70"),
            'low' => $query->whereRaw("{$marginSql} < 40"),
        };
    }
}
