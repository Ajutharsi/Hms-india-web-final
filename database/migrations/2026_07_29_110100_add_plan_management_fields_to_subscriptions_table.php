<?php

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links subscriptions to the new plans catalog and adds the fields the
     * Subscription Management redesign needs (SUPER_ADMIN_SUBSCRIPTION_MANAGEMENT_REPORT.md):
     * payment_status (manually-tracked, since no payment gateway/ledger
     * exists), last_renewed_at (distinct from expires_at — see report §2),
     * cancelled_at, and soft-delete-backed archiving (consistent with the
     * Hospital Management module).
     */
    public function up(): void
    {
        // Widened (not narrowed) — existing enum values remain valid, and
        // this lets subscriptions reference newly-created plans with
        // arbitrary keys instead of being locked to the original 4.
        DB::statement("ALTER TABLE subscriptions MODIFY plan VARCHAR(50) NOT NULL DEFAULT 'free_trial'");

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan')->constrained()->nullOnDelete();
            $table->enum('payment_status', ['unpaid', 'paid', 'overdue'])->default('unpaid')->after('status');
            $table->timestamp('last_renewed_at')->nullable()->after('expires_at');
            $table->timestamp('cancelled_at')->nullable()->after('last_renewed_at');
        });

        // Seed the 4 legacy plans as real catalog rows and backfill plan_id
        // on every existing subscription by matching its free-text `plan`
        // string to the new plan's `key` — no subscription is left unlinked.
        $legacy = [
            'free_trial' => ['name' => 'Free Trial', 'price' => 0, 'trial_days' => 14],
            'basic'      => ['name' => 'Basic', 'price' => 1200],
            'pro'        => ['name' => 'Pro', 'price' => 4700],
            'custom'     => ['name' => 'Custom', 'price' => 0],
        ];

        $moduleDefaults = Subscription::DEFAULT_MODULE_PRICING;
        $planModules = Subscription::PLAN_MODULES;

        foreach ($legacy as $key => $attrs) {
            Plan::firstOrCreate(['key' => $key], [
                'name'        => $attrs['name'],
                'description' => "Migrated from the legacy hardcoded {$key} plan definition.",
                'price'       => $attrs['price'],
                'billing_cycle' => 'monthly',
                'trial_days'  => $attrs['trial_days'] ?? null,
                'modules'     => $planModules[$key] ?: array_keys($moduleDefaults),
                'status'      => 'active',
            ]);
        }

        $plans = Plan::pluck('id', 'key');
        Subscription::whereNull('plan_id')->get()->each(function (Subscription $subscription) use ($plans) {
            $planId = $plans[$subscription->plan] ?? null;
            if ($planId) {
                $subscription->forceFill(['plan_id' => $planId])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'last_renewed_at', 'cancelled_at']);
            $table->dropConstrainedForeignId('plan_id');
        });

        DB::statement("ALTER TABLE subscriptions MODIFY plan ENUM('free_trial','basic','pro','custom') NOT NULL DEFAULT 'free_trial'");
    }
};
