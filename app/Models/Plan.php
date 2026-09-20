<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Database-backed subscription plan catalog — the Super Admin's plan
 * management source of truth for new subscriptions. See
 * SUPER_ADMIN_SUBSCRIPTION_MANAGEMENT_REPORT.md for the reconciliation
 * with the legacy Subscription::PLAN_MODULES/PLAN_PRICING constants.
 */
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key', 'name', 'description', 'price', 'billing_cycle', 'trial_days',
        'user_limit', 'doctor_limit', 'patient_limit', 'storage_limit_mb',
        'modules', 'status',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'modules'  => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function limitLabel(?int $limit): string
    {
        return $limit === null ? 'Unlimited' : number_format($limit);
    }
}
