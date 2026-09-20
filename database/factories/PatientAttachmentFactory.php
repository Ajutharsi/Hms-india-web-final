<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class PatientAttachmentFactory extends Factory
{
    public function definition(): array
    {
        $fileName = $this->faker->word() . '.pdf';

        return [
            'patient_id'  => Patient::inRandomOrder()->first()?->id ?? Patient::factory(),
            'file_name'   => $fileName,
            'file_path'   => 'patients/attachments/' . $fileName,
            'file_type'   => 'application/pdf',
            'file_size'   => (string) $this->faker->numberBetween(50, 2000) . ' KB',
            'description' => $this->faker->randomElement(['Lab report', 'Discharge summary', 'X-Ray scan', 'Insurance document', 'Referral letter']),
            'uploaded_by' => User::inRandomOrder()->first()?->id ?? 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($attachment) {
            Storage::disk('public')->put(
                $attachment->file_path,
                "Sample attachment for {$attachment->file_name}"
            );
        });
    }
}
