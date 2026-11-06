<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The end of a day's cash.
 *
 * Collections and expenses are counted from rows that already exist;
 * what cannot be computed is the cash actually in the drawer. The gap
 * between the two is the whole point of closing a day, so both numbers
 * are stored as they stood that night rather than recomputed later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_end_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique();

            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('collections', 12, 2)->default(0);
            $table->decimal('cash_collections', 12, 2)->default(0);
            $table->decimal('expenses', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('counted_cash', 12, 2)->default(0);
            $table->decimal('variance', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_end_closings');
    }
};
