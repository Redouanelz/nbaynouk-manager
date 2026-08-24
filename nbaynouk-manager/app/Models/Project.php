<?php

namespace App\Models;

use App\Enums\BillingType;
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

    protected $appends = ['total_paid', 'remaining_amount'];

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
        return $this->belongsToMany(Service::class)->withTimestamps();
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
