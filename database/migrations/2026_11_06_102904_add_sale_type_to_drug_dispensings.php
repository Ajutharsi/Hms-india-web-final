<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Counter sales and returns, in the table that already records
 * dispensing.
 *
 * A dispensing row has always meant "medicine left the pharmacy against
 * a prescription". Two other things happen at that counter: a walk-in
 * buys something without one, and somebody brings medicine back. Both
 * move stock, so both belong here rather than in a table of their own.
 * They only need telling apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drug_dispensings', function (Blueprint $table) {
            $table->enum('sale_type', ['prescription', 'otc', 'return'])
                ->default('prescription')
                ->after('prescription_id');
            // A return points at what is being brought back.
            $table->foreignId('returned_from_id')->nullable()->after('sale_type')
                ->constrained('drug_dispensings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drug_dispensings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_from_id');
            $table->dropColumn('sale_type');
        });
    }
};
