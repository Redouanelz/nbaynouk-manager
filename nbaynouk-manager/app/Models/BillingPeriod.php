<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'period_start', 'period_end', 'amount', 'due_date', 'description',
    ];

    protected $appends = ['total_paid', 'remaining_amount', 'payment_status', 'total_expenses', 'estimated_profit', 'profit_margin_percentage'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
        return bcadd((string) $this->expenses()->sum('amount'), '0', 2);
    }

    public function getEstimatedProfitAttribute(): string
    {
        return Money::subtract($this->amount, $this->total_expenses);
    }

    public function getProfitMarginPercentageAttribute(): ?float
    {
        return bccomp((string) $this->amount, '0', 2) === 1 ? round(((float) $this->estimated_profit / (float) $this->amount) * 100, 1) : null;
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

    public function getPaymentStatusAttribute(): PaymentStatus
    {
        if (bccomp($this->total_paid, $this->amount, 2) >= 0) {
            return PaymentStatus::Paid;
        }

        if (bccomp($this->remaining_amount, '0', 2) === 1 && $this->due_date?->isBefore(today())) {
            return PaymentStatus::Overdue;
        }

        return bccomp($this->total_paid, '0', 2) === 1
            ? PaymentStatus::Partial
            : PaymentStatus::Unpaid;
    }
}
