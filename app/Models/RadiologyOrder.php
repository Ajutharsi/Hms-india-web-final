<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RadiologyOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'patient_id', 'doctor_id', 'performed_by',
        'modality', 'body_part', 'study_description', 'clinical_indication',
        'findings', 'impression', 'radiologist_name', 'status', 'priority',
        'order_date', 'scheduled_at', 'completed_at',
    ];

    protected $casts = [
        'order_date'   => 'date',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $year  = date('Y');
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->order_no, -4) + 1 : 1;
            $order->order_no = 'RAD-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
