<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DietPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'diet_type', 'restrictions', 'notes', 'status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
