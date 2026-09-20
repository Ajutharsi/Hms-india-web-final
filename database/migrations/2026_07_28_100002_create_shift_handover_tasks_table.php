<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pending-work checklist attached to a handover — ward-wide or, optionally, tied to one patient. */
    public function up(): void
    {
        Schema::create('shift_handover_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_handover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('task_description');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index('shift_handover_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handover_tasks');
    }
};
