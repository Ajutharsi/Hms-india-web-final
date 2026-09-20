<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $invoice = Invoice::inRandomOrder()->first();

        return [
            'invoice_id'     => $invoice?->id ?? Invoice::factory(),
            'patient_id'     => $invoice?->patient_id ?? \App\Models\Patient::inRandomOrder()->first()?->id ?? 1,
            'amount'         => $invoice?->paid_amount ?: $this->faker->randomFloat(2, 500, 5000),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'bank_transfer', 'cheque', 'online']),
            'payment_date'   => $invoice?->invoice_date ?? $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'reference_no'   => $this->faker->optional()->numerify('TXN-####'),
            'notes'          => $this->faker->optional()->sentence(),
            'received_by'    => User::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
