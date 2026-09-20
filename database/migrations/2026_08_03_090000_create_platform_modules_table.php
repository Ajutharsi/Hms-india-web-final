<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('icon')->nullable();
            $table->string('color')->default('primary');
            $table->enum('tier', ['core', 'optional', 'experimental'])->default('optional');
            $table->enum('visibility', ['visible', 'hidden'])->default('visible');
            $table->json('depends_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_modules');
    }
};
