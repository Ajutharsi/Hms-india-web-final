<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Consultation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id', 'patient_id', 'doctor_id',
        'blood_pressure', 'temperature', 'pulse_rate',
        'weight', 'height', 'spo2',
        'chief_complaint', 'diagnosis', 'icd10_code',
        'treatment_plan', 'notes'
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
