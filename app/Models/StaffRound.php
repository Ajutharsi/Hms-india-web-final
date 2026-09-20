<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'staff_id', 'round_time',
        'status', 'blood_pressure', 'temperature',
        'pulse_rate', 'spo2', 'notes',
    ];

    protected $casts = [
        'round_time' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
