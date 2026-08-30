<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\CalendarEventColor;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Models\CalendarEvent;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCockpitTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_financial_kpis_exclude_next_month(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['name' => 'Bayt mensuel', 'status' => ProjectStatus::Suivi, 'billing_type' => BillingType::Monthly, 'amount' => 8000]);
        Payment::factory()->create(['project_id' => $project->id, 'amount' => 8000, 'payment_date' => today()]);
        Payment::factory()->create(['project_id' => $project->id, 'amount' => 9000, 'payment_date' => today()->addMonth()]);
        $this->expense($project, 1500, today());
        $this->expense($project, 2500, today()->addMonth());

        $this->get(route('dashboard'))->assertOk()->assertSee('8 000 DH')->assertSee('1 500 DH')->assertSee('6 500 DH')->assertSee('81,3 %')->assertDontSee('9 000 DH');
    }

    public function test_dashboard_shows_overdue_pending_expense_and_upcoming_event(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['status' => ProjectStatus::Launch, 'billing_type' => BillingType::Monthly]);
        $project->billingPeriods()->create(['period_start' => today()->startOfMonth(), 'period_end' => today()->endOfMonth(), 'amount' => 3000, 'due_date' => today()->subDays(3)]);
        $this->expense($project, 800, today(), 'pending', 'Airbnb tournage');
        CalendarEvent::create(['title' => 'Réunion importante', 'event_date' => today()->addDay(), 'color' => CalendarEventColor::Green]);

        $this->get(route('dashboard'))->assertOk()->assertSee('Paiement en retard')->assertSee('Airbnb tournage')->assertSee('Réunion importante');
    }

    public function test_project_health_detects_critical_and_excellent_projects(): void
    {
        $service = app(DashboardService::class);
        $critical = Project::factory()->create(['status' => ProjectStatus::Launch]);
        $excellent = Project::factory()->create(['status' => ProjectStatus::Suivi]);
        $excellent->setAttribute('active_project_services_count', 2);
        $excellent->setAttribute('completed_services_count', 2);

        $this->assertSame(ProjectHealth::Critical, $service->health($critical, 10, false, 0));
        $this->assertSame(ProjectHealth::Excellent, $service->health($excellent, 80, false, 0));
    }

    private function expense(Project $project, int $amount, mixed $date, string $status = 'paid', string $label = 'Charge'): void
    {
        $project->expenses()->create(['label' => $label, 'amount' => $amount, 'expense_date' => $date, 'status' => $status]);
    }
}
