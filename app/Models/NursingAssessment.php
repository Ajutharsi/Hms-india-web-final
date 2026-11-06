<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One bedside assessment: fall risk, pain, pressure-sore risk and the
 * early warning score.
 *
 * The bands are derived here rather than stored, so a hospital that
 * moves a threshold does not have to rewrite its history.
 */
class NursingAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'admission_id', 'assessed_by', 'assessed_at',
        'fall_risk_score', 'pain_score', 'braden_score', 'ews_score', 'notes',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /** Morse: 0-24 low, 25-44 moderate, 45+ high. */
    public function getFallRiskBandAttribute(): ?string
    {
        return match (true) {
            $this->fall_risk_score === null => null,
            $this->fall_risk_score >= 45 => 'high',
            $this->fall_risk_score >= 25 => 'moderate',
            default => 'low',
        };
    }

    /** Pain: 1-3 mild, 4-6 moderate, 7+ severe. */
    public function getPainBandAttribute(): ?string
    {
        return match (true) {
            $this->pain_score === null => null,
            $this->pain_score >= 7 => 'severe',
            $this->pain_score >= 4 => 'moderate',
            $this->pain_score >= 1 => 'mild',
            default => 'none',
        };
    }

    /** Braden runs the other way: the lower the score, the greater the risk. */
    public function getBradenBandAttribute(): ?string
    {
        return match (true) {
            $this->braden_score === null => null,
            $this->braden_score <= 12 => 'high',
            $this->braden_score <= 16 => 'moderate',
            default => 'low',
        };
    }

    /** NEWS: 0-4 low, 5-6 medium, 7+ high -- 7 is an emergency call. */
    public function getEwsBandAttribute(): ?string
    {
        return match (true) {
            $this->ews_score === null => null,
            $this->ews_score >= 7 => 'high',
            $this->ews_score >= 5 => 'medium',
            default => 'low',
        };
    }

    /**
     * Whether this assessment is one somebody should act on now.
     */
    public function getNeedsAttentionAttribute(): bool
    {
        return $this->fall_risk_band === 'high'
            || $this->braden_band === 'high'
            || $this->ews_band === 'high'
            || $this->pain_band === 'severe';
    }
}
