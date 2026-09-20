<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;


class PlatformAnalyticsService
{
    private const CACHE_TTL = 300; // 5 minutes — analytics tolerate being slightly stale.

    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    
    public function growthAnalytics(): array
    {
        return Cache::remember('analytics.growth', self::CACHE_TTL, function () {
            return [
                'hospitals'     => $this->dashboard->hospitalGrowthTrend(),
                'users'         => $this->monthlySeries(User::class),
                'doctors'       => $this->monthlySeries(Doctor::class),
                'patients'      => $this->monthlySeries(Patient::class),
                'subscriptions' => $this->monthlySeries(Subscription::class),
            ];
        });
    }

    /** @return array<int, array{month: string, count: int}> */
    private function monthlySeries(string $modelClass): array
    {
        $from = now()->subMonths(5)->startOfMonth();

        $counts = $modelClass::where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
            ->groupBy('ym')->pluck('cnt', 'ym');

        $series = [];
        for ($i = 5; $i >= 0; $i--) {
            
            $month = now()->startOfMonth()->subMonths($i);
            $ym    = $month->format('Y-m');
            $series[] = ['month' => $month->format('M'), 'count' => (int) ($counts[$ym] ?? 0)];
        }

        return $series;
    }

    
    public function revenueAnalytics(): array
    {
        return Cache::remember('analytics.revenue', self::CACHE_TTL, function () {
            $active = Subscription::where('status', 'active');

           
            $annual = (clone $active)->where('billing_cycle', 'yearly')->sum('total_amount')
                + (clone $active)->where('billing_cycle', 'monthly')->sum('total_amount') * 12;

            $activeValue = (float) (clone $active)->sum('total_amount');

            return [
                'monthlyRevenue'           => $activeValue,
                'annualRevenue'            => (float) $annual,
                'activeSubscriptionValue'  => $activeValue,
                'expiredSubscriptionValue' => (float) Subscription::where('status', 'expired')->sum('total_amount'),
            ];
        });
    }

    public function subscriptionAnalytics(): array
    {
        return Cache::remember('analytics.subscriptions', self::CACHE_TTL, function () {
            $byStatus = Subscription::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

            return [
                'active'       => (int) ($byStatus['active'] ?? 0),
                
                'trial'        => Subscription::whereHas('hospital', fn ($q) => $q->where('status', 'trial'))->count(),
                'expiringSoon' => Subscription::where('status', 'active')
                    ->whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
                'expired'      => (int) ($byStatus['expired'] ?? 0),
                'cancelled'    => (int) ($byStatus['cancelled'] ?? 0),
                'archived'     => Subscription::onlyTrashed()->count(),
            ];
        });
    }

    public function planAnalytics(): array
    {
        return Cache::remember('analytics.plans', self::CACHE_TTL, function () {
            $distribution = Subscription::where('subscriptions.status', 'active')
                ->whereNotNull('plan_id')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->selectRaw('plans.id, plans.name, COUNT(*) as subscriptions, SUM(subscriptions.total_amount) as revenue')
                ->groupBy('plans.id', 'plans.name')
                ->orderByDesc('subscriptions')
                ->get();

            return [
                'distribution'  => $distribution,
                'mostPopular'   => $distribution->first(),
                'totalPlans'    => Plan::count(),
                'activePlans'   => Plan::active()->count(),
            ];
        });
    }

    
    public function usageAnalytics(): array
    {
        return Cache::remember('analytics.usage', self::CACHE_TTL, function () {
            $totalHospitals = Hospital::count();
            $totalUsers     = User::count();
            $scopedUsers    = User::whereNotNull('hospital_id')->count();

            return [
                'totalUsers'         => $totalUsers,
                'doctors'            => Doctor::count(),
                'staff'              => User::role(['nurse', 'staff', 'receptionist', 'lab_assistant', 'pharmacist'])->count(),
                'patients'           => Patient::count(),
                
                'avgUsersPerHospital' => $scopedUsers === $totalUsers && $totalHospitals > 0
                    ? round($totalUsers / $totalHospitals, 1)
                    : null,
                'storageUsedMb'      => $this->platformStorageUsedMb(),
               
                'avgStorageUsedMb'   => null,
            ];
        });
    }

    /** Real sum of file sizes under the public storage disk — not an estimate. */
    public function platformStorageUsedMb(): ?float
    {
        return Cache::remember('analytics.storage_used', self::CACHE_TTL, function () {
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk('public');
                $bytes = collect($disk->allFiles())->sum(fn ($file) => $disk->size($file));
                return round($bytes / 1024 / 1024, 2);
            } catch (\Throwable) {
                return null;
            }
        });
    }

    public function registrationAnalytics(): array
    {
        return Cache::remember('analytics.registrations', self::CACHE_TTL, function () {
            return [
                'newHospitals30d' => Hospital::where('created_at', '>=', now()->subDays(30))->count(),
                'newUsers30d'     => User::where('created_at', '>=', now()->subDays(30))->count(),
                'recentHospitals' => Hospital::latest()->take(8)->get(['id', 'name', 'status', 'created_at']),
            ];
        });
    }

    /** Everything the Analytics page needs, one call — mirrors the shape each section method returns. */
    public function all(): array
    {
        return [
            'overview'      => $this->dashboard->platformStats(),
            'growth'        => $this->growthAnalytics(),
            'revenue'       => $this->revenueAnalytics(),
            'subscriptions' => $this->subscriptionAnalytics(),
            'plans'         => $this->planAnalytics(),
            'usage'         => $this->usageAnalytics(),
            'registrations' => $this->registrationAnalytics(),
        ];
    }
}
