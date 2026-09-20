<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BillingServiceFactory extends Factory
{
    public function definition(): array
    {
        $services = [
            'Consultation Fee', 'X-Ray', 'ECG', 'Ultrasound', 'MRI Scan', 'CT Scan',
            'Blood Test Panel', 'Surgery - Minor', 'Surgery - Major', 'Room Charges',
            'Nursing Care', 'Physiotherapy Session', 'Vaccination', 'Dressing',
            'Ambulance Service', 'ICU Charges', 'Anesthesia', 'Emergency Care',
        ];

        return [
            'service_name' => $this->faker->unique()->randomElement($services),
            'category'     => $this->faker->randomElement(['Consultation', 'Diagnostic', 'Surgery', 'Room', 'Nursing', 'Emergency']),
            'price'        => $this->faker->randomElement([500, 1000, 1500, 2500, 5000, 10000, 15000]),
            'description'  => $this->faker->optional()->sentence(),
            'status'       => 'active',
        ];
    }
}
