<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WardFactory extends Factory
{
    public function definition(): array
    {
        $wardNames = [
            'General Ward A', 'General Ward B', 'Private Ward 1', 'Private Ward 2',
            'ICU Ward', 'NICU Ward', 'Emergency Ward', 'Semi Private Ward',
            'Maternity Ward', 'Pediatric Ward', 'Surgical Ward', 'Cardiac Ward',
        ];

        static $index = 0;
        $name = $wardNames[$index % count($wardNames)] . ($index >= count($wardNames) ? ' ' . ($index + 1) : '');
        $index++;

        $totalBeds = $this->faker->numberBetween(5, 20);

        return [
            'name'           => $name,
            'type'           => $this->faker->randomElement(['general', 'private', 'semi_private', 'icu', 'nicu', 'emergency']),
            'floor'          => 'Floor ' . $this->faker->numberBetween(1, 5),
            'building'       => 'Block ' . $this->faker->randomElement(['A', 'B', 'C']),
            'total_beds'     => $totalBeds,
            'available_beds' => $totalBeds,
            'charge_per_day' => $this->faker->randomElement([1000, 1500, 2000, 2500, 3000, 5000]),
            'status'         => 'active',
            'description'    => $this->faker->sentence(),
        ];
    }
}