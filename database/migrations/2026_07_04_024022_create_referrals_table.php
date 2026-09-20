<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referral_no')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('referred_by');
            $table->unsignedBigInteger('referred_to');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('referred_by')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('referred_to')->references('id')->on('doctors')->onDelete('cascade');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->enum('priority', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->enum('status', ['pending', 'accepted', 'completed', 'cancelled'])->default('pending');
            $table->date('referral_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};