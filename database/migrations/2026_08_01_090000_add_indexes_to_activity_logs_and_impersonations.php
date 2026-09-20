<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('module');
            $table->index('action');
            $table->index(['module', 'created_at']);
        });

        Schema::table('impersonations', function (Blueprint $table) {
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['module']);
            $table->dropIndex(['action']);
            $table->dropIndex(['module', 'created_at']);
        });

        Schema::table('impersonations', function (Blueprint $table) {
            $table->dropIndex(['started_at']);
        });
    }
};
