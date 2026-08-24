<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'billing_period_id', 'amount', 'payment_date', 'method', 'reference', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            if ($payment->billing_period_id === null) {
                return;
            }

            $periodProjectId = BillingPeriod::query()
                ->whereKey($payment->billing_period_id)
                ->value('project_id');

            if ((int) $periodProjectId !== (int) $payment->project_id) {
                throw ValidationException::withMessages([
                    'billing_period_id' => 'La période de facturation doit appartenir au même projet.',
                ]);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }
}
