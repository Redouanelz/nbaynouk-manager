<?php

namespace App\Models;

use App\Enums\BillingType;
use App\Enums\ProjectServiceStatus;
use App\Enums\ProjectStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'status', 'billing_type', 'amount', 'currency',
        'start_date', 'end_date', 'next_payment_date', 'notes',
    ];

    protected $appends = ['total_paid', 'remaining_amount', 'total_expenses', 'paid_expenses', 'pending_expenses', 'estimated_profit', 'profit_margin_percentage', 'net_cash', 'progress_percentage', 'completed_services_count', 'total_services_count'];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'billing_type' => BillingType::class,
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->code)) {
                $project->code = DB::transaction(function (): string {
                    DB::table('project_code_sequences')->insertOrIgnore([
                        'id' => 1,
                        'next_number' => 1,
                    ]);

                    $sequence = DB::table('project_code_sequences')
                        ->where('id', 1)
                        ->lockForUpdate()
                        ->first();

                    $number = (int) $sequence->next_number;
                    DB::table('project_code_sequences')->where('id', 1)->update([
                        'next_number' => $number + 1,
                    ]);

                    return sprintf('PRJ-%04d', $number);
                });
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->using(ProjectService::class)
            ->withPivot(['id', 'status', 'notes', 'completed_at', 'is_active'])->withTimestamps();
    }

    public function projectServices(): HasMany
    {
        return $this->hasMany(ProjectService::class);
    }

    public function activeProjectServices(): HasMany
    {
        return $this->projectServices()->where('is_active', true);
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function billingPeriods(): HasMany
    {
        return $this->hasMany(BillingPeriod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function getTotalExpensesAttribute(): string
    {
        return $this->expenseSum();
    }

    public function getPaidExpensesAttribute(): string
    {
        return $this->expenseSum('paid');
    }

    public function getPendingExpensesAttribute(): string
    {
        return $this->expenseSum('pending');
    }

    public function getEstimatedProfitAttribute(): string
    {
        return Money::subtract($this->amount, $this->total_expenses);
    }

    public function getProfitMarginPercentageAttribute(): ?float
    {
        return bccomp((string) $this->amount, '0', 2) === 1 ? round(((float) $this->estimated_profit / (float) $this->amount) * 100, 1) : null;
    }

    public function getNetCashAttribute(): string
    {
        return Money::subtract($this->total_paid, $this->paid_expenses);
    }

    public function profitabilityLevel(?float $margin = null): ?string
    {
        $margin ??= isset($this->attributes['overview_margin']) ? (float) $this->attributes['overview_margin'] : $this->profit_margin_percentage;
        if ($margin === null) {
            return null;
        }

        return $margin >= 70 ? 'high' : ($margin >= 40 ? 'medium' : 'low');
    }

    private function expenseSum(?string $status = null): string
    {
        $attribute = $status ? $status.'_expenses_sum_amount' : 'expenses_sum_amount';
        if (array_key_exists($attribute, $this->attributes)) {
            return bcadd((string) ($this->attributes[$attribute] ?? 0), '0', 2);
        }
        $query = $this->expenses();
        if ($status) {
            $query->where('status', $status);
        }

        return bcadd((string) $query->sum('amount'), '0', 2);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getTotalPaidAttribute(): string
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->reduce(fn (string $total, Payment $payment) => bcadd($total, $payment->amount, 2), '0.00');
        }

        return bcadd((string) $this->payments()->sum('amount'), '0', 2);
    }

    public function getRemainingAmountAttribute(): string
    {
        return Money::subtract($this->amount, $this->total_paid);
    }

    public function getTotalServicesCountAttribute(): int
    {
        return (int) ($this->attributes['active_project_services_count'] ?? ($this->relationLoaded('activeProjectServices') ? $this->activeProjectServices->count() : $this->activeProjectServices()->count()));
    }

    public function getCompletedServicesCountAttribute(): int
    {
        return (int) ($this->attributes['completed_services_count'] ?? ($this->relationLoaded('activeProjectServices') ? $this->activeProjectServices->where('status', ProjectServiceStatus::Completed)->count() : $this->activeProjectServices()->where('status', ProjectServiceStatus::Completed->value)->count()));
    }

    public function getProgressPercentageAttribute(): int
    {
        return $this->total_services_count === 0 ? 0 : (int) round(($this->completed_services_count / $this->total_services_count) * 100);
    }

    public function formattedAmount(string $attribute = 'amount'): string
    {
        return Money::format($this->{$attribute}, $this->currency);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ProjectStatus::Onboarding->value,
            ProjectStatus::Launch->value,
            ProjectStatus::Suivi->value,
        ]);
    }

    public function scopeStatus(Builder $query, ProjectStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof ProjectStatus ? $status->value : $status);
    }

    public function scopeBillingType(Builder $query, BillingType|string $type): Builder
    {
        return $query->where('billing_type', $type instanceof BillingType ? $type->value : $type);
    }
}
