<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + ['label' => 'Airbnb', 'amount' => '800.00', 'expense_date' => today()->toDateString(), 'status' => 'paid'];
    }

    public function test_an_authenticated_user_can_create_update_and_delete_an_expense(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['amount' => 8000]);
        $this->postJson(route('project-expenses.store', $project), $this->payload())->assertCreated();
        $expense = ProjectExpense::first();
        $this->patchJson(route('project-expenses.update', [$project, $expense]), $this->payload(['label' => 'Airbnb Rabat', 'status' => 'pending']))->assertOk();
        $this->assertDatabaseHas('project_expenses', ['label' => 'Airbnb Rabat', 'status' => 'pending']);
        $this->deleteJson(route('project-expenses.destroy', [$project, $expense]))->assertOk();
        $this->assertDatabaseCount('project_expenses', 0);
    }

    public function test_project_expense_totals_profit_margin_and_net_cash_are_correct(): void
    {
        $project = Project::factory()->create(['amount' => 8000]);
        foreach ([[600, 'paid'], [800, 'paid'], [200, 'pending']] as [$amount, $status]) {
            $project->expenses()->create($this->payload(['amount' => $amount, 'status' => $status]));
        }
        $this->assertSame('1600.00', $project->total_expenses);
        $this->assertSame('1400.00', $project->paid_expenses);
        $this->assertSame('200.00', $project->pending_expenses);
        $this->assertSame('6400.00', $project->estimated_profit);
        $this->assertSame(80.0, $project->profit_margin_percentage);
    }

    public function test_zero_and_negative_amounts_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        foreach ([0, -1] as $amount) {
            $this->postJson(route('project-expenses.store', $project), $this->payload(['amount' => $amount]))->assertJsonValidationErrors('amount');
        }
    }

    public function test_period_and_service_must_belong_to_the_project(): void
    {
        $this->actingAs(User::factory()->create());
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $period = $a->billingPeriods()->create(['period_start' => today(), 'period_end' => today(), 'amount' => 100, 'due_date' => today()]);
        $service = Service::create(['name' => 'Externe', 'slug' => 'externe']);
        $a->projectServices()->create(['service_id' => $service->id]);
        $this->postJson(route('project-expenses.store', $b), $this->payload(['billing_period_id' => $period->id, 'service_id' => $service->id]))->assertJsonValidationErrors(['billing_period_id', 'service_id']);
    }

    public function test_an_expense_cannot_be_modified_through_another_project(): void
    {
        $this->actingAs(User::factory()->create());
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $expense = $a->expenses()->create($this->payload());
        $this->patchJson(route('project-expenses.update', [$b, $expense]), $this->payload())->assertNotFound();
    }

    public function test_a_guest_cannot_manage_expenses(): void
    {
        $project = Project::factory()->create();
        $this->post(route('project-expenses.store', $project), $this->payload())->assertRedirect(route('login'));
    }
}
