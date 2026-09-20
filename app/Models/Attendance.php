<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'user_id', 'attendance_date', 'shift', 'check_in', 'check_out',
        'status', 'hours_worked', 'notes', 'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in'        => 'datetime',
        'check_out'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
