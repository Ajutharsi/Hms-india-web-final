<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HospitalFactory extends Factory
{
    public function definition(): array
    {
        $planFees = ['basic' => 15000, 'professional' => 35000, 'enterprise' => 75000];
        $plan = $this->faker->randomElement(['basic', 'basic', 'professional', 'professional', 'enterprise']);
        $status = $this->faker->randomElement(['active', 'active', 'active', 'active', 'trial', 'trial', 'suspended', 'cancelled']);
        $createdAt = $this->faker->dateTimeBetween('-14 months', '-1 week');

        $trialEndsAt = null;
        $subStart = null;
        $subEnd = null;

        if ($status === 'trial') {
            $trialEndsAt = $this->faker->dateTimeBetween('now', '+21 days');
        } else {
            $subStart = $createdAt;
            // Spread renewal dates across past/near/future so "expiring soon" and "active" widgets both have real data.
            $subEnd = $this->faker->dateTimeBetween('-10 days', '+75 days');
        }

        return [
            'name'                    => $this->faker->company() . ' ' . $this->faker->randomElement(['Hospital', 'Medical Center', 'Clinic', 'Healthcare', 'General Hospital']),
            'email'                   => $this->faker->unique()->companyEmail(),
            'phone'                   => $this->faker->phoneNumber(),
            'city'                    => $this->faker->city(),
            'country'                 => $this->faker->randomElement(['Sri Lanka', 'India', 'Bangladesh', 'Nepal', 'Pakistan']),
            'status'                  => $status,
            'plan'                    => $plan,
            'monthly_fee'             => $planFees[$plan],
            'trial_ends_at'           => $trialEndsAt,
            'subscription_starts_at'  => $subStart,
            'subscription_ends_at'    => $subEnd,
            'created_at'              => $createdAt,
            'updated_at'              => $createdAt,
        ];
    }
}
