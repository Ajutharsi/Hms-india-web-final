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
    Schema::create('lab_tests', function (Blueprint $table) {
        $table->id();
        $table->string('test_code')->unique();
        $table->string('test_name');
        $table->string('category'); 
        $table->string('sample_type'); 
        $table->decimal('price', 8, 2)->default(0);
        $table->string('unit')->nullable(); 
        $table->string('normal_range_male')->nullable();
        $table->string('normal_range_female')->nullable();
        $table->text('description')->nullable();
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('lab_tests');
}

};
