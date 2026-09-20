<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'admission_no', 'patient_id', 'ward_id', 'bed_id',
        'doctor_id', 'admission_date', 'discharge_date',
        'status', 'admission_reason', 'discharge_notes', 'total_charges',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function otSchedules()
    {
        return $this->hasMany(OtSchedule::class);
    }

    public function bloodTransfusions()
    {
        return $this->hasMany(BloodTransfusion::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function ipdDeposits()
    {
        return $this->hasMany(IpdDeposit::class);
    }

    // Auto generate admission_no
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($admission) {
            $year  = date('Y');
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->admission_no, -4) + 1 : 1;
            $admission->admission_no = 'ADM-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });

        // Bed status update on admission
        static::created(function ($admission) {
            Bed::where('id', $admission->bed_id)->update(['status' => 'occupied']);
            Ward::where('id', $admission->ward_id)
                ->decrement('available_beds');
        });

        // Bed status update on discharge
        static::updated(function ($admission) {
            if ($admission->isDirty('status') && $admission->status === 'discharged') {
                Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
                Ward::where('id', $admission->ward_id)
                    ->increment('available_beds');
            }
        });
    }
}
