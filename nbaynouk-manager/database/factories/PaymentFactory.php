<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'billing_period_id' => null,
            'amount' => fake()->randomFloat(2, 100, 30000),
            'payment_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference' => fake()->optional()->bothify('PAY-####-????'),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
