<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'  => Patient::inRandomOrder()->first()?->id ?? 1,
            'doctor_id'   => Doctor::inRandomOrder()->first()?->id ?? 1,
            'order_date'  => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status'      => $this->faker->randomElement(['pending', 'processing', 'completed', 'completed']),
            'notes'       => $this->faker->optional()->sentence(),
        ];
    }
}