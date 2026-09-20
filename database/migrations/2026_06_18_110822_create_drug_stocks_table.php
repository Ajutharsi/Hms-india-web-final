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
    Schema::create('drug_stocks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade');
        $table->enum('type', ['in', 'out']); 
        $table->integer('quantity');
        $table->integer('balance');
        $table->string('reference')->nullable(); 
        $table->string('reason')->nullable(); 
        $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('drug_stocks');
}

    
};
