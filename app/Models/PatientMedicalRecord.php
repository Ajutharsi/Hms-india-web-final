<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientMedicalRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

     protected $fillable = [
        'patient_id', 'record_type', 'record_date',
        'description', 'doctor_name', 'notes','reference_id','reference_type'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
