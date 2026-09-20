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
    Schema::create('lab_orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_no')->unique();
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
        $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
        $table->date('order_date');
        $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index(['status', 'doctor_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('lab_orders');
}

};
