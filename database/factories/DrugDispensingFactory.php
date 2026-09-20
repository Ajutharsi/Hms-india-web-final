<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrugDispensingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'    => Patient::inRandomOrder()->first()?->id ?? 1,
            'dispensed_by'  => \App\Models\User::inRandomOrder()->first()?->id ?? 1,
            'dispensed_date'=> $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status'        => $this->faker->randomElement(['pending', 'dispensed', 'dispensed', 'partial']),
            'notes'         => $this->faker->optional()->sentence(),
            'total_amount'  => $this->faker->randomFloat(2, 100, 3000),
        ];
    }
}