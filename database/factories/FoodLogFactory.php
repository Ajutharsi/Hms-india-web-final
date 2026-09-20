<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FoodLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'logged_by'  => User::inRandomOrder()->first()?->id ?? 1,
            'log_date'   => $this->faker->dateTimeBetween('-7 days', 'now')->format('Y-m-d'),
            'meal_type'  => $this->faker->randomElement(['breakfast', 'lunch', 'snacks', 'dinner']),
            'status'     => $this->faker->randomElement(['given', 'given', 'given', 'refused', 'partial', 'pending']),
            'quantity'   => $this->faker->randomElement(['full', 'half', 'none']),
            'notes'      => $this->faker->optional()->sentence(),
        ];
    }
}
