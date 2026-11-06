<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One month's pay for one member of staff.
 *
 * Net pay is stored rather than worked out on read: once a payslip has
 * been issued it must keep saying what it said, even if the allowance
 * rules change next month.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // YYYY-MM: a payroll run is a month, not a date range.
            $table->string('period', 7);

            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);

            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->date('paid_on')->nullable();
            $table->string('payment_mode', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
