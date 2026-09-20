<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE (post-consolidation): all columns this migration used to add to `users`
     * (employee_id, department_id, joining_date, dob, gender, address, pay_type,
     * pay_rate, currency, employment_status, emergency_contact_name,
     * emergency_contact_phone, profile_photo) have been folded directly into
     * 0001_01_01_000000_create_users_table.php as part of migration consolidation.
     * This file is intentionally kept (not deleted) to add ONLY the department_id
     * foreign key, because `users` is created before `departments` exists
     * (0001_01_01_000000 vs 2026_06_27_055610), so the FK cannot be declared
     * inline in the base create migration without breaking a fresh install.
     * See MIGRATION_MERGE_REPORT.md for details.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
    }
};