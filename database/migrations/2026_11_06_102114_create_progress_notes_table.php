<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the doctor writes on the ward round.
 *
 * Nurses had nursing notes and consultations covered the clinic, but a
 * daily round on an admitted patient had nowhere to go -- the note that
 * says how yesterday's plan worked and what today's is.
 *
 * The four SOAP fields are separate columns rather than one blob so the
 * assessment and the plan can be read on their own, which is what the
 * next person on the round actually wants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('note_type', ['round', 'progress', 'handover'])->default('round');
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();

            $table->timestamp('noted_at');
            $table->timestamps();

            $table->index(['admission_id', 'noted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
    }
};
