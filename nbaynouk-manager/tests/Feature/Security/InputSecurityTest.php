<?php

namespace Tests\Feature\Security;

use App\Enums\BillingType;
use App\Enums\ProjectStatus;
use App\Models\Business;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_treats_sql_payload_as_literal_and_limits_input_length(): void
    {
        $this->actingAs(User::factory()->create());
        Client::factory()->create(['name' => 'Client visible']);

        $this->getJson('/search?q='.urlencode("' OR 1=1 --"))->assertOk()->assertJsonCount(0, 'clients');
        $this->getJson('/search?q='.str_repeat('a', 256))->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->get('/projects?status='.urlencode("' OR 1=1 --"))->assertSessionHasErrors('status');
    }

    public function test_user_content_is_html_escaped_in_project_page(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = '<img src=x onerror=alert(1)>';
        $project = Project::factory()->create(['name' => $payload, 'notes' => '<script>alert(1)</script>']);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_overposting_does_not_modify_unvalidated_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $business = Business::factory()->create();
        $payload = [
            'business_id' => $business->id, 'name' => 'Projet sûr', 'status' => ProjectStatus::Waiting->value,
            'billing_type' => BillingType::Monthly->value, 'amount' => '1000.00', 'currency' => 'MAD',
            'code' => 'INJECTED', 'user_id' => 999, 'is_admin' => true, 'completed_at' => now(),
        ];

        $this->post(route('projects.store'), $payload)->assertRedirect();
        $project = Project::firstOrFail();
        $this->assertNotSame('INJECTED', $project->code);
        $this->assertArrayNotHasKey('is_admin', $project->getAttributes());
    }

    public function test_dangerous_website_scheme_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        $this->post(route('businesses.store'), ['client_id' => $client->id, 'name' => 'Test', 'website' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('website');
    }
}
