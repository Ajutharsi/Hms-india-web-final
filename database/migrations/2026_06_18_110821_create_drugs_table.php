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
    Schema::create('drugs', function (Blueprint $table) {
        $table->id();
        $table->string('drug_code')->unique();
        $table->unsignedBigInteger('inventory_item_id')->nullable();
        $table->string('drug_name');
        $table->string('generic_name')->nullable();
        $table->string('category'); 
        $table->string('form'); 
        $table->string('strength')->nullable(); 
        $table->string('manufacturer')->nullable();
        $table->decimal('unit_price', 8, 2)->default(0);
        $table->integer('min_stock_level')->default(10);
        $table->integer('current_stock')->default(0);
        $table->date('expiry_date')->nullable();
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->timestamps();
    });
}



    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::dropIfExists('drugs');
}
};
