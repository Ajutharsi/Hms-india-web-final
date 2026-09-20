<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super Admin Global Settings — see SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
     * A singleton row (mirrors `hospital_settings`'s own "always just
     * `first()`" convention) holding platform-owner-only configuration,
     * deliberately separate from `hospital_settings` (which stays this
     * installation's operational/clinical settings, per
     * SUPER_ADMIN_MASTER_PLAN.md §4.3 — the two must not be conflated).
     * Feature toggles are NOT duplicated here — those still live on
     * `hospital_settings.features_enabled` and this module reuses that
     * column directly.
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();

            // Platform Settings
            $table->string('platform_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->text('platform_description')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('copyright_text')->nullable();

            // Regional Settings
            $table->string('default_country')->nullable();
            $table->string('default_currency', 10)->nullable();
            $table->string('default_timezone')->nullable();
            $table->string('default_language', 10)->nullable();
            $table->string('default_date_format', 30)->nullable();
            $table->string('default_time_format', 10)->nullable();

            // Authentication & Security
            $table->unsignedTinyInteger('password_min_length')->default(8);
            $table->boolean('password_require_uppercase')->default(false);
            $table->boolean('password_require_number')->default(false);
            $table->boolean('password_require_symbol')->default(false);
            $table->unsignedInteger('session_timeout_minutes')->default(120);
            $table->unsignedInteger('remember_me_days')->default(400);
            $table->unsignedTinyInteger('max_login_attempts')->default(5);
            $table->unsignedTinyInteger('lockout_duration_minutes')->default(1);
            $table->boolean('two_factor_enabled')->default(false);

            // Email Settings — overrides config('mail.*') when present;
            // falls back to .env/config when null (same `?:` idiom
            // HospitalSetting::applyToConfig() already uses).
            $table->string('mail_mailer')->nullable();
            $table->string('mail_host')->nullable();
            $table->unsignedInteger('mail_port')->nullable();
            $table->string('mail_encryption')->nullable();
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable(); // encrypted cast — see PlatformSetting model
            $table->string('mail_from_name')->nullable();
            $table->string('mail_from_address')->nullable();

            // File & Storage Settings
            $table->unsignedInteger('max_upload_size_mb')->default(10);
            $table->string('allowed_file_types')->default('jpg,jpeg,png,pdf,doc,docx,xlsx,csv');

            // Maintenance
            $table->text('maintenance_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
