<?php

namespace Tests\Feature\Security;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_payment_amounts_are_rejected_without_database_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        foreach (['-5000', '0', 'abc', '1 OR 1=1', '10000000000.00'] as $amount) {
            $this->post(route('payments.store'), ['project_id' => $project->id, 'amount' => $amount, 'payment_date' => today()->toDateString()])
                ->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_billing_period_must_belong_to_payment_project(): void
    {
        $this->actingAs(User::factory()->create());
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $period = $projectA->billingPeriods()->create(['period_start' => today(), 'period_end' => today(), 'amount' => 1000, 'due_date' => today()]);

        $this->post(route('payments.store'), ['project_id' => $projectB->id, 'billing_period_id' => $period->id, 'amount' => 100, 'payment_date' => today()->toDateString()])
            ->assertSessionHasErrors('billing_period_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_progress_always_stays_within_zero_and_one_hundred(): void
    {
        $project = Project::factory()->create();
        $this->assertGreaterThanOrEqual(0, $project->progress_percentage);
        $this->assertLessThanOrEqual(100, $project->progress_percentage);
    }
}
