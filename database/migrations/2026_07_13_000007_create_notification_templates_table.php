<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // appointment_reminder, lab_ready, etc.
            $table->string('title');
            $table->text('sms_template')->nullable();
            $table->text('whatsapp_template')->nullable();
            $table->text('email_template')->nullable();
            $table->string('variables')->nullable(); // {{patient_name}}, {{date}}, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
