<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per admitted patient carried on a given shift handover. Photo,
     * name, MRN, bed, ward and current vitals are deliberately NOT duplicated
     * here — they're read live from the patient_id/admission_id relationships
     * (see ShiftHandoverPatient model). Only the outgoing nurse's end-of-shift
     * assessment for this patient is stored.
     */
    public function up(): void
    {
        Schema::create('shift_handover_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_handover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('condition', ['stable', 'improving', 'critical', 'observation'])->default('stable');
            $table->enum('medication_status', ['completed', 'pending', 'missed'])->default('pending');
            $table->enum('iv_fluid_status', ['running', 'completed'])->nullable();
            $table->dateTime('iv_next_change_time')->nullable();
            $table->text('doctor_instructions')->nullable();
            $table->text('pending_procedures')->nullable();
            $table->text('pending_lab_reports')->nullable();
            $table->text('pending_imaging')->nullable();
            $table->text('special_notes')->nullable();
            $table->boolean('is_high_priority')->default(false);
            $table->timestamps();

            $table->index('shift_handover_id');
            $table->index('is_high_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handover_patients');
    }
};
