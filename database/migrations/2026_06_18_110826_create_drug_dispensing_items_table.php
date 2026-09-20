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
    Schema::create('drug_dispensing_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('dispensing_id')->constrained('drug_dispensings')->onDelete('cascade');
        $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade');
        $table->string('medicine_name'); 
        $table->integer('quantity_prescribed');
        $table->integer('quantity_dispensed');
        $table->decimal('unit_price', 8, 2)->default(0);
        $table->decimal('total_price', 8, 2)->default(0);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('drug_dispensing_items');
}
};
