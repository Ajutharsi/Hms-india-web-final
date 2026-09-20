<?php

namespace Database\Factories;

use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionItemFactory extends Factory
{
    public function definition(): array
    {
        $medicines = ['Paracetamol', 'Amoxicillin', 'Ibuprofen', 'Metformin', 'Atorvastatin', 'Omeprazole', 'Cetirizine', 'Azithromycin'];

        return [
            'prescription_id' => Prescription::inRandomOrder()->first()?->id ?? Prescription::factory(),
            'medicine_name'   => $this->faker->randomElement($medicines),
            'dosage'          => $this->faker->randomElement(['500mg', '250mg', '1 tablet', '5ml']),
            'frequency'       => $this->faker->randomElement(['Once daily', 'Twice daily', 'Three times daily', 'Every 6 hours']),
            'duration'        => $this->faker->randomElement(['3 days', '5 days', '7 days', '10 days']),
            'route'           => $this->faker->randomElement(['Oral', 'IV', 'IM', 'Topical']),
            'instructions'    => $this->faker->randomElement(['After food', 'Before food', 'With water', null]),
        ];
    }
}
