<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super Admin Reports & Export Centre — see SUPER_ADMIN_REPORTS_CENTRE_REPORT.md.
     * Records every CSV/PDF/print generation so the Reports Dashboard's
     * "Recent Exports" / "Most Generated Reports" / "Total Reports"
     * widgets are backed by real history, not fabricated counters. This is
     * additive to (not a replacement for) the generic ActivityLog trail —
     * every export also gets an ActivityLog::log() row for the unified
     * audit trail; this table exists purely because ActivityLog has no
     * structured `report_slug`/`format` columns to efficiently group by.
     */
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->string('report_slug');
            $table->string('report_label');
            $table->string('category', 50);
            $table->enum('format', ['csv', 'pdf', 'print']);
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('report_slug');
            $table->index('category');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
