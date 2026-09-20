<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientAttachment extends Model
{
    use HasFactory;

     protected $fillable = [
        'patient_id', 'file_name', 'file_path',
        'file_type', 'file_size', 'description', 'uploaded_by'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

}
