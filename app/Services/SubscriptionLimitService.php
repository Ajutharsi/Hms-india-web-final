<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;


 
class SubscriptionLimitService
{
    private const MODULE = 'subscription_limits';

  
    private const USER_LIMIT_ROLES = ['admin', 'doctor', 'nurse', 'receptionist', 'lab_assistant', 'pharmacist', 'staff'];

    public const RESOURCES = ['user', 'doctor', 'patient', 'storage'];

    public function currentPlan(): ?Plan
    {
        return Subscription::resolveCurrent()?->planCatalog;
    }

    /** @return array<string, array{used:int,limit:?int,remaining:?int,percent:int,status:string}> */
    public function usageSummary(): array
    {
        return collect(self::RESOURCES)->mapWithKeys(fn ($resource) => [$resource => $this->resourceSummary($resource)])->all();
    }

    public function resourceSummary(string $resource): array
    {
        $used = $this->currentUsage($resource);
        $limit = $this->limitFor($resource);
        $percent = $limit ? min(100, (int) round(($used / max($limit, 1)) * 100)) : 0;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $limit === null ? null : max(0, $limit - $used),
            'percent' => $percent,
            'status' => $limit === null ? 'unlimited' : ($percent >= 100 ? 'exceeded' : ($percent >= 80 ? 'approaching' : 'ok')),
        ];
    }

    public function currentUsage(string $resource): int
    {
        return match ($resource) {
            'user' => User::role(self::USER_LIMIT_ROLES)->count(),
            'doctor' => Doctor::count(),
            'patient' => Patient::count(),
            'storage' => (int) round($this->storageUsageMb()),
            default => throw new \InvalidArgumentException("Unknown usage resource [{$resource}]."),
        };
    }

    public function limitFor(string $resource): ?int
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return null;
        }

        return match ($resource) {
            'user' => $plan->user_limit,
            'doctor' => $plan->doctor_limit,
            'patient' => $plan->patient_limit,
            'storage' => $plan->storage_limit_mb,
            default => throw new \InvalidArgumentException("Unknown usage resource [{$resource}]."),
        };
    }

    /** Whole-database size — matches Hospital::tenantMetrics()'s own existing storage figure exactly, not a second computation. */
    private function storageUsageMb(): float
    {
        try {
            return (float) (DB::select(
                'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()'
            )[0]->size ?? 0);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    
    public function assertCanCreate(string $resource, ?User $actor = null): void
    {
        $actor ??= auth()->user();
        $summary = $this->resourceSummary($resource);

        if ($summary['limit'] === null || $summary['used'] < $summary['limit']) {
            return;
        }

        if ($actor?->hasRole('super_admin')) {
            ActivityLog::log(
                'limit_bypassed',
                self::MODULE,
                "{$actor->name} created a new " . self::label($resource) . " despite the plan limit ({$summary['used']}/{$summary['limit']}) being reached — allowed because the acting account is Super Admin"
            );

            return;
        }

        ActivityLog::log(
            'limit_blocked',
            self::MODULE,
            ($actor?->name ?? 'A user') . ' was blocked from creating a new ' . self::label($resource) . " — plan limit reached ({$summary['used']}/{$summary['limit']})"
        );

        throw new \InvalidArgumentException(
            "You've reached your plan's " . self::label($resource) . " limit ({$summary['used']}/{$summary['limit']}). Upgrade your subscription plan to add more."
        );
    }

    public static function label(string $resource): string
    {
        return match ($resource) {
            'user' => 'user',
            'doctor' => 'doctor',
            'patient' => 'patient',
            'storage' => 'storage',
            default => $resource,
        };
    }
}
