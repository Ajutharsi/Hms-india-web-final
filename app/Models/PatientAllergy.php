<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientAllergy extends Model
{
    use HasFactory;

     protected $fillable = [
        'patient_id', 'allergen', 'type', 'severity', 'reaction'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
