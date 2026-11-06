<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A day's cash, closed.
 *
 * The counted figure is the only one a person supplies; everything else
 * comes from the day's rows. The variance between them is why the
 * record exists.
 */
class DayEndClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'closing_date', 'opening_cash', 'collections', 'cash_collections',
        'expenses', 'expected_cash', 'counted_cash', 'variance',
        'notes', 'closed_by', 'closed_at',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** Short or over, in words a person reads rather than a sign. */
    public function getVarianceLabelAttribute(): string
    {
        if (abs((float) $this->variance) < 0.01) {
            return 'balanced';
        }

        return (float) $this->variance > 0 ? 'over' : 'short';
    }
}
