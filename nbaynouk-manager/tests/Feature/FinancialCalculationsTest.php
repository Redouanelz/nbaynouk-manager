<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\PaymentStatus;
use App\Models\BillingPeriod;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCalculationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fully_paid_project_has_no_remaining_amount(): void
    {
        $project = Project::factory()->create(['amount' => 8000, 'billing_type' => BillingType::OneTime]);
        Payment::factory()->create(['project_id' => $project->id, 'amount' => 8000]);

        $this->assertSame('8000.00', $project->total_paid);
        $this->assertSame('0.00', $project->remaining_amount);
    }

    public function test_a_partially_paid_future_period_is_partial(): void
    {
        $period = $this->periodWithPayment(now()->addWeek()->toDateString(), 3000);

        $this->assertSame('3000.00', $period->total_paid);
        $this->assertSame('5000.00', $period->remaining_amount);
        $this->assertSame(PaymentStatus::Partial, $period->payment_status);
    }

    public function test_an_unpaid_future_period_is_unpaid(): void
    {
        $period = BillingPeriod::factory()->create([
            'amount' => 8000,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $this->assertSame(PaymentStatus::Unpaid, $period->payment_status);
    }

    public function test_a_period_due_today_is_not_overdue(): void
    {
        $period = BillingPeriod::factory()->create([
            'amount' => 8000,
            'due_date' => today(),
        ]);

        $this->assertSame(PaymentStatus::Unpaid, $period->payment_status);
    }

    public function test_a_partially_paid_past_due_period_is_overdue(): void
    {
        $period = $this->periodWithPayment(now()->subDay()->toDateString(), 3000);

        $this->assertSame(PaymentStatus::Overdue, $period->payment_status);
    }

    public function test_cumulative_payments_can_fully_pay_a_period(): void
    {
        $period = BillingPeriod::factory()->create(['amount' => 8000]);

        foreach ([3000, 2000, 3000] as $amount) {
            Payment::factory()->create([
                'project_id' => $period->project_id,
                'billing_period_id' => $period->id,
                'amount' => $amount,
            ]);
        }

        $this->assertSame('8000.00', $period->total_paid);
        $this->assertSame('0.00', $period->remaining_amount);
        $this->assertSame(PaymentStatus::Paid, $period->payment_status);
    }

    public function test_project_codes_are_monotonic_after_a_soft_delete(): void
    {
        $first = Project::factory()->create();
        $first->delete();
        $second = Project::factory()->create();

        $this->assertSame('PRJ-0001', $first->code);
        $this->assertSame('PRJ-0002', $second->code);
    }

    private function periodWithPayment(string $dueDate, int $amount): BillingPeriod
    {
        $period = BillingPeriod::factory()->create(['amount' => 8000, 'due_date' => $dueDate]);
        Payment::factory()->create([
            'project_id' => $period->project_id,
            'billing_period_id' => $period->id,
            'amount' => $amount,
        ]);

        return $period;
    }
}
