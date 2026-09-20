<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('er_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('er_no')->unique(); // ER-YYYY-XXXX
            $table->unsignedBigInteger('patient_id')->nullable(); // can be walk-in
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            $table->string('patient_name'); // for walk-ins
            $table->string('patient_phone')->nullable();
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->unsignedBigInteger('triaged_by')->nullable();
            $table->foreign('triaged_by')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('attending_doctor_id')->nullable();
            $table->foreign('attending_doctor_id')->references('id')->on('doctors')->onDelete('set null');
            $table->enum('triage_level', ['red', 'yellow', 'green', 'black'])->default('yellow');
            // Red=Immediate, Yellow=Urgent, Green=Minor, Black=Deceased/Expectant
            $table->string('chief_complaint');
            $table->string('icd10_code')->nullable();
            $table->text('presenting_symptoms')->nullable();
            $table->string('mode_of_arrival')->nullable(); // walk-in, ambulance, referred
            $table->enum('status', ['waiting', 'under_treatment', 'admitted', 'discharged', 'transferred', 'expired'])->default('waiting');
            $table->timestamp('arrival_time');
            $table->timestamp('triage_time')->nullable();
            $table->timestamp('discharge_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('er_registrations');
    }
};
