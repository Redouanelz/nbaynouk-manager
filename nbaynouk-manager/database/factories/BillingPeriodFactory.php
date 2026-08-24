<?php

namespace Database\Factories;

use App\Models\BillingPeriod;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingPeriodFactory extends Factory
{
    protected $model = BillingPeriod::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'project_id' => Project::factory(),
            'period_start' => $start,
            'period_end' => (clone $start)->modify('+1 month -1 day'),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'due_date' => (clone $start)->modify('+10 days'),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
