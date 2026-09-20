<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpdToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_no', 'patient_id', 'doctor_id', 'appointment_id', 'token_date',
        'status', 'queue_position', 'estimated_time', 'called_at',
        'completed_at', 'notes',
    ];

    protected $casts = [
        'token_date'   => 'date',
        'called_at'    => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Numbered per token_date (not creation day), continuing from the
        // highest existing suffix so deleted tokens never cause a reused number.
        static::creating(function ($token) {
            $date   = $token->token_date ? \Carbon\Carbon::parse($token->token_date) : today();
            $prefix = 'T-' . $date->format('Ymd') . '-';
            $last   = static::where('token_no', 'like', $prefix . '%')
                ->selectRaw('MAX(CAST(SUBSTRING(token_no, ?) AS UNSIGNED)) AS n', [strlen($prefix) + 1])
                ->value('n');
            $token->token_no = $prefix . str_pad(((int) $last) + 1, 3, '0', STR_PAD_LEFT);
        });
    }

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
}
