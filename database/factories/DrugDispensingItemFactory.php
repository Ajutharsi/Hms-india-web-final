<?php

namespace Database\Factories;

use App\Models\DrugDispensing;
use App\Models\Drug;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrugDispensingItemFactory extends Factory
{
    public function definition(): array
    {
        $drug     = Drug::inRandomOrder()->first() ?? Drug::factory()->create();
        $quantity = $this->faker->numberBetween(1, 30);

        return [
            'dispensing_id'       => DrugDispensing::inRandomOrder()->first()?->id ?? DrugDispensing::factory(),
            'drug_id'             => $drug->id,
            'medicine_name'       => $drug->drug_name,
            'quantity_prescribed' => $quantity,
            'quantity_dispensed'  => $quantity,
            'unit_price'          => $drug->unit_price,
            'total_price'         => $quantity * $drug->unit_price,
        ];
    }
}
