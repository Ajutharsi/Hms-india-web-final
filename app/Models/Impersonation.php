<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Structured audit record of one Super Admin impersonation session — who
 * started it, which hospital, which user was entered, and when it started
 * and ended. See SUPER_ADMIN_IMPERSONATION_REPORT.md.
 */
class Impersonation extends Model
{
    protected $fillable = [
        'super_admin_id', 'hospital_id', 'impersonated_user_id',
        'started_at', 'ended_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function superAdmin()
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function impersonatedUser()
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function durationForHumans(): string
    {
        $end = $this->ended_at ?? now();
        return $this->started_at->diffForHumans($end, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
    }
}
