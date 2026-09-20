<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referral extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'referral_no', 'patient_id', 'referred_by', 'referred_to',
        'reason', 'notes', 'priority', 'status', 'referral_date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function referredBy()
    {
        return $this->belongsTo(Doctor::class, 'referred_by');
    }

    public function referredTo()
    {
        return $this->belongsTo(Doctor::class, 'referred_to');
    }
}
