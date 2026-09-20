<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FluidLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'logged_by', 'log_date', 'log_time',
        'flow_type', 'category', 'amount_ml', 'notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
