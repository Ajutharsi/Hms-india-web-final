<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftHandover extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'handover_no', 'ward_id', 'current_shift', 'next_shift', 'handover_date',
        'outgoing_nurse_id', 'incoming_nurse_id', 'status', 'shift_summary',
        'submitted_at', 'accepted_at', 'rejected_at', 'rejection_reason',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'submitted_at'  => 'datetime',
        'accepted_at'   => 'datetime',
        'rejected_at'   => 'datetime',
    ];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function outgoingNurse()
    {
        return $this->belongsTo(User::class, 'outgoing_nurse_id');
    }

    public function incomingNurse()
    {
        return $this->belongsTo(User::class, 'incoming_nurse_id');
    }

    public function patients()
    {
        return $this->hasMany(ShiftHandoverPatient::class);
    }

    public function tasks()
    {
        return $this->hasMany(ShiftHandoverTask::class);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_acceptance';
    }

    public function highPriorityCount(): int
    {
        return $this->patients->where('is_high_priority', true)->count();
    }

    // Auto-generate handover_no — same year-scoped pattern as Admission::admission_no.
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($handover) {
            $year  = date('Y');
            // withTrashed: soft-deleted handovers still hold their unique number.
            $last  = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
            $count = $last ? (int) substr($last->handover_no, -4) + 1 : 1;
            $handover->handover_no = 'HO-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }
}
