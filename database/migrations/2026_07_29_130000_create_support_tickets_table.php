<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super Admin Support Centre — see SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md.
     * Reuses the unified ActivityLog for audit history (no separate audit
     * table) and Hospital's own `code` auto-numbering convention for
     * `ticket_number`.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();

            // Nullable + nullOnDelete: a support ticket is a historical
            // record — if the hospital is later hard-deleted it must
            // survive (just losing the link), the same reasoning already
            // applied to the impersonations table.
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();

            $table->string('subject');
            $table->text('description');

            // Snapshotted at creation (mirrors Subscription's
            // hospital_name/email pattern) — a hospital's contact person
            // can change after the ticket is opened; the ticket should keep
            // recording who was actually contacted at the time.
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('category', 50)->default('other');
            $table->enum('status', ['open', 'assigned', 'in_progress', 'waiting_customer', 'resolved', 'closed'])->default('open');

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // "Archive", never a hard delete — see report.

            $table->index('status');
            $table->index('priority');
            $table->index('hospital_id');
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
