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
    Schema::create('doctors', function (Blueprint $table) {
        $table->id();
        $table->string('doctor_id')->unique();
        $table->string('name');
        $table->string('specialization');
        $table->string('department')->nullable();
        $table->string('qualification')->nullable();
        $table->string('registration_number')->nullable()->unique();
        $table->string('license_file')->nullable();
        $table->string('license_file_name')->nullable();
        $table->date('license_expiry')->nullable();
        $table->string('certificate_file')->nullable();
        $table->string('certificate_file_name')->nullable();
        $table->string('id_file')->nullable();
        $table->string('id_file_name')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('photo')->nullable();
        $table->decimal('consultation_fee', 8, 2)->default(0);
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('doctors');
}
};
