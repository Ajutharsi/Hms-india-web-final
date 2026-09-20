<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Plan;
use App\Models\PlatformModule;
use App\Services\FeatureManagementService;
use Illuminate\Http\Request;

/**
 * Super Admin Feature Management Centre — see
 * SUPER_ADMIN_FEATURE_MANAGEMENT_REPORT.md.
 */
class FeatureManagementController extends Controller
{
    public function __construct(private readonly FeatureManagementService $features)
    {
    }

    public function index()
    {
        return view('super-admin.features.index', [
            'stats' => $this->features->dashboardStats(),
        ]);
    }

    public function catalog()
    {
        $modules = $this->features->catalogModules();

        return view('super-admin.features.catalog', [
            'categories' => $modules->pluck('category')->unique()->sort()->values(),
        ]);
    }

    public function catalogData(Request $request)
    {
        return response()->json($this->features->catalog($request->only(['search', 'category', 'tier', 'status'])));
    }

    public function groups()
    {
        return view('super-admin.features.groups', [
            'groups' => $this->features->groups(),
        ]);
    }

    public function show(PlatformModule $module)
    {
        $featuresEnabled = $this->features->featuresEnabledMap();

        return response()->json([
            'module' => [
                'id' => $module->id,
                'key' => $module->key,
                'name' => $module->name,
                'description' => $module->description,
                'category' => $module->category,
                'tier' => $module->tier,
                'is_core' => $module->isCore(),
                'enabled' => $this->features->isEnabled($module, $featuresEnabled),
                'independently_toggleable' => $module->isIndependentlyToggleable($featuresEnabled),
            ],
            'dependsOn' => $this->features->catalogModules()->whereIn('key', $module->depends_on ?? [])->values()->map(fn ($m) => ['key' => $m->key, 'name' => $m->name])->all(),
            'dependents' => $this->features->dependentsOf($module)->values()->map(fn ($m) => ['key' => $m->key, 'name' => $m->name])->all(),
            'hospitals' => $this->features->hospitalVisibility($module),
        ]);
    }

    /** Row-level per-hospital override — see SUPER_ADMIN_FEATURE_MANAGEMENT_MULTI_TENANCY_REPORT.md. */
    public function setHospitalOverride(Request $request, PlatformModule $module, Hospital $hospital)
    {
        $validated = $request->validate([
            'value' => 'required|in:enable,disable,inherit',
        ]);

        $value = match ($validated['value']) {
            'enable' => true,
            'disable' => false,
            'inherit' => null,
        };

        try {
            $this->features->setHospitalOverride($hospital, $module, $value);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function enable(PlatformModule $module)
    {
        try {
            $this->features->enable($module);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "\"{$module->name}\" enabled.");
    }

    public function disable(PlatformModule $module)
    {
        try {
            $this->features->disable($module);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "\"{$module->name}\" disabled.");
    }

    // ==================== Subscription integration ====================

    public function plans()
    {
        return view('super-admin.features.plans', [
            'plans' => $this->features->planMapping(),
            'assignable' => $this->features->catalogModules()->reject(fn ($m) => $m->isCore())->values(),
        ]);
    }

    public function updatePlanModules(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'modules' => 'array',
            'modules.*' => 'string',
        ]);

        try {
            $this->features->updatePlanModules($plan, $validated['modules'] ?? []);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Feature assignment for \"{$plan->name}\" updated.");
    }
}
