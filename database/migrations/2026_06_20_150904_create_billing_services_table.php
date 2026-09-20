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
    Schema::create('billing_services', function (Blueprint $table) {
        $table->id();
        $table->string('service_code')->unique();
        $table->string('service_name');
        $table->string('category'); 
        $table->decimal('price', 10, 2)->default(0);
        $table->text('description')->nullable();
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('billing_services');
}

};
