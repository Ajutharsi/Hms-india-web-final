<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fluid_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('logged_by')->nullable();
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('logged_by')->references('id')->on('users')->onDelete('set null');
            $table->date('log_date');
            $table->time('log_time');
            $table->enum('flow_type', ['intake', 'output']);
            $table->enum('category', ['oral', 'iv', 'tube_feed', 'urine', 'vomit', 'drain', 'stool', 'other']);
            $table->integer('amount_ml');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fluid_logs');
    }
};