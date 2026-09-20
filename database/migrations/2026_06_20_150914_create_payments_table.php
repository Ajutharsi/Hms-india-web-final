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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->string('payment_no')->unique();
        $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->decimal('amount', 10, 2);
        $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'cheque', 'online','insurance','upi'])->default('cash');
        $table->date('payment_date');
        $table->string('reference_no')->nullable();
        $table->text('notes')->nullable();
        $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::dropIfExists('payments');
}

};
