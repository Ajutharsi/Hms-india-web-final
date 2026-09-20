<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('blood_group')->nullable();
            $table->string('national_id')->nullable();
            $table->text('address')->nullable();
            $table->enum('pay_type', ['hourly', 'daily', 'monthly'])->default('monthly');
            $table->decimal('pay_rate', 10, 2)->default(0);
            $table->decimal('allowance_transport', 10, 2)->default(0);
            $table->decimal('allowance_meal', 10, 2)->default(0);
            $table->decimal('allowance_housing', 10, 2)->default(0);
            $table->string('currency', 10)->default('LKR');
            $table->enum('employment_status', ['active', 'on_leave', 'resigned', 'terminated'])->default('active');
            $table->integer('leave_balance')->default(14);
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('nok_relationship')->nullable();
            $table->enum('shift', ['morning', 'evening', 'night'])->nullable();
            $table->string('qualification')->nullable();
            $table->string('license_no')->nullable();
            $table->integer('experience_years')->default(0);
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('doc_license')->nullable();
            $table->string('doc_certificate')->nullable();
            $table->string('doc_national_id')->nullable();
            $table->string('doc_other')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
