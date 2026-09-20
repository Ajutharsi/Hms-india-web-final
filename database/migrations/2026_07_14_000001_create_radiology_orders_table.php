<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('set null');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
            $table->string('modality'); // X-Ray, CT, MRI, Ultrasound, Echo, PET
            $table->string('body_part');
            $table->string('study_description');
            $table->text('clinical_indication')->nullable();
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->string('radiologist_name')->nullable();
            $table->enum('status', ['ordered', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('ordered');
            $table->enum('priority', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->date('order_date');
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_orders');
    }
};
