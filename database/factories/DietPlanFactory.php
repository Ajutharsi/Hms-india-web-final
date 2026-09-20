<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class DietPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'   => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'diet_type'    => $this->faker->randomElement(['normal', 'diabetic', 'liquid', 'soft', 'low_salt', 'npo', 'other']),
            'restrictions' => $this->faker->optional()->sentence(),
            'notes'        => $this->faker->optional()->sentence(),
            'status'       => 'active',
        ];
    }
}
