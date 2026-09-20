<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key'              => $this->faker->unique()->slug(2, false),
            'name'             => $this->faker->words(2, true),
            'description'      => $this->faker->sentence(),
            'price'            => $this->faker->randomElement([1000, 2500, 5000, 9000]),
            'billing_cycle'    => $this->faker->randomElement(['monthly', 'yearly']),
            'trial_days'       => $this->faker->randomElement([null, 7, 14, 30]),
            'user_limit'       => $this->faker->randomElement([null, 10, 25, 50]),
            'doctor_limit'     => $this->faker->randomElement([null, 5, 10]),
            'patient_limit'    => $this->faker->randomElement([null, 100, 500]),
            'storage_limit_mb' => $this->faker->randomElement([null, 1000, 5000]),
            'modules'          => ['lab', 'pharmacy', 'billing'],
            'status'           => 'active',
        ];
    }
}
