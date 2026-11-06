<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The step between ordering a test and having a result: somebody has to
 * take the sample.
 *
 * Until now an order went straight from "pending" to a typed-in result,
 * so nobody could tell an order waiting for blood to be drawn from one
 * sitting in the analyser, and a mislabelled tube had nothing to trace.
 * The barcode is what goes on the tube.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->string('sample_barcode', 40)->nullable()->unique()->after('status');
            $table->string('sample_type', 60)->nullable()->after('sample_barcode');
            $table->timestamp('sample_collected_at')->nullable()->after('sample_type');
            $table->foreignId('sample_collected_by')->nullable()->after('sample_collected_at')
                ->constrained('users')->nullOnDelete();
            $table->text('sample_notes')->nullable()->after('sample_collected_by');
        });
    }

    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sample_collected_by');
            $table->dropColumn(['sample_barcode', 'sample_type', 'sample_collected_at', 'sample_notes']);
        });
    }
};
