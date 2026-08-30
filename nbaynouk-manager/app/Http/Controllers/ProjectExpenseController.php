<?php

namespace App\Http\Controllers;

use App\Enums\ProjectExpenseStatus;
use App\Http\Requests\StoreProjectExpenseRequest;
use App\Http\Requests\UpdateProjectExpenseRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\ActivityLogService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectExpenseController extends Controller
{
    public function store(StoreProjectExpenseRequest $request, Project $project, ActivityLogService $activity): JsonResponse
    {
        $expense = DB::transaction(function () use ($request, $project, $activity): ProjectExpense {
            $expense = $project->expenses()->create($request->validated() + ['created_by' => $request->user()->id]);
            $activity->record($project, 'project_expense_created', sprintf('Charge « %s » de %s ajoutée.', $expense->label, Money::format($expense->amount)), $this->metadata($expense));

            return $expense;
        });

        return response()->json($this->response($project, $expense, 'Charge ajoutée avec succès.'), 201);
    }

    public function update(UpdateProjectExpenseRequest $request, Project $project, ProjectExpense $projectExpense, ActivityLogService $activity): JsonResponse
    {
        $this->ensureOwned($project, $projectExpense);
        DB::transaction(function () use ($request, $project, $projectExpense, $activity): void {
            $oldStatus = $projectExpense->status;
            $projectExpense->update($request->validated());
            $activity->record($project, 'project_expense_updated', sprintf('Charge « %s » mise à jour.', $projectExpense->label), $this->metadata($projectExpense));
            if ($oldStatus !== $projectExpense->status) {
                $type = $projectExpense->status === ProjectExpenseStatus::Paid ? 'project_expense_marked_paid' : 'project_expense_marked_pending';
                $activity->record($project, $type, sprintf('Charge « %s » marquée comme %s.', $projectExpense->label, mb_strtolower($projectExpense->status->label())), $this->metadata($projectExpense));
            }
        });

        return response()->json($this->response($project, $projectExpense, 'Charge modifiée avec succès.'));
    }

    public function destroy(Project $project, ProjectExpense $projectExpense, ActivityLogService $activity): JsonResponse
    {
        $this->ensureOwned($project, $projectExpense);
        DB::transaction(function () use ($project, $projectExpense, $activity): void {
            $metadata = $this->metadata($projectExpense);
            $label = $projectExpense->label;
            $projectExpense->delete();
            $activity->record($project, 'project_expense_deleted', sprintf('Charge « %s » supprimée.', $label), $metadata);
        });

        return response()->json(['message' => 'Charge supprimée.', 'kpis' => $this->kpis($project)]);
    }

    private function ensureOwned(Project $project, ProjectExpense $expense): void
    {
        abort_unless($expense->project_id === $project->id, 404);
    }

    private function metadata(ProjectExpense $expense): array
    {
        return ['expense_id' => $expense->id, 'label' => $expense->label, 'amount' => $expense->amount];
    }

    private function response(Project $project, ProjectExpense $expense, string $message): array
    {
        $expense->load(['service', 'billingPeriod']);

        return ['message' => $message, 'expense' => ['id' => $expense->id, 'label' => $expense->label, 'amount' => $expense->amount, 'formatted_amount' => Money::format($expense->amount), 'category' => $expense->category?->value, 'category_label' => $expense->category?->label() ?? '—', 'status' => $expense->status->value, 'status_label' => $expense->status->label(), 'expense_date' => $expense->expense_date->toDateString(), 'date_label' => $expense->expense_date->translatedFormat('d F Y'), 'payment_method' => $expense->payment_method?->value, 'supplier' => $expense->supplier, 'notes' => $expense->notes, 'service_id' => $expense->service_id, 'service_label' => $expense->service?->name ?? '—', 'billing_period_id' => $expense->billing_period_id, 'update_url' => route('project-expenses.update', [$project, $expense]), 'delete_url' => route('project-expenses.destroy', [$project, $expense])], 'kpis' => $this->kpis($project)];
    }

    private function kpis(Project $project): array
    {
        $project->refresh();

        return ['total' => Money::format($project->total_expenses), 'paid' => Money::format($project->paid_expenses), 'pending' => Money::format($project->pending_expenses), 'profit' => Money::format($project->estimated_profit), 'margin' => $project->profit_margin_percentage === null ? '—' : number_format($project->profit_margin_percentage, 1, ',', ' ').' %', 'net_cash' => Money::format($project->net_cash)];
    }
}
