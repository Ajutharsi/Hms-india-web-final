<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One month's pay for one member of staff.
 */
class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'period', 'basic', 'allowances', 'deductions',
        'net_pay', 'status', 'paid_on', 'payment_mode', 'notes', 'created_by',
    ];

    protected $casts = [
        'paid_on' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Net is always basic plus allowances less deductions; it is stored
     * so an issued payslip keeps saying what it said, but it is never
     * taken from the caller.
     */
    public static function netOf(array $values): float
    {
        return round(
            (float) ($values['basic'] ?? 0)
            + (float) ($values['allowances'] ?? 0)
            - (float) ($values['deductions'] ?? 0),
            2
        );
    }
}
