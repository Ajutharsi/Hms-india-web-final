<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// NOTE: this model appears unused (no table migration, no references) — candidate for removal.
class OpdRecord extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'appointment_id',
        'visit_number', 'visit_date', 'chief_complaint',
        'diagnosis', 'treatment', 'notes',
        'consultation_fee', 'status'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($record) {
            $last   = static::latest('id')->first();
            $number = $last ? $last->id + 1 : 1;
            $record->visit_number = 'OPD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
}
