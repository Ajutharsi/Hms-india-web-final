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
        Schema::create('hospital_settings', function (Blueprint $table) {
             $table->id();
        $table->string('hospital_name');
        $table->string('hospital_code')->nullable();
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('country')->default('Sri Lanka');
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('website')->nullable();
        $table->string('logo')->nullable();
        $table->string('registration_number')->nullable();
        $table->string('tax_number')->nullable();
        $table->string('currency')->default('LKR');
        $table->string('timezone')->default('Asia/Colombo');
        $table->string('date_format')->default('d M Y');
        $table->string('mrn_prefix')->default('HMS');
        $table->string('doctor_id_prefix')->default('DR');
        $table->string('employee_id_prefix')->default('EMP');
        $table->unsignedInteger('low_stock_threshold')->default(10);
        $table->unsignedInteger('near_expiry_days')->default(30);
        $table->unsignedInteger('round_alert_hours')->default(1);
        $table->boolean('auto_patient_login')->default(true);
        $table->boolean('auto_doctor_login')->default(true);
        $table->json('features_enabled')->nullable();
        $table->text('footer_note')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_settings');
    }
};
