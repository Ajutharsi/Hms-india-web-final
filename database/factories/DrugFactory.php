<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DrugFactory extends Factory
{
    public function definition(): array
    {
        $strength = $this->faker->randomElement(['500mg', '250mg', '100mg', '50mg', '10mg']);
        $name     = $this->faker->randomElement([
            'Paracetamol', 'Amoxicillin', 'Ibuprofen', 'Metformin',
            'Atorvastatin', 'Omeprazole', 'Amlodipine', 'Lisinopril',
            'Cetirizine', 'Azithromycin', 'Ciprofloxacin', 'Doxycycline',
            'Pantoprazole', 'Metronidazole', 'Salbutamol', 'Prednisolone',
            'Diclofenac', 'Ranitidine', 'Losartan', 'Clopidogrel'
        ]);

        return [
            'drug_name'       => $name . ' ' . $strength,
            'generic_name'    => $this->faker->word(),
            'category'        => $this->faker->randomElement(['Antibiotic', 'Analgesic', 'Antidiabetic', 'Antihypertensive', 'Antihistamine', 'Antacid']),
            'form'            => $this->faker->randomElement(['tablet', 'capsule', 'syrup', 'injection', 'cream']),
            'strength'        => $strength,
            'manufacturer'    => $this->faker->company(),
            'unit_price'      => $this->faker->randomFloat(2, 10, 1000),
            'current_stock'   => $this->faker->numberBetween(0, 500),
            'min_stock_level' => 10,
            'expiry_date'     => $this->faker->dateTimeBetween('+1 month', '+3 years')->format('Y-m-d'),
            'status'          => 'active',
        ];
    }
}