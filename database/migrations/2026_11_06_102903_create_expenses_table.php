<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money going out.
 *
 * The system tracked every rupee coming in -- invoices, payments,
 * deposits -- and nothing going out, so "what did we make today" could
 * never be answered. This is the other half, and what day-end closing
 * subtracts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('category', 60);
            $table->string('paid_to')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['cash', 'card', 'upi', 'bank_transfer', 'cheque'])->default('cash');
            $table->string('reference_no', 60)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
