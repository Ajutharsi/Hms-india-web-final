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
    Schema::create('patients', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        $table->string('mrn')->unique();
        $table->text('name');
        $table->string('name_hash', 64)->nullable();
        $table->text('phone')->nullable();
        $table->string('phone_hash', 64)->nullable();
        $table->text('email')->nullable();
        $table->string('email_hash', 64)->nullable();
        $table->text('dob')->nullable();
        $table->enum('gender', ['male', 'female', 'other']);
        $table->text('blood_group')->nullable();
        $table->text('address')->nullable();
        $table->text('emergency_contact')->nullable();
        $table->text('emergency_contact_name')->nullable();
        $table->string('nationality')->nullable()->default('Indian');
        $table->enum('marital_status', ['single','married','divorced','widowed'])->nullable();
        $table->string('occupation')->nullable();
        $table->string('photo')->nullable();
        $table->enum('status', ['active','discharged','deceased'])->default('active');
        $table->enum('patient_type', ['op','ip'])->default('op');
        $table->timestamps();
        $table->softDeletes();

        $table->index('name_hash');
        $table->index('phone_hash');
        $table->index('email_hash');
        $table->index(['status', 'patient_type']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
