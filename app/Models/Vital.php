<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vital extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'recorded_by', 'admission_id',
        'blood_pressure', 'temperature', 'pulse_rate',
        'respiratory_rate', 'spo2', 'weight', 'height',
        'news_score', 'notes', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * Simplified NEWS (National Early Warning Score) calculation
     * from the vitals already recorded on this model.
     */
    public function newsScore(): int
    {
        $score = 0;

        if ($this->respiratory_rate !== null) {
            $rr = $this->respiratory_rate;
            if ($rr <= 8) $score += 3;
            elseif ($rr <= 11) $score += 1;
            elseif ($rr <= 20) $score += 0;
            elseif ($rr <= 24) $score += 2;
            else $score += 3;
        }

        if ($this->spo2 !== null) {
            $spo2 = $this->spo2;
            if ($spo2 <= 91) $score += 3;
            elseif ($spo2 <= 93) $score += 2;
            elseif ($spo2 <= 95) $score += 1;
        }

        if ($this->temperature !== null) {
            $temp = (float) $this->temperature;
            if ($temp <= 35.0) $score += 3;
            elseif ($temp <= 36.0) $score += 1;
            elseif ($temp <= 38.0) $score += 0;
            elseif ($temp <= 39.0) $score += 1;
            else $score += 2;
        }

        if ($this->pulse_rate !== null) {
            $pulse = $this->pulse_rate;
            if ($pulse <= 40) $score += 3;
            elseif ($pulse <= 50) $score += 1;
            elseif ($pulse <= 90) $score += 0;
            elseif ($pulse <= 110) $score += 1;
            elseif ($pulse <= 130) $score += 2;
            else $score += 3;
        }

        if ($this->blood_pressure) {
            $systolic = (int) explode('/', $this->blood_pressure)[0];
            if ($systolic <= 90) $score += 3;
            elseif ($systolic <= 100) $score += 2;
            elseif ($systolic <= 110) $score += 1;
            elseif ($systolic <= 219) $score += 0;
            else $score += 3;
        }

        return $score;
    }
}
