<?php

// Platform-owner-only configuration, overlaid at runtime from the
// `platform_settings` singleton row — see App\Models\PlatformSetting and
// SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md. Mirrors config/hospital.php's own
// "static defaults here, DB overlay at boot" convention exactly.

return [
    'name'                  => env('APP_NAME', 'HMS'),
    'company_name'          => null,
    'logo_path'             => null,
    'favicon_path'          => null,
    'description'           => null,
    'support_email'         => null,
    'support_phone'         => null,
    'website'               => null,
    'copyright_text'        => null,

    'default_country'       => 'Sri Lanka',
    'default_currency'      => 'LKR',
    'default_timezone'      => 'Asia/Colombo',
    'default_language'      => 'en',
    'default_date_format'   => 'd M Y',
    'default_time_format'   => '24h',

    'password_min_length'         => 8,
    'password_require_uppercase'  => false,
    'password_require_number'     => false,
    'password_require_symbol'     => false,
    'session_timeout_minutes'     => 120,
    'remember_me_days'            => 400,
    'max_login_attempts'          => 5,
    'lockout_duration_minutes'    => 1,
    'two_factor_enabled'          => false,

    'max_upload_size_mb'    => 10,
    'allowed_file_types'   => 'jpg,jpeg,png,pdf,doc,docx,xlsx,csv',
];
