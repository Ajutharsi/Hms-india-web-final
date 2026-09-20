<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE (post-consolidation): the `inventory_item_id` column itself has been
     * folded directly into 2026_06_18_110821_create_drugs_table.php as part of
     * migration consolidation. This file is intentionally kept (not deleted) to
     * add ONLY the foreign key, because `drugs` is created before
     * `inventory_items` exists (2026_06_18 vs 2026_07_14), so the FK cannot
     * be declared inline in the base create migration without breaking a fresh
     * install. See MIGRATION_MERGE_REPORT.md for details.
     */
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
        });
    }
};
