<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;

/**
 * Repoints EXISTING patients/doctors at the stock photos in
 * public/storage/patients/ — use this instead of re-running the full
 * BulkTestDataSeeder when only the photo paths are wrong:
 *
 *   php artisan db:seed --class=ReassignPhotosSeeder
 */
class ReassignPhotosSeeder extends Seeder
{
    private const STOCK_PHOTOS = 5000;

    public function run(): void
    {
        $n = 0;
        Patient::chunkById(200, function ($patients) use (&$n) {
            foreach ($patients as $patient) {
                $patient->updateQuietly(['photo' => $this->photo($n++)]);
            }
        });
        $this->command->info("Patients updated: {$n}");

        $d = 2500; // offset so doctors get different faces
        Doctor::chunkById(200, function ($doctors) use (&$d) {
            foreach ($doctors as $doctor) {
                $doctor->updateQuietly(['photo' => $this->photo($d++)]);
            }
        });
        $this->command->info('Doctors updated: ' . ($d - 2500));
    }

    private function photo(int $n): ?string
    {
        $file = sprintf('%06d', ($n % self::STOCK_PHOTOS) + 1);

        return file_exists(public_path("assets/patients/{$file}.jpg"))
            ? "assets/patients/{$file}.jpg"
            : null;
    }
}
