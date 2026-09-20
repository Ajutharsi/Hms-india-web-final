<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('logged_by')->nullable();
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('logged_by')->references('id')->on('users')->onDelete('set null');
            $table->date('log_date');
            $table->enum('meal_type', ['breakfast', 'lunch', 'snacks', 'dinner']);
            $table->enum('status', ['pending', 'given', 'refused', 'partial'])->default('pending');
            $table->enum('quantity', ['full', 'half', 'none'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_logs');
    }
};