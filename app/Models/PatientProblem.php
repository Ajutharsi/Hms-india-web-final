<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientProblem extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'problem_name', 'onset_date', 'status', 'notes'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

}
