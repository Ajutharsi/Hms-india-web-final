<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    public function definition(): array
    {
        static $number = 0;
        $number++;

        $doctors = Doctor::inRandomOrder()->limit(2)->pluck('id');
        $referredBy = $doctors->get(0) ?? Doctor::factory();
        $referredTo = $doctors->get(1) ?? Doctor::factory();

        return [
            'referral_no'   => 'REF-' . str_pad($number, 4, '0', STR_PAD_LEFT),
            'patient_id'    => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'referred_by'   => $referredBy,
            'referred_to'   => $referredTo,
            'reason'        => $this->faker->sentence(),
            'notes'         => $this->faker->optional()->sentence(),
            'priority'      => $this->faker->randomElement(['routine', 'routine', 'urgent', 'emergency']),
            'status'        => $this->faker->randomElement(['pending', 'accepted', 'completed', 'cancelled']),
            'referral_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }
}
