<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicationAdministration extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'admission_id', 'doctor_id', 'nurse_id',
        'drug_name', 'dosage', 'route', 'frequency',
        'scheduled_time', 'administered_at', 'status',
        'notes', 'is_prn', 'is_controlled',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'is_prn'          => 'boolean',
        'is_controlled'   => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
