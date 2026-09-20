<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $attempts = 0;

        while (true) {
            try {
                DB::transaction(fn () => $this->seed());
                return;
            } catch (QueryException $e) {
                $attempts++;
                $message = $e->getMessage();
                $isRetryable = str_contains($message, 'Deadlock')
                    || str_contains($message, 'Duplicate entry')
                    || str_contains($message, 'Lock wait timeout');

                if (! $isRetryable || $attempts >= 5) {
                    throw $e;
                }

                usleep(200_000 * $attempts);
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }
        }
    }

    private function seed(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            // Patients
            'view patients', 'create patients', 'edit patients', 'delete patients',
            // Appointments
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            // Doctors
            'view doctors', 'create doctors', 'edit doctors', 'delete doctors',
            // Lab
            'view lab reports', 'create lab reports', 'edit lab reports', 'delete lab reports',
            // Pharmacy
            'view pharmacy', 'create pharmacy', 'edit pharmacy', 'delete pharmacy',
            // Billing
            'view billing', 'create billing', 'edit billing', 'delete billing',
            // Reports
            'view reports',
            // Shift Handovers
            'view handovers', 'create handovers', 'edit handovers', 'accept handovers', 'delete handovers',
            // Settings
            'manage settings',
            // Super Admin only
            'manage hospitals',
            'manage admins',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $superAdmin  = Role::firstOrCreate(['name' => 'super_admin']);
        $admin       = Role::firstOrCreate(['name' => 'admin']);
        $doctor      = Role::firstOrCreate(['name' => 'doctor']);
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        $labAssistant = Role::firstOrCreate(['name' => 'lab_assistant']);
        $pharmacist  = Role::firstOrCreate(['name' => 'pharmacist']);
        $nurse       = Role::firstOrCreate(['name' => 'nurse']);
        $staff       = Role::firstOrCreate(['name' => 'staff']);
        $patient     = Role::firstOrCreate(['name' => 'patient']);

        // Super Admin — all permissions
        $superAdmin->syncPermissions(Permission::all());

        // Admin
        $admin->syncPermissions([
            'view patients', 'create patients', 'edit patients', 'delete patients',
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            'view doctors', 'create doctors', 'edit doctors', 'delete doctors',
            'view lab reports',
            'view pharmacy',
            'view billing', 'create billing', 'edit billing',
            'view reports',
            'view handovers',
            'manage settings',
        ]);

        // Doctor
        $doctor->syncPermissions([
            'view patients', 'edit patients',
            'view appointments',
            'view lab reports', 'create lab reports',
            'view pharmacy',
            'view reports',
        ]);

        // Receptionist
        $receptionist->syncPermissions([
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view billing', 'create billing',
        ]);

        // Lab Assistant
        $labAssistant->syncPermissions([
            'view patients',
            'view appointments',
            'view lab reports', 'create lab reports', 'edit lab reports',
        ]);

        // Pharmacist
        $pharmacist->syncPermissions([
            'view patients',
            'view appointments',
            'view pharmacy', 'create pharmacy', 'edit pharmacy',
        ]);

        // Nurse
        $nurse->syncPermissions([
            'view patients', 'edit patients',
            'view appointments',
            'view lab reports',
            'view handovers', 'create handovers', 'accept handovers',
        ]);

        // Staff
        $staff->syncPermissions([
            'view patients',
            'edit patients',
            'view appointments',
        ]);

        // Patient 
        $patient->syncPermissions([
            'view appointments',
        ]);

        // Demo login users — never seeded outside local/testing, since they
        // use a fixed, publicly-known password. See
        // SUPER_ADMIN_MODULE_ANALYSIS.md §7/§14.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            [
                'name'  => 'Super Admin',
                'email' => 'superadmin@hospital.com',
                'role'  => 'super_admin',
            ],
            [
                'name'  => 'Admin User',
                'email' => 'admin@hospital.com',
                'role'  => 'admin',
            ],
            [
                'name'  => 'Dr. Kumar',
                'email' => 'doctor@hospital.com',
                'role'  => 'doctor',
            ],
            [
                'name'  => 'Reception Staff',
                'email' => 'reception@hospital.com',
                'role'  => 'receptionist',
            ],
            [
                'name'  => 'Lab Staff',
                'email' => 'lab@hospital.com',
                'role'  => 'lab_assistant',
            ],
            [
                'name'  => 'Pharmacist',
                'email' => 'pharmacist@hospital.com',
                'role'  => 'pharmacist',
            ],
            [
                'name'  => 'Nurse Staff',
                'email' => 'nurse@hospital.com',
                'role'  => 'nurse',
            ],
            [
                'name'  => 'Staff Member',
                'email' => 'staff@hospital.com',
                'role'  => 'staff',
            ],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'              => $u['name'],
                    'password'          => bcrypt('password123'),
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }
        }

        // Links the demo admin to the platform's default hospital, so
        // "Login as Hospital Admin" has a real target in local/testing.
        // The equivalent backfill in add_hospital_id_to_users_table only
        // covers users that already existed *before* that migration ran —
        // it can never reach users a seeder creates afterward, since
        // migrations always run before seeders.
        $defaultHospital = \App\Models\Hospital::default()->first();
        if ($defaultHospital) {
            User::where('email', 'admin@hospital.com')
                ->whereNull('hospital_id')
                ->update(['hospital_id' => $defaultHospital->id]);
        }
    }
}