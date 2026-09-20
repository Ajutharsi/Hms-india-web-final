<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id', 'medicine_name',
        'dosage', 'frequency', 'duration',
        'route', 'instructions'
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
