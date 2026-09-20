<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BloodTransfusion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'transfusion_no', 'patient_id', 'admission_id', 'doctor_id', 'blood_unit_id', 'administered_by',
        'indication', 'status', 'reaction_notes', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($t) {
            $year  = date('Y');
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->transfusion_no, -4) + 1 : 1;
            $t->transfusion_no = 'TRF-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bloodUnit()
    {
        return $this->belongsTo(BloodUnit::class);
    }

    public function administeredBy()
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
