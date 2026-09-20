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
    Schema::create('lab_results', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lab_order_id')->constrained('lab_orders')->onDelete('cascade');
        $table->foreignId('lab_order_item_id')->constrained('lab_order_items')->onDelete('cascade');
        $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
        $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');
        $table->string('result_value');
        $table->string('unit')->nullable();
        $table->string('normal_range')->nullable();
        $table->enum('flag', ['normal', 'high', 'low', 'critical'])->default('normal');
        $table->text('remarks')->nullable();
        $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
        $table->datetime('result_date');
        $table->timestamps();
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::dropIfExists('lab_results');
}

};
