<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsentForm extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'consent_no', 'patient_id', 'consent_type', 'procedure_name',
        'description', 'patient_name', 'witness_name', 'doctor_name',
        'consent_date', 'patient_signature', 'witness_signature',
        'status', 'created_by',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
