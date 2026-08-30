<?php

namespace Database\Seeders;

use App\Enums\BillingType;
use App\Enums\CalendarEventColor;
use App\Enums\PaymentMethod;
use App\Enums\ProjectExpenseCategory;
use App\Enums\ProjectExpenseStatus;
use App\Enums\ProjectServiceStatus;
use App\Enums\ProjectStatus;
use App\Models\Business;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::pluck('id');
        $team = TeamMember::pluck('id');
        $examples = [
            ['client' => 'Rachid', 'business' => 'Bayt Al Musk', 'project' => 'Suivi marketing', 'amount' => '8000.00', 'status' => ProjectStatus::Suivi, 'payment' => '8000.00', 'due' => 10],
            ['client' => 'Salma', 'business' => 'Compass Coffee', 'project' => 'Lancement digital', 'amount' => '6000.00', 'status' => ProjectStatus::Launch, 'payment' => '3000.00', 'due' => -8],
            ['client' => 'Amine', 'business' => 'ProdVice', 'project' => 'Acquisition mensuelle', 'amount' => '6000.00', 'status' => ProjectStatus::Suivi, 'payment' => '6000.00', 'due' => 5],
            ['client' => 'Nora', 'business' => 'HARD', 'project' => 'Identité de marque', 'amount' => '12000.00', 'status' => ProjectStatus::Waiting, 'payment' => null, 'due' => 6],
            ['client' => 'Mehdi', 'business' => 'Bazare', 'project' => 'Production de contenu', 'amount' => '9500.00', 'status' => ProjectStatus::Paused, 'payment' => null, 'due' => -3],
        ];

        foreach ($examples as $index => $example) {
            $client = Client::create(['name' => $example['client'], 'email' => strtolower($example['client']).'@demo.test', 'phone' => '+212 6 00 00 00 '.($index + 10)]);
            $business = Business::create(['client_id' => $client->id, 'name' => $example['business'], 'website' => 'https://'.str($example['business'])->slug().'.test']);
            $project = Project::create(['business_id' => $business->id, 'name' => $example['project'], 'status' => $example['status'], 'billing_type' => BillingType::Monthly, 'amount' => $example['amount'], 'currency' => 'MAD', 'start_date' => today()->subMonths(2), 'next_payment_date' => today()->addDays($example['due'])]);
            if ($example['business'] === 'Compass Coffee') {
                $tracked = Service::whereIn('name', ['Site web', 'Script', 'Scénario', 'Modèles', 'Production vidéo'])->get();
                foreach ($tracked as $position => $service) {
                    $project->projectServices()->create([
                        'service_id' => $service->id,
                        'status' => $position < 3 ? ProjectServiceStatus::Completed : ProjectServiceStatus::Pending,
                        'completed_at' => $position < 3 ? now()->subDays(2) : null,
                        'notes' => $service->name === 'Site web' ? 'Le site est prêt, reste uniquement la vérification mobile finale.' : null,
                    ]);
                }
            } else {
                foreach ($services->shuffle()->take(2) as $serviceId) {
                    $project->projectServices()->create(['service_id' => $serviceId]);
                }
            }
            $project->teamMembers()->sync($team->shuffle()->take(2)->mapWithKeys(fn ($id) => [$id => ['role' => TeamMember::find($id)?->default_role]])->all());
            $period = $project->billingPeriods()->create(['period_start' => today()->startOfMonth(), 'period_end' => today()->endOfMonth(), 'amount' => $example['amount'], 'due_date' => today()->addDays($example['due']), 'description' => ucfirst(today()->translatedFormat('F Y'))]);
            if ($example['payment']) {
                Payment::create(['project_id' => $project->id, 'billing_period_id' => $period->id, 'amount' => $example['payment'], 'payment_date' => today()->subDays(2), 'method' => PaymentMethod::BankTransfer, 'reference' => 'DEMO-'.($index + 1)]);
            }
            if ($example['business'] === 'Compass Coffee') {
                foreach ([['Modèle', '600.00', ProjectExpenseCategory::Model, ProjectExpenseStatus::Paid], ['Airbnb', '800.00', ProjectExpenseCategory::Location, ProjectExpenseStatus::Paid], ['Transport', '200.00', ProjectExpenseCategory::Transport, ProjectExpenseStatus::Pending]] as [$label, $amount, $category, $status]) {
                    $project->expenses()->create(['billing_period_id' => $period->id, 'label' => $label, 'amount' => $amount, 'category' => $category, 'status' => $status, 'expense_date' => today(), 'created_by' => User::query()->value('id')]);
                }
            }
            $project->activityLogs()->create(['type' => 'project_created', 'description' => 'Projet créé.', 'occurred_at' => now()->subDays(12 - $index)]);
        }

        $creatorId = User::query()->value('id');
        foreach ([
            ['Tournage Bayt Al Musk', 0, CalendarEventColor::Green],
            ['Réunion Compass Coffee', 2, CalendarEventColor::Blue],
            ['Préparation script HARD', 5, CalendarEventColor::Purple],
            ['Livraison ProdVice', 8, CalendarEventColor::Orange],
        ] as [$title, $dayOffset, $color]) {
            CalendarEvent::create(['title' => $title, 'event_date' => today()->addDays($dayOffset), 'color' => $color, 'created_by' => $creatorId]);
        }
    }
}
