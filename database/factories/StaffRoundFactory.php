<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffRoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'     => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'staff_id'       => User::role(['nurse', 'staff'])->inRandomOrder()->first()?->id ?? User::inRandomOrder()->first()?->id ?? 1,
            'round_time'     => $this->faker->dateTimeBetween('-7 days', 'now'),
            'status'         => 'completed',
            'blood_pressure' => $this->faker->numberBetween(100, 140) . '/' . $this->faker->numberBetween(60, 90),
            'temperature'    => $this->faker->randomFloat(1, 36.1, 38.5) . ' C',
            'pulse_rate'     => $this->faker->numberBetween(60, 100) . ' bpm',
            'spo2'           => $this->faker->numberBetween(94, 100) . '%',
            'notes'          => $this->faker->optional()->sentence(),
        ];
    }
}
