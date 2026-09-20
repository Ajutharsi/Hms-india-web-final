<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id', 'patient_id', 'doctor_id',
        'appointment_date', 'appointment_time',
        'token_number', 'type', 'status', 'notes', 'booked_by'
    ];

    public function getRouteKeyName()
{
    return 'id';
}

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    // Auto generate appointment_id
   protected static function boot()
{
    parent::boot();
    static::creating(function ($appointment) {
        $year = date('Y');
        do {
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->appointment_id, -4) + 1 : 1;
            $aptId = 'APT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        } while (static::where('appointment_id', $aptId)->exists());

        $appointment->appointment_id = $aptId;
    });
}
}
