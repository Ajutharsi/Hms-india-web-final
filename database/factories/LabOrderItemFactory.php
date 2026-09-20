<?php

namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\LabTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lab_order_id' => LabOrder::inRandomOrder()->first()?->id ?? LabOrder::factory(),
            'lab_test_id'  => LabTest::inRandomOrder()->first()?->id ?? LabTest::factory(),
            'status'       => $this->faker->randomElement(['pending', 'completed']),
        ];
    }
}
