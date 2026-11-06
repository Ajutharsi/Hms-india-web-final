<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Money going out: the half of the books the system never had.
 */
class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_date', 'category', 'paid_to', 'amount',
        'payment_mode', 'reference_no', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
