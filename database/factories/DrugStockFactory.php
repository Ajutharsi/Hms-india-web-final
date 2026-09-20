<?php

namespace Database\Factories;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrugStockFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(50, 500);

        return [
            'drug_id'      => Drug::inRandomOrder()->first()?->id ?? Drug::factory(),
            'type'         => 'in',
            'quantity'     => $quantity,
            'balance'      => $quantity,
            'reference'    => 'PO-' . $this->faker->numerify('####'),
            'reason'       => 'Stock purchase',
            'performed_by' => User::inRandomOrder()->first()?->id ?? 1,
            'notes'        => $this->faker->optional()->sentence(),
        ];
    }
}
