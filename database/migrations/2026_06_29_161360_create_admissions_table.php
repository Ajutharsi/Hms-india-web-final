<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    if (!Schema::hasTable('admissions')) {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('admission_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('ward_id');
            $table->unsignedBigInteger('bed_id');
            $table->unsignedBigInteger('doctor_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('ward_id')->references('id')->on('wards')->onDelete('cascade');
            $table->foreign('bed_id')->references('id')->on('beds')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->date('admission_date');
            $table->date('discharge_date')->nullable();
            $table->enum('status', ['admitted', 'discharged', 'transferred'])->default('admitted');
            $table->text('admission_reason')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->decimal('total_charges', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};