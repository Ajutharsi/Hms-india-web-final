<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtSchedule extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ot_no', 'patient_id', 'admission_id', 'consent_form_id', 'surgeon_id', 'anaesthetist_id', 'scrub_nurse_id',
        'theatre_no', 'procedure_name', 'icd10_code', 'anaesthesia_type',
        'status', 'priority', 'scheduled_start', 'scheduled_end',
        'actual_start', 'actual_end', 'pre_op_notes', 'post_op_notes', 'complications',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end'   => 'datetime',
        'actual_start'    => 'datetime',
        'actual_end'      => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ot) {
            $year  = date('Y');
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->ot_no, -4) + 1 : 1;
            $ot->ot_no = 'OT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgeon()
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id');
    }

    public function anaesthetist()
    {
        return $this->belongsTo(Doctor::class, 'anaesthetist_id');
    }

    public function scrubNurse()
    {
        return $this->belongsTo(User::class, 'scrub_nurse_id');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function consentForm()
    {
        return $this->belongsTo(ConsentForm::class);
    }

    public function duration(): ?int
    {
        if (!$this->actual_start || !$this->actual_end) {
            return null;
        }

        return $this->actual_start->diffInMinutes($this->actual_end);
    }
}
