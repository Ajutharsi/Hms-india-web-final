<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_no')->unique(); // T001, T002...
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('doctor_id');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->date('token_date');
            $table->enum('status', ['waiting', 'in_consultation', 'completed', 'skipped', 'cancelled'])->default('waiting');
            $table->integer('queue_position')->default(0);
            $table->time('estimated_time')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_tokens');
    }
};
