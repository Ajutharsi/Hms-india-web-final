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
    Schema::create('drug_dispensings', function (Blueprint $table) {
        $table->id();
        $table->string('dispensing_no')->unique();
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->onDelete('set null');
        $table->foreignId('dispensed_by')->nullable()->constrained('users')->onDelete('set null');
        $table->date('dispensed_date');
        $table->decimal('total_amount', 10, 2)->default(0);
        $table->enum('status', ['pending', 'dispensed', 'partial'])->default('pending');
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::dropIfExists('drug_dispensings');
}

  
};
