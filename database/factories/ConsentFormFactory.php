<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentFormFactory extends Factory
{
    public function definition(): array
    {
        static $number = 0;
        $number++;

        $patient = Patient::inRandomOrder()->first() ?? Patient::factory()->create();
        $type    = $this->faker->randomElement(['general', 'surgery', 'anesthesia', 'blood_transfusion', 'procedure', 'other']);

        return [
            'consent_no'     => 'CNS-' . str_pad($number, 4, '0', STR_PAD_LEFT),
            'patient_id'     => $patient->id,
            'consent_type'   => $type,
            'procedure_name' => $type !== 'general' ? $this->faker->words(3, true) : null,
            'description'    => $this->faker->paragraph(),
            'patient_name'   => $patient->name,
            'witness_name'   => $this->faker->optional()->name(),
            'doctor_name'    => 'Dr. ' . $this->faker->lastName(),
            'consent_date'   => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status'         => $this->faker->randomElement(['pending', 'signed', 'signed', 'declined']),
            'created_by'     => User::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
