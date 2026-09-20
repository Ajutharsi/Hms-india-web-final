<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->string('blood_pressure')->nullable(); // e.g. 120/80
            $table->decimal('temperature', 4, 1)->nullable(); // celsius
            $table->integer('pulse_rate')->nullable(); // bpm
            $table->integer('respiratory_rate')->nullable(); // breaths/min
            $table->integer('spo2')->nullable(); // %
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->integer('news_score')->nullable(); // NEWS/MEWS score
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('admission_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
