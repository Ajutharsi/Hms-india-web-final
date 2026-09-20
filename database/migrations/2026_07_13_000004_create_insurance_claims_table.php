<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->string('insurance_company');
            $table->string('tpa_name')->nullable();
            $table->string('policy_number');
            $table->string('member_id')->nullable();
            $table->decimal('claim_amount', 10, 2)->default(0);
            $table->decimal('approved_amount', 10, 2)->default(0);
            $table->decimal('patient_liability', 10, 2)->default(0);
            $table->enum('claim_type', ['cashless', 'reimbursement'])->default('cashless');
            $table->enum('status', ['draft', 'submitted', 'pre_auth_pending', 'pre_auth_approved', 'pre_auth_rejected', 'claim_submitted', 'approved', 'partially_approved', 'rejected', 'settled'])->default('draft');
            $table->date('admission_date')->nullable();
            $table->date('discharge_date')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('icd10_code')->nullable();
            $table->text('treatment_details')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
