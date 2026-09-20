<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Hospital extends Model
{
    use HasFactory, SoftDeletes;

    /** Always present in array/JSON output (e.g. the edit-offcanvas fetch). */
    protected $appends = ['logo_url'];

    protected $fillable = [
        'code', 'name', 'contact_person', 'email', 'phone', 'city', 'country',
        'logo_path', 'status', 'plan', 'monthly_fee', 'is_default',
        'trial_ends_at', 'subscription_starts_at', 'subscription_ends_at',
        'notes',
    ];

    protected $casts = [
        'trial_ends_at'           => 'date',
        'subscription_starts_at'  => 'date',
        'subscription_ends_at'    => 'date',
        'monthly_fee'             => 'decimal:2',
        'is_default'              => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($hospital) {
            $last   = static::latest('id')->first();
            $number = $last ? $last->id + 1 : 1;
            do {
                $code = 'HSP-' . str_pad($number, 4, '0', STR_PAD_LEFT);
                $number++;
            } while (static::where('code', $code)->exists());

            $hospital->code = $code;
        });

        // Only one hospital may be the platform's default (self) tenant.
        static::saving(function ($hospital) {
            if ($hospital->is_default) {
                static::where('id', '!=', $hospital->id ?? 0)->update(['is_default' => false]);
            }
        });
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }

    /** Every admin-role user linked to this hospital (see SUPER_ADMIN_IMPERSONATION_REPORT.md). */
    public function adminUsers()
    {
        return $this->hasMany(User::class)->whereHas('roles', fn ($q) => $q->where('name', 'admin'));
    }

    /** The primary admin to impersonate for this hospital — the oldest linked admin account. */
    public function adminUser(): ?User
    {
        return $this->adminUsers()->oldest()->first();
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/' . $this->logo_path);
        }

        return null;
    }

    /**
     * Real, per-hospital usage metrics — only meaningful for the platform's
     * default hospital, which genuinely IS this running install. Every other
     * hospital is a CRM/tenant-registry record with no linked operational
     * data (no hospital_id on User/Doctor/Patient — see
     * SUPER_ADMIN_MODULE_ANALYSIS.md §17), so it returns null rather than a
     * fabricated number. Wire this up for real once row-level multi-tenancy
     * (SUPER_ADMIN_MASTER_PLAN.md §4.2/Phase 4) lands.
     *
     * @return array{totalUsers: int, totalDoctors: int, totalPatients: int, storageUsage: string}|null
     */
    /** Per-instance memoized — a DataTables row renders this via 4 separate columns. */
    private ?array $tenantMetricsCache = null;

    public function tenantMetrics(): ?array
    {
        if (! $this->is_default) {
            return null;
        }

        if ($this->tenantMetricsCache !== null) {
            return $this->tenantMetricsCache;
        }

        $dbSizeMb = \Illuminate\Support\Facades\Cache::remember('dashboard.db_size', 300, function () {
            try {
                return \Illuminate\Support\Facades\DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()")[0]->size ?? 0;
            } catch (\Throwable) {
                return 0;
            }
        });

        return $this->tenantMetricsCache = [
            'totalUsers'    => \App\Models\User::count(),
            'totalDoctors'  => \App\Models\Doctor::count(),
            'totalPatients' => \App\Models\Patient::count(),
            'storageUsage'  => $dbSizeMb . ' MB',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /** The subscription currently governing this hospital's access/feature-gating. */
    public function currentSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /** Subscription end date within the next N days (used for "expiring soon" widgets). */
    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeAlreadyExpired($query)
    {
        return $query->whereNotNull('subscription_ends_at')->where('subscription_ends_at', '<', now());
    }

    /**
     * The single Hospital record representing this application instance's own
     * tenant (see SUPER_ADMIN_MASTER_PLAN.md §4.1) — creates one on first use
     * so a fresh install always has somewhere to attach its subscription.
     */
    public static function firstOrCreateDefault(): self
    {
        return static::default()->first() ?? static::create([
            'name'       => config('app.name', 'Default Hospital'),
            'status'     => 'active',
            'plan'       => 'basic',
            'is_default' => true,
        ]);
    }
}
