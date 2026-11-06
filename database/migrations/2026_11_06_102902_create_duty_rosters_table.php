<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who is on which shift, on which day.
 *
 * Attendance records what happened and leave records who is away; the
 * roster is the third leg -- what was planned. Without it the ward
 * sister works it out on paper and nobody else can see it.
 *
 * One row per person per day, so a double booking is a database error
 * rather than something to notice later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('duty_date');
            $table->enum('shift', ['morning', 'evening', 'night', 'off'])->default('morning');
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'duty_date']);
            $table->index('duty_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_rosters');
    }
};
