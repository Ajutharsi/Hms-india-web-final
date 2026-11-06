<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One person, one day, one shift.
 *
 * Attendance says what happened and leave says who is away; this says
 * what was planned.
 */
class DutyRoster extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'duty_date', 'shift', 'ward_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'duty_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }
}
