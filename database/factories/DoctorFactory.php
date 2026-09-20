<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        $specializations = [
            'Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics',
            'Ophthalmology', 'Dentistry', 'Dermatology', 'Gynecology',
            'General Medicine', 'Surgery', 'ENT', 'Psychiatry'
        ];

        return [
            'name'                => 'Dr. ' . $this->faker->name(),
            'specialization'      => $this->faker->randomElement($specializations),
            'department'          => $this->faker->randomElement($specializations),
            'qualification'       => $this->faker->randomElement(['MBBS', 'MBBS MD', 'MBBS MS', 'MBBS PhD']),
            'registration_number' => 'REG-' . $this->faker->unique()->numerify('####'),
            'phone'               => $this->faker->phoneNumber(),
            'email'               => $this->faker->unique()->safeEmail(),
            'consultation_fee'    => $this->faker->randomElement([500, 1000, 1500, 2000, 2500, 3000]),
            'status'              => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),

            // No photo by default — same convention as PatientFactory. Doctor::getPhotoUrlAttribute()
            // falls back to the shared default avatar, so seeded doctors render correctly without a
            // fake generated image (the previous generatePhoto() produced malformed SVGs — see below).
            'license_file' => $this->generateDoc(),
            'license_file_name' => 'license_' . $this->faker->uuid() . '.pdf',
            'certificate_file' => $this->generateDoc(),
            'certificate_file_name' => 'certificate_' . $this->faker->uuid() . '.pdf',
            'id_file' => $this->generateDoc(),
            'id_file_name' => 'id_' . $this->faker->uuid() . '.pdf',
        ];
    }

    private function generateDoc(): ?string
    {
        try {
            Storage::disk('public')->makeDirectory('doctors/docs');
            
            $content = "Dummy document generated at: " . now() . "\n";
            $content .= "This is a placeholder document for testing.\n";
            $content .= str_repeat("Sample text. ", 50);
            
            $filename = 'doc_' . uniqid() . '.txt';
            Storage::disk('public')->put('doctors/docs/' . $filename, $content);
            
            return 'doctors/docs/' . $filename;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}
















































