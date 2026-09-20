<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity  = $this->faker->numberBetween(1, 3);
        $unitPrice = $this->faker->randomElement([500, 1000, 1500, 2500, 5000]);
        $discount  = 0;
        $total     = ($quantity * $unitPrice) - $discount;

        return [
            'invoice_id'  => Invoice::inRandomOrder()->first()?->id ?? Invoice::factory(),
            'item_name'   => $this->faker->randomElement(['Consultation Fee', 'X-Ray', 'Blood Test', 'Room Charges', 'Nursing Care', 'Medication']),
            'item_type'   => $this->faker->randomElement(['service', 'lab', 'pharmacy', 'room']),
            'description' => $this->faker->optional()->sentence(),
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'discount'    => $discount,
            'total'       => $total,
        ];
    }
}
