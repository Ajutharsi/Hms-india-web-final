<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_charts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('admission_id')->nullable();
            // Ventilator parameters
            $table->string('ventilator_mode')->nullable(); // AC, SIMV, CPAP, PSV
            $table->integer('fio2')->nullable(); // %
            $table->integer('peep')->nullable(); // cmH2O
            $table->integer('tidal_volume')->nullable(); // ml
            $table->integer('respiratory_rate_set')->nullable();
            // Hemodynamics
            $table->string('arterial_bp')->nullable();
            $table->integer('cvp')->nullable(); // mmHg
            $table->integer('heart_rate')->nullable();
            $table->string('rhythm')->nullable(); // Sinus, AF, etc.
            // Lines & Drains
            $table->text('lines_drains')->nullable(); // CVL, arterial line, foley, etc.
            // GCS
            $table->integer('gcs_eye')->nullable(); // 1-4
            $table->integer('gcs_verbal')->nullable(); // 1-5
            $table->integer('gcs_motor')->nullable(); // 1-6
            $table->integer('gcs_total')->nullable(); // 3-15
            // Fluid balance
            $table->decimal('intake_oral', 8, 2)->nullable();
            $table->decimal('intake_iv', 8, 2)->nullable();
            $table->decimal('output_urine', 8, 2)->nullable();
            $table->decimal('output_drain', 8, 2)->nullable();
            $table->decimal('fluid_balance', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('charted_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_charts');
    }
};
