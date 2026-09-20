<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('nurse_id')->nullable();
            $table->foreign('nurse_id')->references('id')->on('users')->onDelete('set null');
            $table->string('drug_name');
            $table->string('dosage');
            $table->string('route')->nullable(); // oral, IV, IM, SC
            $table->string('frequency')->nullable(); // OD, BD, TDS, QID, PRN
            $table->time('scheduled_time')->nullable();
            $table->timestamp('administered_at')->nullable();
            $table->enum('status', ['scheduled', 'administered', 'missed', 'refused', 'held'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->boolean('is_prn')->default(false); // as needed
            $table->boolean('is_controlled')->default(false); // controlled drug
            $table->timestamps();
            $table->softDeletes();

            $table->index('admission_id');
            $table->index('doctor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_administrations');
    }
};
