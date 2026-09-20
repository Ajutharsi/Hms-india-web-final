<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supports the Hospital Management redesign (SUPER_ADMIN_MASTER_PLAN.md
     * §5.2): a logo + named contact person for the tenant profile.
     * Soft-delete-backed archiving (deleted_at) lives in the earlier
     * add_is_default_to_hospitals_table migration — see the comment there.
     */
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('code');
            $table->string('contact_person')->nullable()->after('name');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropColumn(['logo_path', 'contact_person']);
        });
    }
};
