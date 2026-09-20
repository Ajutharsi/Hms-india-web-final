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
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();
        $table->string('appointment_id')->unique();
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
        $table->string('department')->nullable();
        $table->enum('type', ['op', 'emergency', 'consult', 'followup'])->default('op');
        $table->date('appointment_date');
        $table->time('appointment_time');
        $table->integer('token_number')->nullable();
        $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
        $table->text('notes')->nullable();
        $table->string('booked_by')->default('reception');
        $table->timestamps();
        $table->softDeletes();

        $table->index(['appointment_date', 'status']);
        $table->index(['doctor_id', 'appointment_date']);
    });
}

public function down(): void
{
    Schema::dropIfExists('appointments');
}

};
