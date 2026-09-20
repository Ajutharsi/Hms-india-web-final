<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchemeBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_bill_no', 'patient_id', 'invoice_id', 'scheme', 'scheme_name',
        'beneficiary_id', 'package_code', 'package_name', 'package_rate',
        'actual_bill', 'scheme_payment', 'patient_liability', 'status',
        'rejection_reason', 'submission_date', 'settlement_date', 'icd10_code',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'settlement_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {
            $year  = date('Y');
            $last  = static::whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->scheme_bill_no, -4) + 1 : 1;
            $bill->scheme_bill_no = 'SCH-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
