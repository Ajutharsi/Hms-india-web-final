<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\BillingService;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Drug;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Vital;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Bulk TEST data — run with:
 *   php artisan db:seed --class=BulkTestDataSeeder
 *
 * Never run in production. Generates local SVG avatars (no network needed)
 * into storage/app/public/avatars — run `php artisan storage:link` once so
 * they're visible in the browser.
 */
class BulkTestDataSeeder extends Seeder
{
    // Adjust volumes here.
    private const PATIENTS     = 1000;
    private const DOCTORS      = 100;
    private const APPOINTMENTS = 1000;
    private const INVOICES     = 1000;
    private const LAB_ORDERS   = 500;
    private const CHUNK        = 100;

    private $faker;
    private ?int $adminUserId = null;

    private function adminUserId(): ?int
    {
        return $this->adminUserId ??= \App\Models\User::query()->value('id');
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('BulkTestDataSeeder is blocked in production.');

            return;
        }

        $this->faker = \Faker\Factory::create('en_IN'); // Indian names/addresses/phones

        Storage::disk('public')->makeDirectory('avatars/patients');
        Storage::disk('public')->makeDirectory('avatars/doctors');

        $this->seedReferenceData();
        $doctorIds  = $this->seedDoctors();
        $patientIds = $this->seedPatients();
        $this->seedAppointmentsAndClinical($doctorIds, $patientIds);
        $this->seedBilling($patientIds);
        $this->seedLab($doctorIds, $patientIds);
        $this->seedAdmissionsAndVitals($doctorIds, $patientIds);

