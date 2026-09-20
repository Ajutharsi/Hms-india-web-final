<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hospital_id',
        'hospital_name', 'hospital_email', 'hospital_phone',
        'plan', 'plan_id', 'allowed_modules', 'module_pricing',
        'total_amount', 'billing_cycle', 'currency', 'payment_status',
        'trial_started_at', 'expires_at', 'last_renewed_at', 'cancelled_at',
        'status', 'notes',
    ];

    protected $casts = [
        'allowed_modules'  => 'array',
        'module_pricing'   => 'array',
        'expires_at'       => 'datetime',
        'trial_started_at' => 'datetime',
        'last_renewed_at'  => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    const DEFAULT_MODULE_PRICING = [
        'lab'           => 500,
        'pharmacy'      => 800,
        'billing'       => 600,
        'ward'          => 700,
        'food_intake'   => 300,
        'fluid_intake'  => 300,
        'staff_rounds'  => 400,
        'consent_forms' => 350,
        'referrals'     => 350,
        'reports'       => 500,
        'patient_portal'=> 400,
    ];

    const PLAN_MODULES = [
        'free_trial' => ['lab','pharmacy','billing','ward','food_intake','fluid_intake','staff_rounds','consent_forms','referrals','reports','patient_portal'],
        'basic'      => ['billing','patient_portal'],
        'pro'        => ['lab','pharmacy','billing','ward','food_intake','fluid_intake','staff_rounds','consent_forms','referrals','reports','patient_portal'],
        'custom'     => [],
    ];

    const PLAN_PRICING = [
        'free_trial' => 0,
        'basic'      => 1200,
        'pro'        => 4700,
        'custom'     => null,
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Named planCatalog(), not plan() — `plan` is already a string column
     * (the legacy free-text plan key), and Eloquent's attribute lookup
     * always wins over a same-named relationship method, so $this->plan
     * would silently keep returning the string forever if this relation
     * were named plan(). Use $this->planCatalog to get the real Plan model.
     */
    public function planCatalog()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function daysRemaining(): int
    {
        if (!$this->expires_at) return 0;
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function getAllowedModules(): array
    {
        // Always use saved allowed_modules if set — an explicit per-subscription
        // override takes priority over whatever the linked plan defines.
        if (!empty($this->allowed_modules)) {
            return $this->allowed_modules;
        }
        // Prefer the real linked Plan (database-backed catalog) over the
        // legacy hardcoded constants, which now only serve subscriptions
        // with no linked plan (pre-Plan-Management-era records).
        if ($this->plan_id && $this->planCatalog && !empty($this->planCatalog->modules)) {
            return $this->planCatalog->modules;
        }
        return self::PLAN_MODULES[$this->plan] ?? [];
    }

    public function isModuleAllowed(string $module): bool
    {
        return in_array($module, $this->getAllowedModules());
    }

    /**
     * Flat plan pricing (new, database-backed plans) takes priority; falls
     * back to the legacy per-module pricing sum for subscriptions with no
     * linked plan.
     */
    public function calculateTotal(): float
    {
        if ($this->plan_id && $this->planCatalog) {
            return (float) $this->planCatalog->price;
        }

        $pricing = $this->module_pricing ?? self::DEFAULT_MODULE_PRICING;
        $modules = $this->getAllowedModules();
        $total   = 0;
        foreach ($modules as $module) {
            $total += $pricing[$module] ?? 0;
        }
        return $total;
    }

    public function syncToSettings(): void
    {
        $settings = HospitalSetting::first();
        if (!$settings) return;

        $allowedModules = $this->getAllowedModules();
        $allModules     = array_keys(self::DEFAULT_MODULE_PRICING);

        $features = [];
        foreach ($allModules as $module) {
            $features[$module] = in_array($module, $allowedModules);
        }

        $settings->update(['features_enabled' => $features]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscription) {
            $subscription->hospital_id ??= Hospital::firstOrCreateDefault()->id;
        });

        static::saved(fn () => static::refreshCurrent());
        static::deleted(fn () => static::refreshCurrent());
    }

    /**
     * Resolves "the" subscription that gates the whole app for the current
     * single-tenant install: the platform's default hospital's most recent
     * subscription. Replaces the old "whichever row was saved/touched most
     * recently, across any hospital" resolution (see
     * SUPER_ADMIN_MASTER_PLAN.md §4.1) — editing an unrelated tenant's
     * subscription in the Hospitals CRM can no longer silently swap which
     * subscription drives everyone else's access.
     */
    public static function resolveCurrent(): ?self
    {
        $hospital = Hospital::default()->first();
        if (! $hospital) {
            return null;
        }

        return static::where('hospital_id', $hospital->id)->latest()->first();
    }

    // Recomputes "the current subscription" and pushes it into
    // config('hospital.subscription.*') immediately on any save/delete.
    public static function refreshCurrent(): ?self
    {
        $current = static::resolveCurrent();
        Cache::put('current_subscription', $current, 300);
        static::applyToConfig($current);

        return $current;
    }

    public static function applyToConfig(?self $subscription): void
    {
        if (! $subscription) {
            return;
        }

        config([
            'hospital.subscription.plan'       => $subscription->plan,
            'hospital.subscription.status'     => $subscription->status,
            'hospital.subscription.expires_at' => $subscription->expires_at,
            'hospital.subscription.modules'    => $subscription->getAllowedModules(),
        ]);
    }
}
