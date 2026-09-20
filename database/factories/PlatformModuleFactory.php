<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlatformModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2, false),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'category' => 'Administration',
            'icon' => 'tabler-puzzle',
            'color' => 'primary',
            'tier' => 'optional',
            'visibility' => 'visible',
            'depends_on' => [],
            'sort_order' => 0,
        ];
    }
}
