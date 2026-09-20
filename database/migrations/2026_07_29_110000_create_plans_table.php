<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Database-backed plan catalog, replacing the hardcoded
     * Subscription::PLAN_MODULES/PLAN_PRICING constants as the source of
     * truth for NEW subscriptions (see SUPER_ADMIN_MASTER_PLAN.md §5.3/§7.1).
     * Legacy subscriptions with no linked plan keep working off the old
     * constants — see Subscription::getAllowedModules()/calculateTotal().
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->unsignedInteger('trial_days')->nullable();
            $table->unsignedInteger('user_limit')->nullable();
            $table->unsignedInteger('doctor_limit')->nullable();
            $table->unsignedInteger('patient_limit')->nullable();
            $table->unsignedInteger('storage_limit_mb')->nullable();
            $table->json('modules')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
