<?php

namespace App\Models;

use App\Enums\PaymentStatus;
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

    protected $appends = ['total_paid', 'remaining_amount', 'payment_status'];

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

    public function getTotalPaidAttribute(): string
    {
        return number_format((float) $this->payments()->sum('amount'), 2, '.', '');
    }

    public function getRemainingAmountAttribute(): string
    {
        return number_format(max(0, (float) $this->amount - (float) $this->total_paid), 2, '.', '');
    }

    public function getPaymentStatusAttribute(): PaymentStatus
    {
        if ((float) $this->total_paid >= (float) $this->amount) {
            return PaymentStatus::Paid;
        }

        if ((float) $this->remaining_amount > 0 && $this->due_date?->isBefore(today())) {
            return PaymentStatus::Overdue;
        }

        return (float) $this->total_paid > 0
            ? PaymentStatus::Partial
            : PaymentStatus::Unpaid;
    }
}
