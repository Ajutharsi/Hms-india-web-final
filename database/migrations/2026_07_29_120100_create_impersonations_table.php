<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structured session-lifecycle record for Super Admin impersonation —
     * distinct from the generic ActivityLog (which also gets a
     * start/end entry for cross-module visibility, e.g. on the Hospital
     * detail page's audit timeline) because a start/end pair with a real
     * duration doesn't fit ActivityLog's flat single-event shape cleanly.
     * See SUPER_ADMIN_IMPERSONATION_REPORT.md.
     */
    public function up(): void
    {
        Schema::create('impersonations', function (Blueprint $table) {
            $table->id();
            // Nullable + nullOnDelete (not restrictOnDelete) because these
            // are historical audit records — if the referenced hospital or
            // user is later hard-deleted, the audit row must survive (just
            // losing the specific link), not block the deletion or vanish.
            $table->foreignId('super_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('impersonated_user_id')->nullable()->constrained('users')->nullOnDelete();
            // dateTime, not timestamp: MySQL's TIMESTAMP column type converts
            // on write/read based on the session's `time_zone` (this
            // environment's is SYSTEM, not UTC, unlike config('app.timezone')
            // which PHP's now() uses) — that mismatch produced ended_at values
            // earlier than started_at. DATETIME stores/returns the naive
            // value PHP sent with no server-side conversion.
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonations');
    }
};
