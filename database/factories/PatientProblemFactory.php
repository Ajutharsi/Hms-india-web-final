<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientProblemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'   => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'problem_name' => $this->faker->randomElement(['Hypertension', 'Type 2 Diabetes', 'Asthma', 'Chronic Back Pain', 'Migraine', 'Arthritis', 'GERD']),
            'onset_date'   => $this->faker->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'status'       => $this->faker->randomElement(['active', 'resolved', 'chronic']),
            'notes'        => $this->faker->optional()->sentence(),
        ];
    }
}
