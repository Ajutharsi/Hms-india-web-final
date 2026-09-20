<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform-level activity feed for the Super Admin dashboard (hospital
     * registered / activated / suspended / plan changed) — separate from the
     * per-hospital ActivityLog, which tracks in-app clinical actions.
     */
    public function up(): void
    {
        Schema::create('hospital_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('description');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_activity_logs');
    }
};
