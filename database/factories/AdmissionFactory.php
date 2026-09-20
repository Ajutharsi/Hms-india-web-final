<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Ward;
use App\Models\Bed;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdmissionFactory extends Factory
{
    public function definition(): array
    {
        $bed = Bed::where('status', 'available')->inRandomOrder()->first();

        return [
            'patient_id'       => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'ward_id'          => $bed?->ward_id ?? Ward::inRandomOrder()->first()?->id ?? 1,
            'bed_id'           => $bed?->id ?? Bed::inRandomOrder()->first()?->id ?? 1,
            'doctor_id'        => Doctor::inRandomOrder()->first()?->id ?? 1,
            'admission_date'   => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'discharge_date'   => null,
            'status'           => 'admitted',
            'admission_reason' => $this->faker->sentence(),
            'discharge_notes'  => null,
            'total_charges'    => 0,
        ];
    }

    /**
     * Transition the admission to discharged via update(), so the Admission
     * model's discharge event correctly frees the bed and increments the
     * ward's available_beds count (creating directly with status=discharged
     * would leave the bed permanently marked occupied).
     */
    public function discharged(): static
    {
        return $this->afterCreating(function ($admission) {
            $admission->update([
                'discharge_date'  => $this->faker->dateTimeBetween($admission->admission_date, 'now')->format('Y-m-d'),
                'discharge_notes' => $this->faker->sentence(),
                'total_charges'   => $this->faker->randomFloat(2, 5000, 50000),
                'status'          => 'discharged',
            ]);
        });
    }
}
