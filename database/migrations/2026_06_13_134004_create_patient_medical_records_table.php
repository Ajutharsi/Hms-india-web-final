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
    Schema::create('patient_medical_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->string('record_type'); 
        $table->date('record_date');
        $table->text('description');
        $table->string('doctor_name')->nullable();
        $table->text('notes')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();
        $table->string('reference_type')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_records');
    }
};