        $this->command->info('Bulk test data complete. Run `php artisan storage:link` if avatars are not visible.');
    }

    /**
     * Stock photos live in public/assets/patients/000001.jpg … 005000.jpg.
     * Falls back to a generated SVG initials avatar if the file is missing.
     */
    private const STOCK_PHOTOS = 5000;

    private function stockPhotoOrAvatar(string $name, string $folder, int $i): string
    {
        // Offset doctors so they don't reuse the first patients' faces.
        $offset = $folder === 'doctors' ? 2500 : 0;
        $file   = sprintf('%06d', (($i - 1 + $offset) % self::STOCK_PHOTOS) + 1);

        if (file_exists(public_path("assets/patients/{$file}.jpg"))) {
            return "assets/patients/{$file}.jpg";
        }

        return $this->makeAvatar($name, $folder, $i);
    }

    /** Deterministic initials avatar as a local SVG file. Returns relative path. */
    private function makeAvatar(string $name, string $folder, int $i): string
    {
        $colors  = ['7367f0', '28c76f', 'ff9f43', 'ea5455', '00cfe8', '82868b', 'a66bbe', '2e7d32'];
        $bg      = $colors[$i % count($colors)];
        $words   = preg_split('/\s+/', trim(preg_replace('/^Dr\.\s*/', '', $name)));
        $initial = strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1] ?? '', 0, 1));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128">'
            . '<rect width="128" height="128" rx="64" fill="#' . $bg . '"/>'
            . '<text x="50%" y="50%" dy=".08em" text-anchor="middle" dominant-baseline="middle" '
            . 'font-family="Arial, sans-serif" font-size="48" font-weight="600" fill="#fff">'
            . htmlspecialchars($initial) . '</text></svg>';

        $path = "avatars/{$folder}/{$i}.svg";
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    private function seedReferenceData(): void
    {
        if (BillingService::count() < 10) {
            $services = [
                ['Consultation', 'OPD', 500], ['Specialist Consultation', 'OPD', 1500],
                ['ECG', 'Diagnostics', 800], ['X-Ray Chest', 'Radiology', 1200],
                ['Ultrasound Abdomen', 'Radiology', 2000], ['Dressing', 'Procedure', 300],
                ['Injection', 'Procedure', 150], ['Nebulization', 'Procedure', 400],
                ['Room Charge (General)', 'IPD', 2500], ['Room Charge (Private)', 'IPD', 6000],
            ];
            foreach ($services as [$name, $cat, $price]) {
                BillingService::create(['service_name' => $name, 'category' => $cat, 'price' => $price, 'status' => 'active']);
            }
        }

        if (LabTest::count() < 10) {
            $tests = [
                ['CBC', 'Hematology', 'Blood', 400], ['Blood Sugar Fasting', 'Biochemistry', 'Blood', 150],
                ['HbA1c', 'Biochemistry', 'Blood', 600], ['Lipid Profile', 'Biochemistry', 'Blood', 900],
                ['LFT', 'Biochemistry', 'Blood', 800], ['KFT', 'Biochemistry', 'Blood', 800],
                ['TSH', 'Endocrinology', 'Blood', 350], ['Urine Routine', 'Pathology', 'Urine', 200],
                ['Dengue NS1', 'Serology', 'Blood', 700], ['Widal', 'Serology', 'Blood', 300],
            ];
            foreach ($tests as $i => [$name, $cat, $sample, $price]) {
                LabTest::create([
                    'test_code' => 'LT-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT), 'test_name' => $name,
                    'category' => $cat, 'sample_type' => $sample, 'price' => $price,
                    'unit' => 'unit', 'normal_range_male' => 'N/A', 'normal_range_female' => 'N/A', 'status' => 'active',
                ]);
            }
        }

        if (Drug::count() < 20) {
            Drug::factory()->count(50)->create(['current_stock' => 500, 'status' => 'active']);
        }

        if (Ward::count() < 5) {
            // Ward type enum: general|private|semi_private|icu|nicu|emergency
            // Bed  type enum: standard|electric|icu|nicu
            $wards = [
                ['General A', 'general', 20], ['General B', 'general', 20],
                ['Private', 'private', 10], ['ICU', 'icu', 8], ['Semi Private', 'semi_private', 12],
            ];
            foreach ($wards as $i => [$name, $type, $beds]) {
                $ward = Ward::create([
                    'name' => $name, 'type' => $type, 'floor' => (string) ($i + 1),
                    'total_beds' => $beds, 'available_beds' => $beds,
                    'charge_per_day' => $type === 'icu' ? 8000 : 2500, 'status' => 'active',
                ]);
            }
        }
    }

    private function seedDoctors(): array
    {
        $this->command->info('Seeding ' . self::DOCTORS . ' doctors…');
        $ids = [];

        for ($i = 1; $i <= self::DOCTORS; $i++) {
            $name   = 'Dr. ' . $this->faker->name();
            $doctor = Doctor::factory()->create([
                'name'   => $name,
                'phone'  => $this->faker->numerify('9#########'),
                'status' => 'active',
                'photo'  => $this->stockPhotoOrAvatar($name, 'doctors', $i),
            ]);
            $ids[] = $doctor->id;

            // Mon–Sat schedule so slot booking is testable.
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) {
                DoctorSchedule::create([
                    'doctor_id' => $doctor->id, 'day' => $day,
                    'start_time' => '09:00', 'end_time' => '17:00',
                    'slot_duration' => 30, 'is_available' => true,
                ]);
            }
        }

        return $ids;
    }

    private function seedPatients(): array
    {
        $this->command->info('Seeding ' . self::PATIENTS . ' patients…');
        $ids = [];

        for ($i = 1; $i <= self::PATIENTS; $i++) {
            $name = $this->faker->name();
            $ids[] = Patient::factory()->create([
                'name'        => $name,
                'email'       => "patient{$i}." . $this->faker->unique()->safeEmail(),
                'phone'       => $this->faker->numerify('9#########'),
                'address'     => $this->faker->streetAddress() . ', ' . $this->faker->city(),
                'nationality' => 'Indian',
                'photo'       => $this->stockPhotoOrAvatar($name, 'patients', $i),
            ])->id;

            if ($i % 5 === 0) { // 20% get an allergy on record
                PatientAllergy::create([
                    'patient_id' => $ids[count($ids) - 1],
                    'allergen'   => $this->faker->randomElement(['Penicillin', 'Sulfa drugs', 'Aspirin', 'Peanuts', 'Latex', 'Dust']),
                    'type'       => 'drug',
                    'severity'   => $this->faker->randomElement(['mild', 'moderate', 'severe']),
                    'reaction'   => $this->faker->randomElement(['Rash', 'Anaphylaxis', 'Swelling', 'Breathing difficulty']),
                ]);
            }

            if ($i % self::CHUNK === 0) {
                $this->command->getOutput()->write("\r  {$i}/" . self::PATIENTS);
            }
        }
        $this->command->newLine();

        return $ids;
    }

    private function seedAppointmentsAndClinical(array $doctorIds, array $patientIds): void
    {
        $this->command->info('Seeding ' . self::APPOINTMENTS . ' appointments + consultations + prescriptions…');
        $drugs  = Drug::pluck('drug_name')->all();
        $tokens = []; // per doctor+date token counters

        for ($i = 1; $i <= self::APPOINTMENTS; $i++) {
            $doctorId  = $doctorIds[array_rand($doctorIds)];
            $patientId = $patientIds[array_rand($patientIds)];
            $date      = $this->faker->dateTimeBetween('-60 days', '+15 days')->format('Y-m-d');
            $status    = $date < now()->toDateString()
                ? $this->faker->randomElement(['completed', 'completed', 'completed', 'cancelled', 'no_show'])
                : 'scheduled';

            $key = "{$doctorId}|{$date}";
            $tokens[$key] = ($tokens[$key] ?? 0) + 1;

            $appointment = Appointment::create([
                'patient_id' => $patientId, 'doctor_id' => $doctorId,
                'appointment_date' => $date,
                'appointment_time' => $this->faker->randomElement(['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00']),
                'token_number' => $tokens[$key],
                'type' => $this->faker->randomElement(['op', 'op', 'op', 'consult', 'followup', 'emergency']),
                'status' => $status,
                'notes' => $this->faker->optional(0.3)->sentence(),
            ]);

            // Completed visits get a consultation, most get a prescription.
            if ($status === 'completed') {
                $consultation = Consultation::create([
                    'appointment_id' => $appointment->id, 'patient_id' => $patientId, 'doctor_id' => $doctorId,
                    'blood_pressure' => rand(100, 150) . '/' . rand(60, 95),
                    'temperature' => rand(970, 1010) / 10, 'pulse_rate' => rand(60, 110),
                    'weight' => rand(40, 95), 'height' => rand(145, 190),
                    'chief_complaint' => $this->faker->randomElement(['Fever and body ache', 'Chest pain', 'Persistent cough', 'Abdominal pain', 'Headache', 'Back pain', 'Fatigue', 'Joint pain']),
                    'diagnosis' => $this->faker->randomElement(['Viral fever', 'Hypertension', 'Type 2 Diabetes', 'URTI', 'Gastritis', 'Migraine', 'Lumbar strain', 'Anemia']),
                    'treatment_plan' => $this->faker->sentence(8),
                ]);

                if ($this->faker->boolean(75) && $drugs) {
                    $prescription = Prescription::create([
                        'consultation_id' => $consultation->id, 'patient_id' => $patientId, 'doctor_id' => $doctorId,
                        'prescription_date' => $date, 'status' => 'active',
                    ]);
                    for ($d = 0; $d < rand(1, 4); $d++) {
                        PrescriptionItem::create([
                            'prescription_id' => $prescription->id,
                            'medicine_name' => $drugs[array_rand($drugs)],
                            'dosage' => $this->faker->randomElement(['250mg', '500mg', '650mg', '10ml']),
                            'frequency' => $this->faker->randomElement(['1-0-1', '1-1-1', '0-0-1', '1-0-0']),
                            'duration' => rand(3, 10) . ' days',
                            'route' => 'oral',
                        ]);
                    }
                }
            }

            if ($i % self::CHUNK === 0) {
                $this->command->getOutput()->write("\r  {$i}/" . self::APPOINTMENTS);
            }
        }
        $this->command->newLine();
    }

    private function seedBilling(array $patientIds): void
    {
        $this->command->info('Seeding ' . self::INVOICES . ' invoices + payments…');
        $services = BillingService::active()->get();
        $createdBy = $this->adminUserId();

        for ($i = 1; $i <= self::INVOICES; $i++) {
            $subtotal = 0;
            $lines    = [];
            foreach ($services->random(rand(1, 3)) as $svc) {
                $qty       = rand(1, 2);
                $subtotal += $svc->price * $qty;
                $lines[]   = ['item_name' => $svc->service_name, 'item_type' => $svc->category, 'quantity' => $qty, 'unit_price' => $svc->price, 'discount' => 0, 'total' => $svc->price * $qty];
            }

            $invoice = Invoice::create([
                'patient_id' => $patientIds[array_rand($patientIds)],
                'invoice_date' => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
                'due_date' => now()->addDays(30)->toDateString(),
                'subtotal' => $subtotal, 'discount' => 0, 'tax' => 0, 'total' => $subtotal,
                'paid_amount' => 0, 'balance' => $subtotal,
                'status' => 'sent', 'created_by' => $createdBy,
            ]);
            $invoice->items()->createMany($lines);

            // ~65% paid in full, ~15% partial, rest unpaid.
            $roll = rand(1, 100);
            if ($roll <= 80) {
                Payment::create([
                    'invoice_id' => $invoice->id, 'patient_id' => $invoice->patient_id,
                    'amount' => $roll <= 65 ? $subtotal : round($subtotal / 2, 2),
                    'payment_method' => $this->faker->randomElement(['cash', 'card', 'online', 'bank_transfer']), // DB enum: cash|card|bank_transfer|cheque|online
                    'payment_date' => $invoice->invoice_date, 'received_by' => $createdBy,
                ]);
                $invoice->recalculate();
            }

            if ($i % self::CHUNK === 0) {
                $this->command->getOutput()->write("\r  {$i}/" . self::INVOICES);
            }
        }
        $this->command->newLine();
    }

    private function seedLab(array $doctorIds, array $patientIds): void
    {
        $this->command->info('Seeding ' . self::LAB_ORDERS . ' lab orders + results…');
        $tests = LabTest::where('status', 'active')->get();
        $performedBy = $this->adminUserId();

        for ($i = 1; $i <= self::LAB_ORDERS; $i++) {
            $patientId = $patientIds[array_rand($patientIds)];
            $status    = $this->faker->randomElement(['pending', 'completed', 'completed', 'processing']);
            $statusOrderItems    = $this->faker->randomElement(['pending', 'completed']);

            $order = LabOrder::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorIds[array_rand($doctorIds)],
                'order_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
                'status' => $status,
            ]);

            foreach ($tests->random(rand(1, 3)) as $test) {
                $item = LabOrderItem::create(['lab_order_id' => $order->id, 'lab_test_id' => $test->id, 'status' => $statusOrderItems]);

                if ($status === 'completed') {
                    LabResult::create([
                        'lab_order_id' => $order->id, 'lab_order_item_id' => $item->id,
                        'patient_id' => $patientId, 'lab_test_id' => $test->id,
                        'result_value' => (string) (rand(50, 300) / 10), 'unit' => $test->unit,
                        'normal_range' => $test->normal_range_male,
                        'flag' => $this->faker->randomElement(['normal', 'normal', 'normal', 'high', 'low']),
                        'performed_by' => $performedBy, 'result_date' => $order->order_date,
                    ]);
                }
            }
        }
    }

    private function seedAdmissionsAndVitals(array $doctorIds, array $patientIds): void
    {
        $this->command->info('Seeding admissions + vitals…');
        $recordedBy = $this->adminUserId();

        // Admit IP patients into free beds (model events handle bed/ward counts).
        $freeBeds = Bed::where('status', 'available')->take(40)->get();
        $ipIds    = Patient::whereIn('id', $patientIds)->where('patient_type', 'ip')->active()
            ->take($freeBeds->count())->pluck('id');

        foreach ($ipIds as $k => $patientId) {
            $bed = $freeBeds[$k];
            Admission::create([
                'patient_id' => $patientId, 'ward_id' => $bed->ward_id, 'bed_id' => $bed->id,
                'doctor_id' => $doctorIds[array_rand($doctorIds)],
                'admission_date' => now()->subDays(rand(0, 14))->toDateString(),
                'status' => 'admitted',
                'admission_reason' => $this->faker->randomElement(['Observation', 'Post-surgery recovery', 'Severe dehydration', 'Cardiac monitoring', 'Pneumonia treatment']),
            ]);

            for ($v = 0; $v < rand(2, 6); $v++) { // vitals history per admitted patient
                Vital::create([
                    'patient_id' => $patientId, 'recorded_by' => $recordedBy,
                    'blood_pressure' => rand(100, 150) . '/' . rand(60, 95),
                    'temperature' => rand(970, 1015) / 10, 'pulse_rate' => rand(60, 115),
                    'respiratory_rate' => rand(12, 24), 'weight' => rand(40, 95),
                    'recorded_at' => now()->subHours(rand(1, 96)),
                ]);
            }
        }
    }
}
