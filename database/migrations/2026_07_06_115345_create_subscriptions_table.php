<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('hospital_name');
            $table->string('hospital_email')->nullable();
            $table->string('hospital_phone')->nullable();
            $table->enum('plan', ['free_trial', 'basic', 'pro', 'custom'])->default('free_trial');
            $table->json('allowed_modules')->nullable();
            $table->json('module_pricing')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->string('currency', 10)->default('LKR');
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
