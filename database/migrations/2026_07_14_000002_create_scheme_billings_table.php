<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_billings', function (Blueprint $table) {
            $table->id();
            $table->string('scheme_bill_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->enum('scheme', ['pmjay', 'ayushman', 'cghs', 'esi', 'state_scheme', 'other'])->default('pmjay');
            $table->string('scheme_name')->nullable(); // custom scheme name
            $table->string('beneficiary_id')->nullable(); // PMJAY ID / ABHA
            $table->string('package_code')->nullable();
            $table->string('package_name')->nullable();
            $table->string('icd10_code')->nullable();
            $table->decimal('package_rate', 10, 2)->default(0);
            $table->decimal('actual_bill', 10, 2)->default(0);
            $table->decimal('scheme_payment', 10, 2)->default(0);
            $table->decimal('patient_liability', 10, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'settled'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->date('submission_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_billings');
    }
};
