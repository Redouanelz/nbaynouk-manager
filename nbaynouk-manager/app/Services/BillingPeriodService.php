<?php

namespace App\Services;

use App\Models\BillingPeriod;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingPeriodService
{
    public function create(Project $project, array $data): BillingPeriod
    {
        return DB::transaction(function () use ($project, $data): BillingPeriod {
            $exists = $project->billingPeriods()
                ->whereDate('period_start', $data['period_start'])
                ->whereDate('period_end', $data['period_end'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['period_start' => 'Cette période existe déjà.']);
            }

            $period = $project->billingPeriods()->create($data);
            app(ActivityLogService::class)->record($project, 'billing_period_created', 'Une nouvelle période de facturation a été créée.');

            return $period;
        });
    }

    public function createNext(Project $project): BillingPeriod
    {
        $last = $project->billingPeriods()->orderByDesc('period_end')->first();
        $start = $last?->period_end?->copy()->addDay()->startOfMonth() ?? today()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $dueDay = $last?->due_date?->day ?? 10;
        $dueDate = $start->copy()->day(min($dueDay, $start->daysInMonth));

        return $this->create($project, [
            'period_start' => $start,
            'period_end' => $end,
            'amount' => $project->amount,
            'due_date' => $dueDate,
            'description' => ucfirst($start->translatedFormat('F Y')),
        ]);
    }
}
