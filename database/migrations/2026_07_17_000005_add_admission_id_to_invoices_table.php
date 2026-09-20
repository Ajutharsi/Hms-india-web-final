<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE (post-consolidation): the `admission_id` column itself has been
     * folded directly into 2026_06_20_150905_create_invoices_table.php as part
     * of migration consolidation. This file is intentionally kept (not deleted)
     * to add ONLY the foreign key, because `invoices` is created before
     * `admissions` exists (2026_06_20 vs 2026_06_29), so the FK cannot be
     * declared inline in the base create migration without breaking a fresh
     * install. See MIGRATION_MERGE_REPORT.md for details.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('admission_id')->references('id')->on('admissions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['admission_id']);
        });
    }
};
