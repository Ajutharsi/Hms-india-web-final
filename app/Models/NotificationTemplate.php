<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'title', 'sms_template', 'whatsapp_template',
        'email_template', 'variables', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
