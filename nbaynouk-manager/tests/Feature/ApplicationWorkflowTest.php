<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Models\Business;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_routes_are_protected_and_authenticated_user_can_open_dashboard(): void
    {
        $this->get('/projects')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Vue d’ensemble');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_project_can_be_created_updated_and_archived(): void
    {
        $this->actingAs(User::factory()->create());
        $business = Business::factory()->create();
        $projectData = ['business_id' => $business->id, 'name' => 'Campagne rentrée', 'status' => ProjectStatus::Waiting->value, 'billing_type' => BillingType::Monthly->value, 'amount' => '8000.00', 'currency' => 'MAD'];
        $this->post('/projects', $projectData)->assertRedirect();
        $project = Project::firstOrFail();
        $this->assertSame('PRJ-0001', $project->code);
        $this->put("/projects/{$project->id}", array_merge($projectData, ['name' => 'Campagne rentrée 2026', 'status' => ProjectStatus::Launch->value]))->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Campagne rentrée 2026', 'status' => 'launch']);
        $this->delete("/projects/{$project->id}")->assertRedirect('/projects');
        $this->assertSoftDeleted($project);
    }

    public function test_client_can_be_created_and_updated(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/clients', ['name' => 'Rachid', 'email' => 'rachid@example.test'])->assertRedirect();
        $client = Client::firstOrFail();
        $this->put("/clients/{$client->id}", ['name' => 'Rachid B.', 'email' => 'rachid@example.test'])->assertRedirect();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Rachid B.']);
    }

    public function test_payment_creation_updates_totals_and_deletion_is_logged(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['amount' => '8000.00']);
        $payload = ['project_id' => $project->id, 'amount' => '3000.00', 'payment_date' => today()->format('Y-m-d'), 'method' => PaymentMethod::BankTransfer->value];
        $this->post('/payments', $payload)->assertRedirect();
        $this->assertSame('3000.00', $project->fresh()->total_paid);
        $payment = Payment::firstOrFail();
        $this->delete("/payments/{$payment->id}")->assertRedirect();
        $this->assertSame('0.00', $project->fresh()->total_paid);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'type' => 'payment_deleted']);
    }

    public function test_dashboard_uses_real_monthly_aggregates(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create(['status' => ProjectStatus::Suivi, 'billing_type' => BillingType::Monthly, 'amount' => '8000.00']);
        Payment::factory()->create(['project_id' => $project->id, 'amount' => '3000.00', 'payment_date' => today()]);
        $this->get('/dashboard')->assertOk()->assertSee('8 000 DH')->assertSee('3 000 DH');
    }

    public function test_authenticated_user_can_open_all_primary_pages(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/dashboard', '/projects', '/clients', '/payments', '/billing', '/team', '/services', '/settings'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_primary_pages_render_with_related_business_data(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $period = $project->billingPeriods()->create([
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'amount' => '5000.00',
            'due_date' => today()->addDays(5),
        ]);
        Payment::factory()->create(['project_id' => $project->id, 'billing_period_id' => $period->id]);

        $this->get('/clients')->assertOk()->assertSee($project->business->client->name);
        $this->get("/clients/{$project->business->client_id}")->assertOk()->assertSee($project->name);
        $this->get("/projects/{$project->id}")->assertOk()->assertSee($project->code);
        $this->get('/payments')->assertOk()->assertSee($project->name);
        $this->get('/billing')->assertOk()->assertSee($project->code);
    }
}
