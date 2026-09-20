<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'hospital_id'   => Hospital::factory(),
            'subject'       => $this->faker->sentence(6),
            'description'   => $this->faker->paragraph(),
            'contact_name'  => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'priority'      => $this->faker->randomElement(SupportTicket::PRIORITIES),
            'category'      => $this->faker->randomElement(SupportTicket::CATEGORIES),
            'status'        => 'open',
        ];
    }
}
