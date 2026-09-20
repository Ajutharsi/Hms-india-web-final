<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use GeneratesSequenceNumber;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payment_no', 'invoice_id', 'patient_id',
        'amount', 'payment_method', 'payment_date',
        'reference_no', 'notes', 'received_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $year  = date('Y');
            $model->payment_no = static::nextSequenceNumber('payment_no', 'PAY-' . $year . '-');
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
