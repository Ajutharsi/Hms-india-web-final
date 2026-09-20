<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErRegistration extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'er_no', 'patient_id', 'patient_name', 'patient_phone', 'age', 'gender',
        'triaged_by', 'attending_doctor_id', 'triage_level', 'chief_complaint',
        'presenting_symptoms', 'mode_of_arrival', 'status', 'arrival_time',
        'triage_time', 'discharge_time', 'notes', 'icd10_code',
    ];

    protected $casts = [
        'arrival_time'   => 'datetime',
        'triage_time'    => 'datetime',
        'discharge_time' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($er) {
            $year  = date('Y');
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->er_no, -4) + 1 : 1;
            $er->er_no = 'ER-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function triagedBy()
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    public function attendingDoctor()
    {
        return $this->belongsTo(Doctor::class, 'attending_doctor_id');
    }
}
