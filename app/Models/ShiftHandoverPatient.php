<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftHandoverPatient extends Model
{
    protected $fillable = [
        'shift_handover_id', 'patient_id', 'admission_id',
        'condition', 'medication_status', 'iv_fluid_status', 'iv_next_change_time',
        'doctor_instructions', 'pending_procedures', 'pending_lab_reports', 'pending_imaging',
        'special_notes', 'is_high_priority',
    ];

    protected $casts = [
        'iv_next_change_time' => 'datetime',
        'is_high_priority'    => 'boolean',
    ];

    public function shiftHandover()
    {
        return $this->belongsTo(ShiftHandover::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    /** Live vitals — deliberately not stored on this row, read from the existing Vitals module. */
    public function latestVitals(): ?Vital
    {
        return $this->patient?->vitals()->latest('recorded_at')->first();
    }
}
