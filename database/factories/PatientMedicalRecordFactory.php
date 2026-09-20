<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientMedicalRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'  => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'record_type' => $this->faker->randomElement(['Diagnosis', 'Lab Result', 'Surgery', 'Vaccination', 'Note']),
            'record_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'description' => $this->faker->sentence(),
            'doctor_name' => 'Dr. ' . $this->faker->lastName(),
            'notes'       => $this->faker->optional()->sentence(),
        ];
    }
}
