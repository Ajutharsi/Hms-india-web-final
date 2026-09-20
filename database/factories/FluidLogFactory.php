<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FluidLogFactory extends Factory
{
    public function definition(): array
    {
        $flowType = $this->faker->randomElement(['intake', 'output']);
        $category = $flowType === 'intake'
            ? $this->faker->randomElement(['oral', 'iv', 'tube_feed'])
            : $this->faker->randomElement(['urine', 'vomit', 'drain', 'stool', 'other']);

        return [
            'patient_id' => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'logged_by'  => User::inRandomOrder()->first()?->id ?? 1,
            'log_date'   => $this->faker->dateTimeBetween('-7 days', 'now')->format('Y-m-d'),
            'log_time'   => $this->faker->time('H:i'),
            'flow_type'  => $flowType,
            'category'   => $category,
            'amount_ml'  => $this->faker->numberBetween(50, 500),
            'notes'      => $this->faker->optional()->sentence(),
        ];
    }
}
