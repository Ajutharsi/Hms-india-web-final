<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('deposit_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->foreign('admission_id')->references('id')->on('admissions')->onDelete('set null');
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->foreign('collected_by')->references('id')->on('users')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['advance', 'refund', 'adjustment'])->default('advance');
            $table->enum('payment_method', ['cash', 'card', 'upi', 'bank_transfer', 'cheque'])->default('cash');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('deposited_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_deposits');
    }
};
