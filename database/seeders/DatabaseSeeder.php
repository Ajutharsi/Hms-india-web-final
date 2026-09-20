<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\LabTest;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\Drug;
use App\Models\DrugStock;
use App\Models\DrugDispensing;
use App\Models\DrugDispensingItem;
use App\Models\BillingService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\DietPlan;
use App\Models\FoodLog;
use App\Models\FluidLog;
use App\Models\StaffRound;
use App\Models\RoundAssignment;
use App\Models\Referral;
use App\Models\ConsentForm;
use App\Models\PatientAllergy;
use App\Models\PatientProblem;
use App\Models\PatientAttachment;
use App\Models\PatientMedicalRecord;
use App\Models\HospitalSetting;

class DatabaseSeeder extends Seeder
{
  public function run(): void
  {
    $this->call(RolesAndPermissionsSeeder::class);
    $this->call(BulkTestDataSeeder::class);
    $this->call(HospitalSeeder::class);
    // Previously never called anywhere (confirmed by a repo-wide search) —
    // NotificationService::send() silently no-ops without a matching
    // active template, so this was a dead letter for every notification
    // type, not just Support Centre's new 'ticket_reply' template. See
    // SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md.
    $this->call(NotificationTemplateSeeder::class);
    // Populates Department + links BulkTestDataSeeder's 100 Doctor records
    // to real User accounts, and tops up nurse/receptionist/lab/pharmacist/
    // staff accounts — fixes User/Staff Management showing missing/
    // incomplete data (empty departments, zero doctors, bare demo accounts).
    $this->call(StaffDataSeeder::class);
    // Feature Management catalog — see SUPER_ADMIN_FEATURE_MANAGEMENT_REPORT.md.
    $this->call(PlatformModuleSeeder::class);
  }
  public function dummyData()
  {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    HospitalSetting::updateOrCreate([], [
      'hospital_name' => 'City Care Hospital',
      'hospital_code' => 'CCH-001',
      'address'       => '123 Galle Road',
      'city'          => 'Colombo',
      'country'       => 'Sri Lanka',
      'phone'         => '+94 11 234 5678',
      'email'         => 'info@citycarehospital.lk',
      'website'       => 'https://citycarehospital.lk',
      'registration_number' => 'REG-HOSP-2024-001',
      'tax_number'    => 'TAX-998877',
      'currency'      => 'LKR',
      'timezone'      => 'Asia/Colombo',
      'footer_note'   => 'Thank you for choosing City Care Hospital.',
      'date_format'         => 'd M Y',
      'mrn_prefix'          => 'HMS',
      'doctor_id_prefix'    => 'DR',
      'employee_id_prefix'  => 'EMP',
      'low_stock_threshold' => 10,
      'near_expiry_days'    => 30,
      'round_alert_hours'   => 1,
      'auto_patient_login'  => true,
      'auto_doctor_login'   => true,
      'features_enabled'    => HospitalSetting::DEFAULT_FEATURES,
    ]);


    $departments = Department::factory(10)->create();


    $doctors = Doctor::factory(20)->create();

    foreach ($doctors as $doctor) {
      $user = User::create([
        'name'              => $doctor->name,
        'email'             => $doctor->email,
        'password'          => Hash::make('password123'),
        'email_verified_at' => now(),
        'department_id'     => $departments->random()->id,
        'joining_date'      => now()->subDays(rand(30, 1500)),
        'qualification'     => $doctor->qualification,
      ]);
      $user->assignRole('doctor');

      foreach (fake()->randomElements(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], rand(3, 5)) as $day) {
        DoctorSchedule::factory()->create([
          'doctor_id' => $doctor->id,
          'day'       => $day,
        ]);
      }
    }


    $patients = Patient::factory(25)->create();

    foreach ($patients->random(12) as $patient) {
      $user = User::create([
        'name'              => $patient->name,
        'email'             => $patient->email ?? fake()->unique()->safeEmail(),
        'password'          => Hash::make('password123'),
        'email_verified_at' => now(),
      ]);
      $user->assignRole('patient');
      $patient->update(['user_id' => $user->id, 'email' => $user->email]);
    }

    $staffPlan = [
      'nurse'         => 8,
      'receptionist'  => 5,
      'lab_assistant' => 4,
      'pharmacist'    => 4,
      'staff'         => 6,
    ];

