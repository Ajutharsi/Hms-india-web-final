<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks which single Hospital row represents this application instance's
     * own operating tenant. The app is still single-tenant at the data layer
     * (see SUPER_ADMIN_MASTER_PLAN.md §4.2), so exactly one Hospital is
     * flagged "default" and its Subscription is what actually gates the app
     * — replacing the old "whichever Subscription row was touched most
     * recently" resolution.
     */
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('status');
            // Added here (rather than in the later
            // add_management_fields_to_hospitals_table migration where the
            // rest of the Hospital Management redesign's columns live)
            // because the very next migration
            // (add_hospital_id_to_subscriptions_table) queries the Hospital
            // Eloquent model, and the model's SoftDeletes trait applies a
            // "deleted_at IS NULL" global scope to every query regardless of
            // which migration is "currently" running — deleted_at must exist
            // before any Hospital:: query runs on a fresh install.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('is_default');
        });
    }
};
