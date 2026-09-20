<?php

namespace Database\Factories;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    public function definition(): array
    {
        $appointment = Appointment::inRandomOrder()->first();

        return [
            'appointment_id'  => $appointment?->id ?? Appointment::factory(),
            'patient_id'      => $appointment?->patient_id ?? \App\Models\Patient::inRandomOrder()->first()?->id ?? 1,
            'doctor_id'       => $appointment?->doctor_id ?? \App\Models\Doctor::inRandomOrder()->first()?->id ?? 1,
            'blood_pressure'  => $this->faker->numberBetween(100, 140) . '/' . $this->faker->numberBetween(60, 90),
            'temperature'     => $this->faker->randomFloat(1, 36.1, 38.5) . ' C',
            'pulse_rate'      => $this->faker->numberBetween(60, 100) . ' bpm',
            'weight'          => $this->faker->numberBetween(45, 95) . ' kg',
            'height'          => $this->faker->numberBetween(150, 190) . ' cm',
            'spo2'            => $this->faker->numberBetween(94, 100) . '%',
            'chief_complaint' => $this->faker->sentence(),
            'diagnosis'       => $this->faker->sentence(),
            'treatment_plan'  => $this->faker->sentence(),
            'notes'           => $this->faker->optional()->sentence(),
        ];
    }
}
