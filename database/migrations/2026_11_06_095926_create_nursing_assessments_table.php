<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The four scores a nurse records at the bedside.
 *
 * Vitals and nursing notes were already here; the scored assessments --
 * fall risk, pain, pressure-sore risk and the early-warning score --
 * had nowhere to go, so they were being written into free-text notes
 * where nothing can trend them or alert on them.
 *
 * Scores are stored as the numbers the scales produce. The bands
 * ("high risk") are worked out when the row is read, so changing where
 * a threshold sits does not need the stored rows rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at');

            // Morse fall scale, 0-125.
            $table->unsignedSmallInteger('fall_risk_score')->nullable();
            // Numeric pain rating, 0-10.
            $table->unsignedTinyInteger('pain_score')->nullable();
            // Braden scale, 6-23; lower is worse, unlike the others.
            $table->unsignedTinyInteger('braden_score')->nullable();
            // National early warning score, 0-20.
            $table->unsignedTinyInteger('ews_score')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_assessments');
    }
};
