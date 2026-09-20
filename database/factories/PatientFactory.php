<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                   => $this->faker->name(),
            'dob'                    => $this->faker->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
            'gender'                 => $this->faker->randomElement(['male', 'female']),
            'blood_group'            => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'phone'                  => $this->faker->phoneNumber(),
            'email'                  => $this->faker->unique()->safeEmail(),
            'address'                => $this->faker->address(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact'      => $this->faker->phoneNumber(),
            'patient_type'           => $this->faker->randomElement(['op', 'ip']),
            'status'                 => $this->faker->randomElement(['active', 'discharged']),
        ];
    }
}