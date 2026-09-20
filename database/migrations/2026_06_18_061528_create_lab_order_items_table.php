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
    Schema::create('lab_order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lab_order_id')->constrained('lab_orders')->onDelete('cascade');
        $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');
        $table->enum('status', ['pending', 'completed'])->default('pending');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('lab_order_items');
}
};
