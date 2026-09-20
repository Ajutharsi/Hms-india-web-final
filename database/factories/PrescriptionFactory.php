<?php

namespace Database\Factories;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        $consultation = Consultation::inRandomOrder()->first();

        return [
            'consultation_id'   => $consultation?->id ?? Consultation::factory(),
            'patient_id'        => $consultation?->patient_id ?? \App\Models\Patient::inRandomOrder()->first()?->id ?? 1,
            'doctor_id'         => $consultation?->doctor_id ?? \App\Models\Doctor::inRandomOrder()->first()?->id ?? 1,
            'prescription_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'notes'             => $this->faker->optional()->sentence(),
            'status'            => $this->faker->randomElement(['active', 'active', 'completed']),
        ];
    }
}
