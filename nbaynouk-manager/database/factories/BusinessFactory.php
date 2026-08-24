<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->company(),
            'website' => fake()->optional()->url(),
            'instagram' => fake()->optional()->userName(),
        ];
    }
}
