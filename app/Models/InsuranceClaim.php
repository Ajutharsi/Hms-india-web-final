<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_no', 'patient_id', 'invoice_id', 'insurance_company',
        'tpa_name', 'policy_number', 'member_id', 'claim_amount',
        'approved_amount', 'patient_liability', 'claim_type', 'status',
        'admission_date', 'discharge_date', 'diagnosis',
        'treatment_details', 'rejection_reason', 'icd10_code',
    ];

    protected $casts = [
        'admission_date'    => 'date',
        'discharge_date'    => 'date',
        'claim_amount'      => 'decimal:2',
        'approved_amount'   => 'decimal:2',
        'patient_liability' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            $year  = date('Y');
            $last  = static::whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->claim_no, -4) + 1 : 1;
            $claim->claim_no = 'CLM-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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
