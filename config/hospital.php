<?php

return [
    'name'               => 'Hospital Management System',
    'currency'           => 'LKR',
    'date_format'        => 'd M Y',
    'timezone'           => 'Asia/Colombo',
    'mrn_prefix'         => 'HMS',
    'doctor_prefix'      => 'DR',
    'employee_prefix'    => 'EMP',
    'low_stock'          => 10,
    'near_expiry'        => 30,
    // A ticket still open this many hours after creation counts as
    // "overdue" — a disclosed heuristic, not a real SLA engine (see
    // SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md; SLA timers are explicit
    // Phase 3+ scope per SUPER_ADMIN_MASTER_PLAN.md §5.6).
    'ticket_overdue_hours' => 48,
    'round_hours'        => 1,
    'auto_patient_login' => true,
    'auto_doctor_login'  => true,
    'features'           => [
        'lab'            => true,
        'pharmacy'       => true,
        'billing'        => true,
        'ward'           => true,
        'food_intake'    => true,
        'fluid_intake'   => true,
        'staff_rounds'   => true,
        'consent_forms'  => true,
        'referrals'      => true,
        'reports'        => true,
        'patient_portal' => true,
    ],
    'subscription' => [
        'plan'       => 'free_trial',
        'status'     => 'active',
        'expires_at' => null,
        'modules'    => [],
    ],
];
