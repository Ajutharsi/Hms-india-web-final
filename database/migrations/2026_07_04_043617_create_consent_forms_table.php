<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_forms', function (Blueprint $table) {
            $table->id();
            $table->string('consent_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->enum('consent_type', ['general', 'surgery', 'anesthesia', 'blood_transfusion', 'procedure', 'other'])->default('general');
            $table->string('procedure_name')->nullable();
            $table->text('description');
            $table->string('patient_name');
            $table->string('witness_name')->nullable();
            $table->string('doctor_name')->nullable();
            $table->date('consent_date');
            $table->text('patient_signature')->nullable();
            $table->text('witness_signature')->nullable();
            $table->enum('status', ['pending', 'signed', 'declined'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_forms');
    }
};