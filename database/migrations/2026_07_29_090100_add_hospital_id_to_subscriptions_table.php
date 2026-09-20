<?php

use App\Models\Hospital;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reconciles the previously-disconnected Hospital (tenant registry) and
     * Subscription (global singleton) concepts by giving every subscription
     * a real owning hospital. See SUPER_ADMIN_MASTER_PLAN.md §4.1/§7.4.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->after('id')
                ->constrained()->restrictOnDelete();
            // Added here (rather than in the later
            // add_plan_management_fields_to_subscriptions_table migration
            // where the rest of the Plan Management redesign's columns
            // live) for the same reason documented in
            // add_is_default_to_hospitals_table: the very next lines in
            // this same method query the Subscription Eloquent model, and
            // once SoftDeletes is on that model class, EVERY query against
            // it — from any migration, regardless of dating — carries a
            // "deleted_at IS NULL" scope. deleted_at must exist before the
            // first Subscription:: query ever runs on a fresh install.
            $table->softDeletes();
        });

        // Backfill: match each existing subscription to a hospital by email
        // then name, falling back to the platform's default (self) hospital
        // so no subscription is left orphaned.
        Subscription::whereNull('hospital_id')->get()->each(function (Subscription $subscription) {
            $hospital = null;

            if ($subscription->hospital_email) {
                $hospital = Hospital::where('email', $subscription->hospital_email)->first();
            }
            if (! $hospital && $subscription->hospital_name) {
                $hospital = Hospital::where('name', $subscription->hospital_name)->first();
            }
            $hospital ??= Hospital::firstOrCreateDefault();

            $subscription->forceFill(['hospital_id' => $hospital->id])->saveQuietly();
        });

        if (! Hospital::default()->exists()) {
            Hospital::firstOrCreateDefault();
        }

        DB::statement('ALTER TABLE subscriptions MODIFY hospital_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['hospital_id']);
            $table->dropColumn('hospital_id');
        });
    }
};
