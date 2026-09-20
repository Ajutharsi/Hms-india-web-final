<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientAllergyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'allergen'   => $this->faker->randomElement(['Penicillin', 'Peanuts', 'Dust', 'Latex', 'Sulfa Drugs', 'Aspirin', 'Pollen', 'Shellfish']),
            'type'       => $this->faker->randomElement(['food', 'drug', 'environmental', 'other']),
            'severity'   => $this->faker->randomElement(['mild', 'moderate', 'severe']),
            'reaction'   => $this->faker->optional()->sentence(),
        ];
    }
}
