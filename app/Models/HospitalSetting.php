<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HospitalSetting extends Model
{

    protected $fillable = [
        'hospital_id', 'hospital_name', 'hospital_code', 'address',
        'city', 'country', 'phone', 'email', 'website',
        'logo', 'registration_number', 'tax_number',
        'currency', 'timezone', 'footer_note',
        'date_format', 'mrn_prefix', 'doctor_id_prefix', 'employee_id_prefix',
        'low_stock_threshold', 'near_expiry_days', 'round_alert_hours',
        'auto_patient_login', 'auto_doctor_login', 'features_enabled',
    ];

    protected function casts(): array
    {
        return [
            'auto_patient_login'  => 'boolean',
            'auto_doctor_login'   => 'boolean',
            'low_stock_threshold' => 'integer',
            'near_expiry_days'    => 'integer',
            'round_alert_hours'   => 'integer',
            'features_enabled'    => 'array',
        ];
    }

    const DEFAULT_FEATURES = [
        'lab'            => true,
        'pharmacy'       => true,
        'billing'        => true,
        'food_intake'    => true,
        'fluid_intake'   => true,
        'staff_rounds'   => true,
        'consent_forms'  => true,
        'referrals'      => true,
        'patient_portal' => true,
        'ward'           => true,
        'reports'        => true,
    ];

    // Singleton — always get the global (hospital_id IS NULL) record. Explicit
    // whereNull() rather than a bare first(), because since row-level feature
    // overrides (see SUPER_ADMIN_FEATURE_MANAGEMENT_MULTI_TENANCY_REPORT.md)
    // this table can now hold additional per-hospital rows too — first()
    // alone could return an arbitrary one of those instead of the true global
    // default depending on insertion order.
    public static function get()
    {
        return static::whereNull('hospital_id')->first() ?? new static([
            'hospital_name'       => 'Hospital MS',
            'currency'            => 'LKR',
            'country'             => 'Sri Lanka',
            'timezone'            => 'Asia/Colombo',
            'date_format'         => 'd M Y',
            'mrn_prefix'          => 'HMS',
            'doctor_id_prefix'    => 'DR',
            'employee_id_prefix'  => 'EMP',
            'low_stock_threshold' => 10,
            'near_expiry_days'    => 30,
            'round_alert_hours'   => 1,
            'auto_patient_login'  => true,
            'auto_doctor_login'   => true,
            'features_enabled'    => self::DEFAULT_FEATURES,
        ]);
    }

    /**
     * The settings row for a specific hospital, if a per-hospital override
     * has ever been saved for it — otherwise an unsaved instance seeded from
     * the global default's `features_enabled`, so a hospital with no
     * explicit override yet still inherits the platform's current toggle
     * state rather than an arbitrary blank slate. Never eagerly creates a
     * row — see the migration's own doc comment for why.
     */
    public static function forHospital(?int $hospitalId): self
    {
        if (! $hospitalId) {
            return static::get();
        }

        return static::where('hospital_id', $hospitalId)->first() ?? new static([
            'hospital_id'      => $hospitalId,
            'features_enabled' => static::get()->features_enabled ?? self::DEFAULT_FEATURES,
        ]);
    }

    public function isFeatureEnabled(string $key): bool
    {
        $features = $this->features_enabled ?? [];

        return (bool) ($features[$key] ?? true);
    }

    /**
     * Resolves the hospital logo only if the row has a path AND the file still
     * exists on the storage disk — mirrors Patient/Doctor::getPhotoUrlAttribute().
     * Views previously checked only `$settings->logo` (a non-empty DB value),
     * which rendered a broken <img> if the file had been deleted/moved.
     * Returns null (not a default asset — no generic hospital-logo placeholder
     * exists in this app) so callers keep using their existing `@if(...)` guard.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }

    protected static function booted(): void
    {
        // Only the global row (hospital_id IS NULL) may refresh the shared
        // cache/config — a per-hospital override row being saved must never
        // clobber every other request's global settings with one hospital's
        // values. See SUPER_ADMIN_FEATURE_MANAGEMENT_MULTI_TENANCY_REPORT.md.
        static::saved(function ($settings) {
            if ($settings->hospital_id === null) {
                static::refreshCache($settings);
            }
        });
        static::deleted(function ($settings) {
            if ($settings->hospital_id === null) {
                Cache::forget('hospital_settings');
            }
        });
    }

    // Re-caches the settings row and pushes it into config('hospital.*') immediately,
    // so every read path (config() calls, cached view data) reflects a save the moment
    // it happens instead of waiting for the next request's AppServiceProvider boot.
    public static function refreshCache(?self $settings = null): ?self
    {
        $settings ??= static::whereNull('hospital_id')->first();
        Cache::put('hospital_settings', $settings, 300);
        static::applyToConfig($settings);

        return $settings;
    }

    public static function applyToConfig(?self $settings): void
    {
        if (! $settings) {
            return;
        }

        config([
            'hospital.name'               => $settings->hospital_name ?? config('hospital.name'),
            'hospital.currency'           => $settings->currency ?: config('hospital.currency'),
            'hospital.date_format'        => $settings->date_format ?: config('hospital.date_format'),
            'hospital.timezone'           => $settings->timezone ?: config('hospital.timezone'),
            'hospital.mrn_prefix'         => $settings->mrn_prefix ?: config('hospital.mrn_prefix'),
            'hospital.doctor_prefix'      => $settings->doctor_id_prefix ?: config('hospital.doctor_prefix'),
            'hospital.employee_prefix'    => $settings->employee_id_prefix ?: config('hospital.employee_prefix'),
            'hospital.low_stock'          => $settings->low_stock_threshold ?: config('hospital.low_stock'),
            'hospital.near_expiry'        => $settings->near_expiry_days ?: config('hospital.near_expiry'),
            'hospital.round_hours'        => $settings->round_alert_hours ?: config('hospital.round_hours'),
            'hospital.auto_patient_login' => $settings->auto_patient_login ?? config('hospital.auto_patient_login'),
            'hospital.auto_doctor_login'  => $settings->auto_doctor_login ?? config('hospital.auto_doctor_login'),
            'hospital.features'           => $settings->features_enabled ?: config('hospital.features'),
        ]);
    }
}
