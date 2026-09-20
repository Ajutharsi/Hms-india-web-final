<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $departments = [
            'Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics',
            'Ophthalmology', 'Dentistry', 'Dermatology', 'Gynecology',
            'General Medicine', 'Surgery', 'ENT', 'Psychiatry',
            'Radiology', 'Oncology', 'Urology', 'Nephrology',
            'Gastroenterology', 'Pulmonology', 'Endocrinology', 'Hematology',
            'Rheumatology', 'Infectious Disease', 'Emergency Medicine', 'Anesthesiology',
        ];

        static $index = 0;
        $name = $departments[$index % count($departments)] . ($index >= count($departments) ? ' ' . ($index + 1) : '');
        $index++;

        return [
            'name'        => $name,
            'code'        => strtoupper($this->faker->unique()->lexify('???')) . '-' . $this->faker->numerify('##'),
            'head_doctor' => 'Dr. ' . $this->faker->name(),
            'phone'       => $this->faker->phoneNumber(),
            'location'    => 'Floor ' . $this->faker->numberBetween(1, 5) . ', Wing ' . $this->faker->randomElement(['A', 'B', 'C']),
            'status'      => 'active',
        ];
    }
}