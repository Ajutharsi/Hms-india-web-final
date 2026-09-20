<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_transfusions', function (Blueprint $table) {
            $table->id();
            $table->string('transfusion_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->onDelete('set null');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->unsignedBigInteger('blood_unit_id');
            $table->foreign('blood_unit_id')->references('id')->on('blood_units')->onDelete('cascade');
            $table->unsignedBigInteger('administered_by')->nullable();
            $table->foreign('administered_by')->references('id')->on('users')->onDelete('set null');
            $table->string('indication')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'adverse_reaction'])->default('pending');
            $table->text('reaction_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_transfusions');
    }
};
