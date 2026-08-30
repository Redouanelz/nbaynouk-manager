<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ProjectExpenseCategory;
use App\Enums\ProjectExpenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    use HasFactory;

    protected $fillable = ['billing_period_id', 'service_id', 'label', 'category', 'amount', 'expense_date', 'status', 'payment_method', 'supplier', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'expense_date' => 'date', 'status' => ProjectExpenseStatus::class, 'category' => ProjectExpenseCategory::class, 'payment_method' => PaymentMethod::class];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
