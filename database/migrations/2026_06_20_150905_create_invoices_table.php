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
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_no')->unique();
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
        $table->unsignedBigInteger('admission_id')->nullable();
        $table->date('invoice_date');
        $table->date('due_date')->nullable();
        $table->decimal('subtotal', 10, 2)->default(0);
        $table->decimal('discount', 10, 2)->default(0);
        $table->decimal('tax', 10, 2)->default(0);
        $table->decimal('total', 10, 2)->default(0);
        $table->decimal('paid_amount', 10, 2)->default(0);
        $table->decimal('balance', 10, 2)->default(0);
        $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
        $table->softDeletes();

        $table->index(['status', 'created_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('invoices');
}

};
