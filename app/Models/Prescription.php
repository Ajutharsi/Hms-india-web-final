<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prescription extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'prescription_no', 'consultation_id',
        'patient_id', 'doctor_id',
        'prescription_date', 'notes', 'status'
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // Auto generate prescription_no
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($prescription) {
            $year = date('Y');
            do {
                $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
                $count = $last ? (int) substr($last->prescription_no, -4) + 1 : 1;
                $prescriptionNo = 'RX-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            } while (static::withTrashed()->where('prescription_no', $prescriptionNo)->exists());

            $prescription->prescription_no = $prescriptionNo;
        });
    }
}
