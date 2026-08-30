<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_authenticated_user_can_open_expenses(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('expenses.index'))->assertOk()->assertSee('Suivez les coûts et la rentabilité de chaque projet.');
    }

    public function test_overview_displays_correct_expenses_profit_and_margin(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['name' => 'Bayt Al Musk', 'amount' => 15000, 'billing_type' => BillingType::OneTime]);
        foreach ([600, 800, 1100] as $amount) {
            $this->expense($project, $amount);
        }

        $this->get(route('expenses.index'))->assertOk()->assertSee('Bayt Al Musk')->assertSee('2 500 DH')->assertSee('12 500 DH')->assertSee('83,3 %');
    }

    public function test_project_without_expenses_and_zero_amount_are_safe(): void
    {
        $this->actingAs(User::factory()->create());
        Project::factory()->create(['name' => 'Sans charge', 'amount' => 8000, 'billing_type' => BillingType::OneTime]);
        Project::factory()->create(['name' => 'Sans valeur', 'amount' => 0, 'billing_type' => BillingType::OneTime]);

        $this->get(route('expenses.index'))->assertOk()->assertSee('8 000 DH')->assertSee('100,0 %')->assertSee('Sans valeur');
    }

    public function test_monthly_project_only_uses_current_month_expenses(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['name' => 'Suivi mensuel', 'amount' => 8000, 'billing_type' => BillingType::Monthly]);
        $current = $project->billingPeriods()->create(['period_start' => today()->startOfMonth(), 'period_end' => today()->endOfMonth(), 'amount' => 8000, 'due_date' => today()]);
        $next = $project->billingPeriods()->create(['period_start' => today()->addMonth()->startOfMonth(), 'period_end' => today()->addMonth()->endOfMonth(), 'amount' => 8000, 'due_date' => today()->addMonth()]);
        $this->expense($project, 1500, $current->id);
        $this->expense($project, 2500, $next->id, today()->addMonth());

        $this->get(route('expenses.index'))->assertOk()->assertSee('1 500 DH')->assertSee('6 500 DH')->assertSee('81,3 %')->assertDontSee('2 500 DH');
    }

    public function test_search_status_sort_validation_and_pagination_work(): void
    {
        $this->actingAs(User::factory()->create());
        $target = Project::factory()->create(['name' => 'Compass Unique', 'status' => ProjectStatus::Launch]);
        Project::factory()->count(15)->create(['status' => ProjectStatus::Waiting]);

        $this->get(route('expenses.index', ['search' => 'Compass', 'status' => 'launch']))->assertOk()->assertSee($target->name);
        $this->get(route('expenses.index', ['sort' => 'DROP TABLE projects']))->assertSessionHasErrors('sort');
        $this->get(route('expenses.index'))->assertOk()->assertSee('page=2', false);
    }

    private function expense(Project $project, int $amount, ?int $periodId = null, mixed $date = null): void
    {
        $project->expenses()->create(['label' => 'Charge '.$amount, 'amount' => $amount, 'expense_date' => $date ?? today(), 'status' => 'paid', 'billing_period_id' => $periodId]);
    }
}
