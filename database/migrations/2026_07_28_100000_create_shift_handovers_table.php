<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header record for a nurse-to-nurse shift handover — one per ward per
     * shift change. Patient-level detail lives in shift_handover_patients,
     * checklist items in shift_handover_tasks.
     */
    public function up(): void
    {
        Schema::create('shift_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_no')->unique();
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('current_shift', ['morning', 'evening', 'night']);
            $table->enum('next_shift', ['morning', 'evening', 'night']);
            $table->date('handover_date');
            $table->foreignId('outgoing_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('incoming_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'pending_acceptance', 'accepted', 'rejected'])->default('draft');
            $table->text('shift_summary')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'incoming_nurse_id']);
            $table->index(['status', 'outgoing_nurse_id']);
            $table->index('handover_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handovers');
    }
};
