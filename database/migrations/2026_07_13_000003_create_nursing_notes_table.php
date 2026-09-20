<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('written_by');
            $table->foreign('written_by')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->enum('note_type', ['nursing', 'progress', 'handover', 'assessment', 'discharge_summary'])->default('nursing');
            $table->enum('shift', ['morning', 'afternoon', 'night'])->nullable();
            $table->text('note');
            $table->string('priority')->default('routine'); // routine, urgent, critical
            $table->timestamp('noted_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('admission_id');
            $table->index('noted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_notes');
    }
};
