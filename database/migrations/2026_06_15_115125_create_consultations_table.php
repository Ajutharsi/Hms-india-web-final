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
    Schema::create('consultations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
        // Vitals
        $table->string('blood_pressure')->nullable();
        $table->string('temperature')->nullable();
        $table->string('pulse_rate')->nullable();
        $table->string('weight')->nullable();
        $table->string('height')->nullable();
        $table->string('spo2')->nullable();
        // Notes
        $table->text('chief_complaint')->nullable();
        $table->text('diagnosis')->nullable();
        $table->string('icd10_code')->nullable();
        $table->text('treatment_plan')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
