<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 500, 10000);
        $discount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $tax      = $this->faker->randomFloat(2, 0, $subtotal * 0.05);
        $total    = $subtotal - $discount + $tax;
        $paid     = $this->faker->randomElement([0, $total / 2, $total]);
        $balance  = $total - $paid;
        $status   = $paid == 0 ? 'sent' : ($balance == 0 ? 'paid' : 'partial');

        return [
            'patient_id'   => Patient::inRandomOrder()->first()?->id ?? 1,
            'invoice_date' => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'due_date'     => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'tax'          => $tax,
            'total'        => $total,
            'paid_amount'  => $paid,
            'balance'      => $balance,
            'status'       => $status,
            'notes'        => $this->faker->optional()->sentence(),
        ];
    }
}