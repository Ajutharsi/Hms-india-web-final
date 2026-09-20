<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IpdDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_no', 'patient_id', 'admission_id', 'collected_by',
        'amount', 'type', 'payment_method', 'reference_no',
        'notes', 'deposited_at',
    ];

    protected $casts = [
        'deposited_at' => 'datetime',
        'amount'       => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deposit) {
            $year  = date('Y');
            $last  = static::whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->deposit_no, -4) + 1 : 1;
            $deposit->deposit_no = 'DEP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public static function getBalanceForPatient(int $patientId): float
    {
        $advance = static::where('patient_id', $patientId)->where('type', 'advance')->sum('amount');
        $refund  = static::where('patient_id', $patientId)->where('type', 'refund')->sum('amount');
        $adjust  = static::where('patient_id', $patientId)->where('type', 'adjustment')->sum('amount');

        return (float) ($advance - $refund + $adjust);
    }

    public static function getBalanceForAdmission(int $admissionId): float
    {
        $advance = static::where('admission_id', $admissionId)->where('type', 'advance')->sum('amount');
        $refund  = static::where('admission_id', $admissionId)->where('type', 'refund')->sum('amount');
        $adjust  = static::where('admission_id', $admissionId)->where('type', 'adjustment')->sum('amount');

        return (float) ($advance - $refund + $adjust);
    }
}
