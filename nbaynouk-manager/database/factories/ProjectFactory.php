<?php

namespace Database\Factories;

use App\Enums\BillingType;
use App\Enums\ProjectStatus;
use App\Models\Business;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = fake()->optional()->dateTimeBetween('-6 months', 'now');

        return [
            'business_id' => Business::factory(),
            'name' => fake()->sentence(3),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'billing_type' => fake()->randomElement(BillingType::cases()),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'MAD',
            'start_date' => $startDate,
            'end_date' => $startDate ? fake()->optional()->dateTimeBetween($startDate, '+1 year') : null,
            'next_payment_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
