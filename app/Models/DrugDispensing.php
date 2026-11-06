<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequenceNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DrugDispensing extends Model
{
    use GeneratesSequenceNumber;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'dispensing_no', 'patient_id', 'prescription_id',
        'dispensed_by', 'dispensed_date', 'total_amount',
        'sale_type', 'returned_from_id',
        'status', 'notes',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $year  = date('Y');
            $model->dispensing_no = static::nextSequenceNumber('dispensing_no', 'DIS-' . $year . '-');
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function dispensedBy()
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function items()
    {
        return $this->hasMany(DrugDispensingItem::class, 'dispensing_id');
    }
}
