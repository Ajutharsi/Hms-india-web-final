<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Super Admin Global Settings — the platform-owner-only singleton
 * configuration row, deliberately separate from `HospitalSetting` (this
 * installation's own operational/clinical settings — see
 * SUPER_ADMIN_MASTER_PLAN.md §4.3). Mirrors `HospitalSetting`'s own
 * cache-then-config-push pattern exactly. See
 * SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'platform_name', 'company_name', 'logo_path', 'favicon_path',
        'platform_description', 'support_email', 'support_phone', 'website', 'copyright_text',
        'default_country', 'default_currency', 'default_timezone', 'default_language',
        'default_date_format', 'default_time_format',
        'password_min_length', 'password_require_uppercase', 'password_require_number', 'password_require_symbol',
        'session_timeout_minutes', 'remember_me_days', 'max_login_attempts', 'lockout_duration_minutes',
        'two_factor_enabled',
        'mail_mailer', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password',
        'mail_from_name', 'mail_from_address',
        'max_upload_size_mb', 'allowed_file_types',
        'maintenance_message',
    ];

    /** Never accidentally serialized into a JSON response / array dump. */
    protected $hidden = ['mail_password'];

    protected function casts(): array
    {
        return [
            'password_require_uppercase' => 'boolean',
            'password_require_number'    => 'boolean',
            'password_require_symbol'    => 'boolean',
            'two_factor_enabled'         => 'boolean',
            'password_min_length'        => 'integer',
            'session_timeout_minutes'    => 'integer',
            'remember_me_days'           => 'integer',
            'max_login_attempts'         => 'integer',
            'lockout_duration_minutes'   => 'integer',
            'mail_port'                  => 'integer',
            'max_upload_size_mb'         => 'integer',
            // Never stored in plaintext, never rendered back into a form —
            // the settings form always shows this field blank; submitting
            // it blank leaves the existing (encrypted) value untouched.
            'mail_password'              => 'encrypted',
        ];
    }

    /** Every genuinely-configurable field's factory default — used by both the singleton fallback and "Reset to Defaults". */
    public static function defaults(): array
    {
        return [
            'platform_name'        => config('app.name', 'HMS'),
            'default_country'      => 'Sri Lanka',
            'default_currency'     => 'LKR',
            'default_timezone'     => 'Asia/Colombo',
            'default_language'     => 'en',
            'default_date_format'  => 'd M Y',
            'default_time_format'  => '24h',
            'password_min_length'         => 8,
            'password_require_uppercase'  => false,
            'password_require_number'     => false,
            'password_require_symbol'     => false,
            'session_timeout_minutes'     => 120,
            'remember_me_days'            => 400,
            'max_login_attempts'          => 5,
            'lockout_duration_minutes'    => 1,
            'two_factor_enabled'          => false,
            'max_upload_size_mb'   => 10,
            'allowed_file_types'   => 'jpg,jpeg,png,pdf,doc,docx,xlsx,csv',
        ];
    }

    // Singleton — always get/create the first (only) record.
    public static function current(): self
    {
        return static::first() ?? static::create(static::defaults());
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/' . $this->logo_path);
        }

        return null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        if ($this->favicon_path && Storage::disk('public')->exists($this->favicon_path)) {
            return asset('storage/' . $this->favicon_path);
        }

        return null;
    }

    protected static function booted(): void
    {
        static::saved(fn ($settings) => static::refreshCache($settings));
        static::deleted(fn () => Cache::forget('platform_settings'));
    }

    /** Re-caches the row and pushes it into config('platform.*') immediately — mirrors HospitalSetting::refreshCache(). */
    public static function refreshCache(?self $settings = null): ?self
    {
        $settings ??= static::first();
        Cache::put('platform_settings', $settings, 300);
        static::applyToConfig($settings);

        return $settings;
    }

    public static function applyToConfig(?self $settings): void
    {
        if (! $settings) {
            return;
        }

        config([
            'platform.name'           => $settings->platform_name ?: config('platform.name'),
            'platform.company_name'   => $settings->company_name ?: config('platform.company_name'),
            'platform.logo_path'      => $settings->logo_path ?: config('platform.logo_path'),
            'platform.favicon_path'   => $settings->favicon_path ?: config('platform.favicon_path'),
            'platform.description'    => $settings->platform_description ?: config('platform.description'),
            'platform.support_email'  => $settings->support_email ?: config('platform.support_email'),
            'platform.support_phone'  => $settings->support_phone ?: config('platform.support_phone'),
            'platform.website'        => $settings->website ?: config('platform.website'),
            'platform.copyright_text' => $settings->copyright_text ?: config('platform.copyright_text'),

            'platform.default_country'     => $settings->default_country ?: config('platform.default_country'),
            'platform.default_currency'    => $settings->default_currency ?: config('platform.default_currency'),
            'platform.default_timezone'    => $settings->default_timezone ?: config('platform.default_timezone'),
            'platform.default_language'    => $settings->default_language ?: config('platform.default_language'),
            'platform.default_date_format' => $settings->default_date_format ?: config('platform.default_date_format'),
            'platform.default_time_format' => $settings->default_time_format ?: config('platform.default_time_format'),

            'platform.password_min_length'        => $settings->password_min_length ?: config('platform.password_min_length'),
            'platform.password_require_uppercase' => $settings->password_require_uppercase ?? config('platform.password_require_uppercase'),
            'platform.password_require_number'    => $settings->password_require_number ?? config('platform.password_require_number'),
            'platform.password_require_symbol'    => $settings->password_require_symbol ?? config('platform.password_require_symbol'),
            'platform.session_timeout_minutes'    => $settings->session_timeout_minutes ?: config('platform.session_timeout_minutes'),
            'platform.remember_me_days'           => $settings->remember_me_days ?: config('platform.remember_me_days'),
            'platform.max_login_attempts'         => $settings->max_login_attempts ?: config('platform.max_login_attempts'),
            'platform.lockout_duration_minutes'   => $settings->lockout_duration_minutes ?: config('platform.lockout_duration_minutes'),
            'platform.two_factor_enabled'         => $settings->two_factor_enabled ?? config('platform.two_factor_enabled'),

            'platform.max_upload_size_mb'  => $settings->max_upload_size_mb ?: config('platform.max_upload_size_mb'),
            'platform.allowed_file_types'  => $settings->allowed_file_types ?: config('platform.allowed_file_types'),

            // Mail — only override config('mail.*') when a value is actually
            // set; otherwise the real .env/config values keep governing mail,
            // exactly like HospitalSetting's own `?:` fallback idiom.
            'mail.default'                    => $settings->mail_mailer ?: config('mail.default'),
            'mail.mailers.smtp.host'          => $settings->mail_host ?: config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port'          => $settings->mail_port ?: config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.encryption'    => $settings->mail_encryption ?: config('mail.mailers.smtp.encryption'),
            'mail.mailers.smtp.username'      => $settings->mail_username ?: config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password'      => $settings->mail_password ?: config('mail.mailers.smtp.password'),
            'mail.from.name'                   => $settings->mail_from_name ?: config('mail.from.name'),
            'mail.from.address'                => $settings->mail_from_address ?: config('mail.from.address'),

            'session.lifetime' => $settings->session_timeout_minutes ?: config('session.lifetime'),
        ]);
    }
}
