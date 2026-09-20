<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftHandoverTask extends Model
{
    protected $fillable = [
        'shift_handover_id', 'patient_id', 'task_description', 'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function shiftHandover()
    {
        return $this->belongsTo(ShiftHandover::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
