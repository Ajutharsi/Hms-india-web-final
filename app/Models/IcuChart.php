<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IcuChart extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'recorded_by', 'admission_id',
        'ventilator_mode', 'fio2', 'peep', 'tidal_volume', 'respiratory_rate_set',
        'arterial_bp', 'cvp', 'heart_rate', 'rhythm', 'lines_drains',
        'gcs_eye', 'gcs_verbal', 'gcs_motor', 'gcs_total',
        'intake_oral', 'intake_iv', 'output_urine', 'output_drain', 'fluid_balance',
        'notes', 'charted_at',
    ];

    protected $casts = [
        'charted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($chart) {
            if ($chart->gcs_eye !== null && $chart->gcs_verbal !== null && $chart->gcs_motor !== null) {
                $chart->gcs_total = $chart->gcs_eye + $chart->gcs_verbal + $chart->gcs_motor;
            }

            $intake  = (float) $chart->intake_oral + (float) $chart->intake_iv;
            $output  = (float) $chart->output_urine + (float) $chart->output_drain;
            if ($chart->intake_oral !== null || $chart->intake_iv !== null || $chart->output_urine !== null || $chart->output_drain !== null) {
                $chart->fluid_balance = $intake - $output;
            }
        });
    }

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
}
