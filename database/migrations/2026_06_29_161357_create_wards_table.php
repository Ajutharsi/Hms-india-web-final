<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('ward_no')->unique();
            $table->string('name');
            $table->enum('type', ['general', 'private', 'semi_private', 'icu', 'nicu', 'emergency'])->default('general');
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->integer('total_beds')->default(0);
            $table->integer('available_beds')->default(0);
            $table->decimal('charge_per_day', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};