<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes columns that are filtered/sorted on directly by
     * HospitalController, SubscriptionController, CheckSubscription
     * middleware, and the unified ActivityLog but were never indexed.
     * See SUPER_ADMIN_MODULE_ANALYSIS.md §15, SUPER_ADMIN_MASTER_PLAN.md §7.7.
     */
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->index('status');
            $table->index('plan');
            $table->index('subscription_ends_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('status');
            $table->index('expires_at');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['plan']);
            $table->dropIndex(['subscription_ends_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['model_type', 'model_id']);
        });
    }
};
