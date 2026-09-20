<?php

namespace Database\Seeders;

use App\Models\PlatformModule;
use Illuminate\Database\Seeder;

/**
 * Super Admin Feature Management catalog — see
 * SUPER_ADMIN_FEATURE_MANAGEMENT_REPORT.md. Replaces the hardcoded module
 * array previously only defined inline in resources/views/settings/index.blade.php
 * (icon/color/name/description for the 11 real toggleable features) with a
 * real, queryable table, per SUPER_ADMIN_MASTER_PLAN.md §5.9 — without
 * touching that view or any other existing settings screen, which continue
 * to work exactly as before.
 *
 * 11 entries are independently toggleable and correspond 1:1 to
 * HospitalSetting::DEFAULT_FEATURES' real keys. 11 are structural "core"
 * platform capabilities that cannot be disabled (always enabled). 2
 * (Radiology, Blood Bank) are real menu-visible modules whose availability
 * today is genuinely derived from another feature's flag (menu.json gates
 * both under 'lab'/'ward' respectively) — they are catalogued for
 * completeness and dependency-awareness, not given a second, redundant
 * toggle of their own.
 */
class PlatformModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // Patient Management
            ['key' => 'patients', 'name' => 'Patient Management', 'category' => 'Patient Management', 'tier' => 'core', 'icon' => 'tabler-users', 'color' => 'primary', 'description' => 'Core patient registry, records, and demographics.', 'depends_on' => []],
            ['key' => 'patient_portal', 'name' => 'Patient Portal', 'category' => 'Patient Management', 'tier' => 'optional', 'icon' => 'tabler-user-circle', 'color' => 'info', 'description' => 'Self-service portal for patients to view appointments, prescriptions, bills, and lab results.', 'depends_on' => []],
            ['key' => 'consent_forms', 'name' => 'Consent Forms', 'category' => 'Patient Management', 'tier' => 'optional', 'icon' => 'tabler-writing', 'color' => 'secondary', 'description' => 'Digital patient consent form capture and storage.', 'depends_on' => []],
            ['key' => 'referrals', 'name' => 'Referrals', 'category' => 'Patient Management', 'tier' => 'optional', 'icon' => 'tabler-transfer', 'color' => 'success', 'description' => 'Inter-department and external patient referral tracking.', 'depends_on' => []],

            // Doctor Management
            ['key' => 'doctors', 'name' => 'Doctor Management', 'category' => 'Doctor Management', 'tier' => 'core', 'icon' => 'tabler-stethoscope', 'color' => 'primary', 'description' => 'Doctor roster, schedules, and specializations.', 'depends_on' => []],

            // Nurse Management
            ['key' => 'nurses', 'name' => 'Nurse Management', 'category' => 'Nurse Management', 'tier' => 'core', 'icon' => 'tabler-nurse', 'color' => 'primary', 'description' => 'Nursing staff roster and assignment.', 'depends_on' => []],
            ['key' => 'ward', 'name' => 'Ward & Inpatient Care', 'category' => 'Nurse Management', 'tier' => 'optional', 'icon' => 'tabler-building-hospital', 'color' => 'info', 'description' => 'Ward beds, admissions, and inpatient tracking.', 'depends_on' => []],
            ['key' => 'food_intake', 'name' => 'Food Intake Tracking', 'category' => 'Nurse Management', 'tier' => 'optional', 'icon' => 'tabler-meat', 'color' => 'warning', 'description' => 'Inpatient food intake logging.', 'depends_on' => ['ward']],
            ['key' => 'fluid_intake', 'name' => 'Fluid Intake Tracking', 'category' => 'Nurse Management', 'tier' => 'optional', 'icon' => 'tabler-droplet', 'color' => 'info', 'description' => 'Inpatient fluid intake/output logging.', 'depends_on' => ['ward']],
            ['key' => 'staff_rounds', 'name' => 'Staff Rounds', 'category' => 'Nurse Management', 'tier' => 'optional', 'icon' => 'tabler-clock-check', 'color' => 'primary', 'description' => 'Scheduled nursing round assignments and alerts.', 'depends_on' => ['ward']],

            // Pharmacy
            ['key' => 'pharmacy', 'name' => 'Pharmacy', 'category' => 'Pharmacy', 'tier' => 'optional', 'icon' => 'tabler-pill', 'color' => 'danger', 'description' => 'Drug master, stock, and dispensing.', 'depends_on' => []],

            // Laboratory
            ['key' => 'lab', 'name' => 'Laboratory', 'category' => 'Laboratory', 'tier' => 'optional', 'icon' => 'tabler-flask', 'color' => 'warning', 'description' => 'Lab test catalog, orders, and results.', 'depends_on' => []],
            ['key' => 'radiology', 'name' => 'Radiology', 'category' => 'Radiology', 'tier' => 'optional', 'icon' => 'tabler-radioactive', 'color' => 'warning', 'description' => 'Radiology orders and imaging reports.', 'depends_on' => ['lab']],

            // Billing
            ['key' => 'billing', 'name' => 'Billing', 'category' => 'Billing', 'tier' => 'optional', 'icon' => 'tabler-file-invoice', 'color' => 'success', 'description' => 'Services, invoices, and payments.', 'depends_on' => []],

            // Blood Bank
            ['key' => 'blood_bank', 'name' => 'Blood Bank', 'category' => 'Blood Bank', 'tier' => 'optional', 'icon' => 'tabler-droplet-filled', 'color' => 'danger', 'description' => 'Blood donor and inventory management.', 'depends_on' => ['ward']],

            // HR
            ['key' => 'hr', 'name' => 'HR & Attendance', 'category' => 'HR', 'tier' => 'core', 'icon' => 'tabler-clock-check', 'color' => 'primary', 'description' => 'Staff attendance and leave management.', 'depends_on' => []],

            // Reports
            ['key' => 'reports', 'name' => 'Reports', 'category' => 'Reports', 'tier' => 'optional', 'icon' => 'tabler-chart-bar', 'color' => 'primary', 'description' => 'Patient, billing, and inventory reports.', 'depends_on' => []],

            // Analytics
            ['key' => 'analytics', 'name' => 'Platform Analytics', 'category' => 'Analytics', 'tier' => 'core', 'icon' => 'tabler-chart-infographic', 'color' => 'primary', 'description' => 'Cross-tenant SaaS analytics for the platform owner.', 'depends_on' => []],

            // Support
            ['key' => 'support', 'name' => 'Support Centre', 'category' => 'Support', 'tier' => 'core', 'icon' => 'tabler-headset', 'color' => 'primary', 'description' => 'Support ticketing between hospitals and the platform owner.', 'depends_on' => []],

            // Administration
            ['key' => 'administration', 'name' => 'Platform Administration', 'category' => 'Administration', 'tier' => 'core', 'icon' => 'tabler-settings-cog', 'color' => 'primary', 'description' => 'Platform-wide configuration, branding, and security settings.', 'depends_on' => []],
            ['key' => 'notifications', 'name' => 'Notification Centre', 'category' => 'Administration', 'tier' => 'core', 'icon' => 'tabler-bell-ringing', 'color' => 'primary', 'description' => 'In-app super admin alerts.', 'depends_on' => []],
            ['key' => 'maintenance', 'name' => 'Platform Maintenance', 'category' => 'Administration', 'tier' => 'core', 'icon' => 'tabler-server-cog', 'color' => 'primary', 'description' => 'System health, cache, queue, and log management.', 'depends_on' => []],
            ['key' => 'audit', 'name' => 'Audit & Activity Centre', 'category' => 'Administration', 'tier' => 'core', 'icon' => 'tabler-shield-check', 'color' => 'primary', 'description' => 'Platform-wide activity and security audit trail.', 'depends_on' => []],

            // Integrations
            ['key' => 'email_integration', 'name' => 'Email Notifications', 'category' => 'Integrations', 'tier' => 'core', 'icon' => 'tabler-mail', 'color' => 'primary', 'description' => 'Outbound email delivery for platform and patient notifications.', 'depends_on' => []],
        ];

        foreach ($modules as $index => $module) {
            PlatformModule::updateOrCreate(
                ['key' => $module['key']],
                array_merge($module, ['sort_order' => $index, 'visibility' => 'visible'])
            );
        }
    }
}
