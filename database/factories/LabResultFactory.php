<?php

namespace Database\Factories;

use App\Models\LabOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabResultFactory extends Factory
{
    public function definition(): array
    {
        $item = LabOrderItem::with('labOrder', 'labTest')->inRandomOrder()->first();

        return [
            'lab_order_id'      => $item?->lab_order_id ?? 1,
            'lab_order_item_id' => $item?->id ?? 1,
            'patient_id'        => $item?->labOrder?->patient_id ?? \App\Models\Patient::inRandomOrder()->first()?->id ?? 1,
            'lab_test_id'       => $item?->lab_test_id ?? 1,
            'result_value'      => (string) $this->faker->numberBetween(10, 200),
            'unit'              => $item?->labTest?->unit ?? 'mg/dL',
            'normal_range'      => $item?->labTest?->normal_range_male ?? '10-100',
            'flag'              => $this->faker->randomElement(['normal', 'normal', 'normal', 'high', 'low']),
            'remarks'           => $this->faker->optional()->sentence(),
            'performed_by'      => \App\Models\User::inRandomOrder()->first()?->id ?? 1,
            'result_date'       => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
