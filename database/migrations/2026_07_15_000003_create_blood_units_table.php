<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_no')->unique(); // BLD-YYYY-XXXX
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->enum('component', ['whole_blood', 'packed_rbc', 'ffp', 'platelets', 'cryoprecipitate']);
            $table->string('donor_name')->nullable();
            $table->date('collection_date');
            $table->date('expiry_date');
            $table->enum('status', ['available', 'reserved', 'transfused', 'expired', 'discarded'])->default('available');
            $table->unsignedBigInteger('reserved_for')->nullable(); // patient_id
            $table->foreign('reserved_for')->references('id')->on('patients')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_units');
    }
};
