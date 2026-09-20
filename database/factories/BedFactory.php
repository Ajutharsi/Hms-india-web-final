<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Bed>
 */
class BedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ward = Ward::inRandomOrder()->first() ?? Ward::factory()->create();

        return [
            'bed_no'  => $ward->ward_no . '-B' . str_pad($this->faker->unique()->numberBetween(1, 999), 2, '0', STR_PAD_LEFT),
            'ward_id' => $ward->id,
            'type'    => $this->faker->randomElement(['standard', 'electric', 'icu', 'nicu']),
            'status'  => 'available',
            'notes'   => null,
        ];
    }
}
