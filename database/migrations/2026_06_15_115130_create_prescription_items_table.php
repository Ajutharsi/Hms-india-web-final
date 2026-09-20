<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('prescription_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
        $table->string('medicine_name');
        $table->string('dosage');
        $table->string('frequency'); // 1-0-1, 1-1-1
        $table->string('duration');  // 5 days, 1 week
        $table->string('route')->nullable(); // oral, injection
        $table->text('instructions')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
