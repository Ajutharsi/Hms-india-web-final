<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\HospitalSetting;
use App\Models\PlatformModule;
use App\Models\Plan;


class FeatureManagementService
{
    private const MODULE = 'feature_management';

    /**
     * Not cached: `platform_modules` is a small (~24-row), rarely-changing
     * reference table with no exposed create/edit UI in this module, so a
     * single unfiltered SELECT here costs nothing — and skips an entire
     * class of stale-cache bugs a TTL'd cache would risk for essentially
     * zero real performance gain.
     *
     * @return \Illuminate\Support\Collection<int, PlatformModule>
     */
    public function catalogModules()
    {
        return PlatformModule::orderBy('sort_order')->get();
    }

    public function featuresEnabledMap(): array
    {
        return HospitalSetting::get()->features_enabled ?? HospitalSetting::DEFAULT_FEATURES;
    }

    public function isEnabled(PlatformModule $module, ?array $featuresEnabled = null): bool
    {
        if ($module->isCore()) {
            return true;
        }

        $featuresEnabled ??= $this->featuresEnabledMap();

        if (array_key_exists($module->key, $featuresEnabled)) {
            return (bool) $featuresEnabled[$module->key];
        }

        $dependsOn = $module->depends_on ?? [];
        if (empty($dependsOn)) {
            return true;
        }

        $byKey = $this->catalogModules()->keyBy('key');
        foreach ($dependsOn as $depKey) {
            $dependency = $byKey->get($depKey);
            if (! $dependency || ! $this->isEnabled($dependency, $featuresEnabled)) {
                return false;
            }
        }

        return true;
    }

    public function dependentsOf(PlatformModule $module)
    {
        return $this->catalogModules()->filter(fn ($m) => in_array($module->key, $m->depends_on ?? []));
    }

  

