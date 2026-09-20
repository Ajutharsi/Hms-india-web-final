<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'       => Patient::inRandomOrder()->first()?->id ?? 1,
            'doctor_id'        => Doctor::inRandomOrder()->first()?->id ?? 1,
            'appointment_date' => $this->faker->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'appointment_time' => $this->faker->randomElement(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30']),
            'token_number'     => $this->faker->numberBetween(1, 50),
            'type'             => $this->faker->randomElement(['op', 'op', 'op', 'emergency']),
            'status'           => $this->faker->randomElement(['scheduled', 'completed', 'completed', 'cancelled']),
            'notes'            => $this->faker->optional()->sentence(),
        ];
    }
}