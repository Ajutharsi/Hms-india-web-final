<?php

namespace Database\Factories;

use App\Models\Ward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoundAssignmentFactory extends Factory
{
    public function definition(): array
    {
        $frequency = $this->faker->randomElement([1, 2, 4, 6]);

        return [
            'ward_id'         => Ward::inRandomOrder()->first()?->id ?? Ward::factory(),
            'staff_id'        => User::role(['nurse', 'staff'])->inRandomOrder()->first()?->id ?? User::inRandomOrder()->first()?->id ?? 1,
            'assigned_by'     => User::role(['admin', 'super_admin'])->inRandomOrder()->first()?->id ?? User::inRandomOrder()->first()?->id ?? 1,
            'frequency_hours' => $frequency,
            'next_round_at'   => now()->addHours($frequency),
            'last_round_at'   => null,
            'status'          => 'active',
            'notes'           => $this->faker->optional()->sentence(),
        ];
    }
}
