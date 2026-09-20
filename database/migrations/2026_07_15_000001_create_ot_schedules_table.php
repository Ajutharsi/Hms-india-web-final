<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('ot_no')->unique(); // OT-YYYY-XXXX
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->onDelete('set null');
            $table->foreignId('consent_form_id')->nullable()->constrained('consent_forms')->onDelete('set null');
            $table->unsignedBigInteger('surgeon_id')->nullable();
            $table->foreign('surgeon_id')->references('id')->on('doctors')->onDelete('set null');
            $table->unsignedBigInteger('anaesthetist_id')->nullable();
            $table->foreign('anaesthetist_id')->references('id')->on('doctors')->onDelete('set null');
            $table->unsignedBigInteger('scrub_nurse_id')->nullable();
            $table->foreign('scrub_nurse_id')->references('id')->on('users')->onDelete('set null');
            $table->string('theatre_no')->nullable(); // OT-1, OT-2
            $table->string('procedure_name');
            $table->string('icd10_code')->nullable();
            $table->enum('anaesthesia_type', ['general', 'spinal', 'epidural', 'local', 'sedation'])->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->enum('priority', ['elective', 'urgent', 'emergency'])->default('elective');
            $table->datetime('scheduled_start');
            $table->datetime('scheduled_end')->nullable();
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->text('pre_op_notes')->nullable();
            $table->text('post_op_notes')->nullable();
            $table->text('complications')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_schedules');
    }
};