    $employeeNumber = 1;
    foreach ($staffPlan as $role => $count) {
      for ($i = 0; $i < $count; $i++) {
        $user = User::factory()->create([
          'employee_id'       => 'EMP-' . str_pad($employeeNumber++, 4, '0', STR_PAD_LEFT),
          'department_id'     => $departments->random()->id,
          'joining_date'      => now()->subDays(rand(30, 2000)),
          'dob'               => fake()->dateTimeBetween('-55 years', '-20 years')->format('Y-m-d'),
          'gender'            => fake()->randomElement(['male', 'female']),
          'blood_group'       => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
          'employment_type'   => fake()->randomElement(['full_time', 'full_time', 'part_time', 'contract']),
          'shift'             => fake()->randomElement(['morning', 'evening', 'night']),
          'qualification'     => fake()->randomElement(['Diploma', 'BSc Nursing', 'Certificate', 'BSc', 'MSc']),
          'experience_years'  => fake()->numberBetween(0, 15),
          'pay_type'          => 'monthly',
          'pay_rate'          => fake()->numberBetween(30000, 90000),
          'currency'          => 'LKR',
          'allowance_transport' => fake()->numberBetween(0, 5000),
          'allowance_meal'      => fake()->numberBetween(0, 3000),
          'allowance_housing'   => fake()->numberBetween(0, 8000),
          'employment_status' => 'active',
          'leave_balance'     => fake()->numberBetween(0, 21),
          'emergency_contact_name'  => fake()->name(),
          'emergency_contact_phone' => fake()->phoneNumber(),
        ]);
        $user->assignRole($role);
      }
    }


    $wards = Ward::factory(8)->create();


    $appointments = Appointment::factory(60)->create();


    $completedAppointments = $appointments->where('status', 'completed')->take(30);
    foreach ($completedAppointments as $appointment) {
      $consultation = Consultation::factory()->create([
        'appointment_id' => $appointment->id,
        'patient_id'     => $appointment->patient_id,
        'doctor_id'      => $appointment->doctor_id,
      ]);

      if (fake()->boolean(70)) {
        $prescription = Prescription::factory()->create([
          'consultation_id' => $consultation->id,
          'patient_id'      => $consultation->patient_id,
          'doctor_id'       => $consultation->doctor_id,
        ]);

        PrescriptionItem::factory(rand(1, 4))->create([
          'prescription_id' => $prescription->id,
        ]);
      }
    }


    $labTests = LabTest::factory(20)->create();
    $labOrders = LabOrder::factory(25)->create();

    foreach ($labOrders as $order) {
      $items = LabOrderItem::factory(rand(1, 3))->create([
        'lab_order_id' => $order->id,
        'lab_test_id'  => fn() => $labTests->random()->id,
      ]);

      foreach ($items as $item) {
        if ($item->status === 'completed') {
          LabResult::factory()->create([
            'lab_order_id'      => $order->id,
            'lab_order_item_id' => $item->id,
            'patient_id'        => $order->patient_id,
            'lab_test_id'       => $item->lab_test_id,
          ]);
        }
      }
    }


    $drugs = Drug::factory(30)->create();
    foreach ($drugs as $drug) {
      DrugStock::factory(rand(1, 2))->create(['drug_id' => $drug->id]);
    }


    $dispensings = DrugDispensing::factory(20)->create();
    foreach ($dispensings as $dispensing) {
      DrugDispensingItem::factory(rand(1, 3))->create([
        'dispensing_id' => $dispensing->id,
      ]);
    }


    BillingService::factory(15)->create();

    $invoices = Invoice::factory(30)->create();
    foreach ($invoices as $invoice) {
      InvoiceItem::factory(rand(1, 4))->create(['invoice_id' => $invoice->id]);

      if ($invoice->paid_amount > 0) {
        Payment::factory()->create([
          'invoice_id' => $invoice->id,
          'patient_id' => $invoice->patient_id,
          'amount'     => $invoice->paid_amount,
          'payment_date' => $invoice->invoice_date,
        ]);
      }
    }


    Admission::factory(10)->create();
    Admission::factory(8)->discharged()->create();

    $admittedPatientIds = Admission::where('status', 'admitted')->pluck('patient_id')->unique();

    foreach ($admittedPatientIds as $patientId) {
      DietPlan::factory()->create(['patient_id' => $patientId]);

      FoodLog::factory(rand(2, 6))->create(['patient_id' => $patientId]);
      FluidLog::factory(rand(2, 6))->create(['patient_id' => $patientId]);
      StaffRound::factory(rand(2, 5))->create(['patient_id' => $patientId]);
    }


    $roundStaff = User::role(['nurse', 'staff'])->inRandomOrder()->get();
    foreach ($wards as $index => $ward) {
      if ($roundStaff->isEmpty()) {
        break;
      }
      $staffMember = $roundStaff[$index % $roundStaff->count()];
      RoundAssignment::factory()->create([
        'ward_id'  => $ward->id,
        'staff_id' => $staffMember->id,
      ]);
    }


    Referral::factory(15)->create();


    ConsentForm::factory(15)->create();


    foreach ($patients->random(15) as $patient) {
      PatientAllergy::factory(rand(1, 2))->create(['patient_id' => $patient->id]);
    }
    foreach ($patients->random(15) as $patient) {
      PatientProblem::factory(rand(1, 3))->create(['patient_id' => $patient->id]);
    }
    foreach ($patients->random(10) as $patient) {
      PatientAttachment::factory(rand(1, 2))->create(['patient_id' => $patient->id]);
    }
    foreach ($patients as $patient) {
      PatientMedicalRecord::factory(rand(1, 3))->create(['patient_id' => $patient->id]);
    }

    $this->command->info('All dummy data seeded successfully!');
  }
}
