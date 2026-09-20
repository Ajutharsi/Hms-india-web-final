<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoundAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id', 'staff_id', 'assigned_by',
        'frequency_hours', 'next_round_at', 'last_round_at', 'status', 'notes',
    ];

    protected $casts = [
        'next_round_at'   => 'datetime',
        'last_round_at'   => 'datetime',
        'frequency_hours' => 'integer',
    ];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isOverdue(): bool
    {
        if (!$this->next_round_at) return false;
        return now()->gt($this->next_round_at);
    }
}
