<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequenceNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabOrder extends Model
{
    use GeneratesSequenceNumber;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'patient_id', 'doctor_id',
        'appointment_id', 'order_date', 'status', 'notes',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $year  = date('Y');
            $model->order_no = static::nextSequenceNumber('order_no', 'LAB-' . $year . '-');
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

    public function items()
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function results()
    {
        return $this->hasMany(LabResult::class);
    }
}