    public function dashboardStats(): array
    {
        $modules = $this->catalogModules();
        $featuresEnabled = $this->featuresEnabledMap();
        $enabledCount = 0;
        $disabledCount = 0;

        foreach ($modules as $module) {
            if ($this->isEnabled($module, $featuresEnabled)) {
                $enabledCount++;
            } else {
                $disabledCount++;
            }
        }

        return [
            'total' => $modules->count(),
            'enabled' => $enabledCount,
            'disabled' => $disabledCount,
            'core' => $modules->filter(fn ($m) => $m->tier === 'core')->count(),
            'optional' => $modules->filter(fn ($m) => $m->tier === 'optional')->count(),
            'experimental' => $modules->filter(fn ($m) => $m->tier === 'experimental')->count(),
            'groups' => $modules->pluck('category')->unique()->count(),
        ];
    }

    
    /** @return array{recordsTotal:int,recordsFiltered:int,data:array} */
    public function catalog(array $filters): array
    {
        $modules = $this->catalogModules();
        $featuresEnabled = $this->featuresEnabledMap();
        $recordsTotal = $modules->count();

        $filtered = $modules->filter(function (PlatformModule $m) use ($filters, $featuresEnabled) {
            if (! empty($filters['search'])) {
                $needle = mb_strtolower($filters['search']);
                if (! str_contains(mb_strtolower($m->name), $needle) && ! str_contains(mb_strtolower($m->key), $needle)) {
                    return false;
                }
            }
            if (! empty($filters['category']) && $m->category !== $filters['category']) {
                return false;
            }
            if (! empty($filters['tier']) && $m->tier !== $filters['tier']) {
                return false;
            }
            if (! empty($filters['status'])) {
                $enabled = $this->isEnabled($m, $featuresEnabled);
                if ($filters['status'] === 'enabled' && ! $enabled) {
                    return false;
                }
                if ($filters['status'] === 'disabled' && $enabled) {
                    return false;
                }
            }

            return true;
        })->values();

        $recordsFiltered = $filtered->count();

        $rows = $filtered->map(fn (PlatformModule $m) => $this->toRow($m, $featuresEnabled));

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->values()->all(),
        ];
    }

    private function toRow(PlatformModule $m, array $featuresEnabled): array
    {
        return [
            'id' => $m->id,
            'key' => $m->key,
            'name' => $m->name,
            'description' => $m->description,
            'category' => $m->category,
            'tier' => $m->tier,
            'icon' => $m->icon,
            'color' => $m->color,
            'is_core' => $m->isCore(),
            'enabled' => $this->isEnabled($m, $featuresEnabled),
            'independently_toggleable' => $m->isIndependentlyToggleable($featuresEnabled),
            'depends_on' => $m->depends_on ?? [],
            'created_at' => $m->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $m->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    

    public function groups(): array
    {
        $modules = $this->catalogModules();
        $featuresEnabled = $this->featuresEnabledMap();

        return $modules->groupBy('category')->map(function ($group, $category) use ($featuresEnabled) {
            return [
                'category' => $category,
                'total' => $group->count(),
                'enabled' => $group->filter(fn ($m) => $this->isEnabled($m, $featuresEnabled))->count(),
                'modules' => $group->map(fn ($m) => $this->toRow($m, $featuresEnabled))->values()->all(),
            ];
        })->values()->all();
    }

    
    public function enable(PlatformModule $module): void
    {
        if ($module->isCore()) {
            throw new \InvalidArgumentException("\"{$module->name}\" is a core module and is always enabled.");
        }

        $featuresEnabled = $this->featuresEnabledMap();

        if (! array_key_exists($module->key, $featuresEnabled)) {
            throw new \InvalidArgumentException("\"{$module->name}\" has no independent toggle — its availability follows its dependency.");
        }

        foreach ($module->depends_on ?? [] as $depKey) {
            $dependency = $this->catalogModules()->firstWhere('key', $depKey);
            if ($dependency && ! $this->isEnabled($dependency, $featuresEnabled)) {
                throw new \InvalidArgumentException("Cannot enable \"{$module->name}\" — its dependency \"{$dependency->name}\" is currently disabled.");
            }
        }

        $previous = $featuresEnabled[$module->key] ?? null;
        $featuresEnabled[$module->key] = true;
        $this->writeFeaturesEnabled($featuresEnabled);

        ActivityLog::log('feature_enabled', self::MODULE, auth()->user()->name . " enabled the \"{$module->name}\" feature", $module, ['enabled' => $previous], ['enabled' => true]);
    }

    public function disable(PlatformModule $module): void
    {
        if ($module->isCore()) {
            throw new \InvalidArgumentException("\"{$module->name}\" is a core module required for system stability and cannot be disabled.");
        }

        $featuresEnabled = $this->featuresEnabledMap();

        if (! array_key_exists($module->key, $featuresEnabled)) {
            throw new \InvalidArgumentException("\"{$module->name}\" has no independent toggle — disable its dependency instead.");
        }

       
        $blockingDependents = $this->dependentsOf($module)->filter(
            fn ($dependent) => $dependent->isIndependentlyToggleable($featuresEnabled) && $this->isEnabled($dependent, $featuresEnabled)
        );
        if ($blockingDependents->isNotEmpty()) {
            $names = $blockingDependents->pluck('name')->join(', ');
            throw new \InvalidArgumentException("Cannot disable \"{$module->name}\" — the following enabled feature(s) depend on it: {$names}.");
        }

        $previous = $featuresEnabled[$module->key] ?? null;
        $featuresEnabled[$module->key] = false;
        $this->writeFeaturesEnabled($featuresEnabled);

        ActivityLog::log('feature_disabled', self::MODULE, auth()->user()->name . " disabled the \"{$module->name}\" feature", $module, ['enabled' => $previous], ['enabled' => false]);
    }

    private function writeFeaturesEnabled(array $featuresEnabled): void
    {
        $settings = HospitalSetting::first();
        if (! $settings) {
            $settings = HospitalSetting::get();
            $settings->features_enabled = $featuresEnabled;
            $settings->save();

            return;
        }

        $settings->update(['features_enabled' => $featuresEnabled]);
    }

   
    public function planMapping(): array
    {
        $modulesByKey = $this->catalogModules()->keyBy('key');

        return Plan::orderBy('name')->get()->map(function (Plan $plan) use ($modulesByKey) {
            $keys = $plan->modules ?? [];

            return [
                'id' => $plan->id,
                'key' => $plan->key,
                'name' => $plan->name,
                'status' => $plan->status,
                'modules' => collect($keys)->map(fn ($key) => [
                    'key' => $key,
                    'name' => $modulesByKey->get($key)?->name ?? $key,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /** Assignable modules are independently-toggleable, non-core catalog entries. */
    public function assignableModuleKeys(): array
    {
        return $this->catalogModules()
            ->reject(fn (PlatformModule $m) => $m->isCore())
            ->pluck('key')
            ->values()
            ->all();
    }

    public function updatePlanModules(Plan $plan, array $moduleKeys): void
    {
        $valid = $this->assignableModuleKeys();
        $invalid = array_diff($moduleKeys, $valid);
        if (! empty($invalid)) {
            throw new \InvalidArgumentException('Unknown or non-assignable feature key(s): ' . implode(', ', $invalid));
        }

        $previous = $plan->modules ?? [];
        $plan->update(['modules' => array_values($moduleKeys)]);

        ActivityLog::log(
            'plan_modules_updated',
            self::MODULE,
            auth()->user()->name . " updated feature assignment for plan \"{$plan->name}\"",
            $plan,
            ['modules' => $previous],
            ['modules' => array_values($moduleKeys)]
        );
    }

   
    public function hospitalVisibility(PlatformModule $module): array
    {
        $hospitals = Hospital::with('currentSubscription.planCatalog')->orderBy('name')->get();
        $overridable = $module->isIndependentlyToggleable($this->featuresEnabledMap());

        $rows = $hospitals->map(function (Hospital $hospital) use ($module, $overridable) {
            $subscription = $hospital->currentSubscription;
            $planAllows = $subscription ? $subscription->isModuleAllowed($module->key) : false;

            $override = null;
            if ($overridable) {
                $settingsRow = HospitalSetting::where('hospital_id', $hospital->id)->first();
                if ($settingsRow && array_key_exists($module->key, $settingsRow->features_enabled ?? [])) {
                    $override = (bool) $settingsRow->features_enabled[$module->key];
                }
            }

            $allowed = $planAllows && ($override ?? true);

            return [
                'hospital_id' => $hospital->id,
                'hospital_name' => $hospital->name,
                'plan_name' => $subscription?->planCatalog?->name ?? $subscription?->plan,
                'has_subscription' => $subscription !== null,
                'plan_allows' => $planAllows,
                'override' => $override,
                'allowed' => $allowed,
                'overridable' => $overridable && $planAllows,
            ];
        });

        return [
            'assigned' => $rows->where('allowed', true)->values()->all(),
            'restricted' => $rows->where('allowed', false)->values()->all(),
        ];
    }

   
    public function setHospitalOverride(Hospital $hospital, PlatformModule $module, ?bool $value): void
    {
        if (! $module->isIndependentlyToggleable($this->featuresEnabledMap())) {
            throw new \InvalidArgumentException("\"{$module->name}\" has no independent toggle and cannot be overridden per hospital.");
        }

        $settings = HospitalSetting::forHospital($hospital->id);
        $features = $settings->features_enabled ?? HospitalSetting::DEFAULT_FEATURES;
        $previous = $features[$module->key] ?? null;

        if ($value === null) {
            unset($features[$module->key]);
        } else {
            $features[$module->key] = $value;
        }

        $settings->features_enabled = $features;
        $settings->hospital_id = $hospital->id;
        $settings->hospital_name ??= $hospital->name;
        $settings->save();

        $description = $value === null
            ? auth()->user()->name . " cleared the \"{$module->name}\" override for {$hospital->name} (inherits plan)"
            : auth()->user()->name . " " . ($value ? 'enabled' : 'disabled') . " \"{$module->name}\" specifically for {$hospital->name}";

        ActivityLog::log(
            'feature_hospital_override_changed',
            self::MODULE,
            $description,
            $module,
            ['override' => $previous],
            ['override' => $value]
        );
    }
}
