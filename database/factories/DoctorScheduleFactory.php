<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id'     => Doctor::inRandomOrder()->first()?->id ?? Doctor::factory(),
            'day'           => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
            'start_time'    => '09:00',
            'end_time'      => '17:00',
            'slot_duration' => $this->faker->randomElement([15, 20, 30]),
            'is_available'  => true,
        ];
    }
}
