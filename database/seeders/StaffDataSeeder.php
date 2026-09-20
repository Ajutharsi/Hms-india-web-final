<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Populates Department + Staff Management with real, database-backed data.
 * Fixes two gaps found while investigating "missing/incomplete data" on
 * those two pages: (1) the `departments` table was only ever populated by
 * DatabaseSeeder::dummyData(), a method `run()` never calls, so every staff
 * row's Department column read "N/A" and the department dropdown was
 * empty; (2) the 100 `Doctor` records BulkTestDataSeeder creates have no
 * matching `User` account, so Staff Management (which lists
 * `User::role([...,'doctor',...])`) showed zero doctors despite the
 * Doctors module showing 100 — this links them via the same email match
 * `User::doctor()`/`Doctor::user()` already use, rather than creating a
 * second, disconnected set of fake doctors.
 */
class StaffDataSeeder extends Seeder
{
    private const STAFF_PLAN = [
        'nurse' => 8,
        'receptionist' => 5,
        'lab_assistant' => 4,
        'pharmacist' => 4,
        'staff' => 6,
    ];

    public function run(): void
    {
        $departments = Department::count() > 0 ? Department::all() : Department::factory(10)->create();

        $this->linkDoctorsToUserAccounts($departments);
        $this->topUpSupportStaff($departments);
    }

    private function linkDoctorsToUserAccounts($departments): void
    {
        Doctor::whereDoesntHave('user')->get()->each(function (Doctor $doctor) use ($departments) {
            $user = User::create([
                'name' => $doctor->name,
                'email' => $doctor->email,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'department_id' => $departments->random()->id,
                'joining_date' => now()->subDays(rand(30, 1500)),
                'qualification' => $doctor->qualification,
                'employee_id' => 'DOC-' . str_pad((string) $doctor->id, 4, '0', STR_PAD_LEFT),
                'employment_status' => 'active',
            ]);
            $user->assignRole('doctor');
        });
    }

    private function topUpSupportStaff($departments): void
    {
        $employeeNumber = User::where('employee_id', 'like', 'EMP-%')->count() + 1;

        foreach (self::STAFF_PLAN as $role => $target) {
            $existing = User::role($role)->count();

            for ($i = $existing; $i < $target; $i++) {
                $user = User::factory()->create([
                    'employee_id' => 'EMP-' . str_pad((string) $employeeNumber++, 4, '0', STR_PAD_LEFT),
                    'department_id' => $departments->random()->id,
                    'joining_date' => now()->subDays(rand(30, 2000)),
                    'dob' => fake()->dateTimeBetween('-55 years', '-20 years')->format('Y-m-d'),
                    'gender' => fake()->randomElement(['male', 'female']),
                    'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
                    'employment_type' => fake()->randomElement(['full_time', 'full_time', 'part_time', 'contract']),
                    'shift' => fake()->randomElement(['morning', 'evening', 'night']),
                    'qualification' => fake()->randomElement(['Diploma', 'BSc Nursing', 'Certificate', 'BSc', 'MSc']),
                    'experience_years' => fake()->numberBetween(0, 15),
                    'pay_type' => 'monthly',
                    'pay_rate' => fake()->numberBetween(30000, 90000),
                    'currency' => 'LKR',
                    'allowance_transport' => fake()->numberBetween(0, 5000),
                    'allowance_meal' => fake()->numberBetween(0, 3000),
                    'allowance_housing' => fake()->numberBetween(0, 8000),
                    'employment_status' => 'active',
                    'leave_balance' => fake()->numberBetween(0, 21),
                    'emergency_contact_name' => fake()->name(),
                    'emergency_contact_phone' => fake()->phoneNumber(),
                ]);
                $user->assignRole($role);
            }
        }
    }
}
