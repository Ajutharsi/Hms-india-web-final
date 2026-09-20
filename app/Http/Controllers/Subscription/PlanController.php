<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * Database-backed subscription plan catalog — Super Admin plan management.
 * See SUPER_ADMIN_SUBSCRIPTION_MANAGEMENT_REPORT.md.
 */
class PlanController extends Controller
{
    public function index()
    {
        return view('subscription.plans.index', [
            'plans' => Plan::withCount('subscriptions')->orderBy('price')->get(),
        ]);
    }

    private function validationRules(?int $planId = null): array
    {
        return [
            'key'              => 'required|alpha_dash|max:50|unique:plans,key' . ($planId ? ",{$planId}" : ''),
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'required|numeric|min:0',
            'billing_cycle'    => 'required|in:monthly,yearly',
            'trial_days'       => 'nullable|integer|min:0',
            'user_limit'       => 'nullable|integer|min:0',
            'doctor_limit'     => 'nullable|integer|min:0',
            'patient_limit'    => 'nullable|integer|min:0',
            'storage_limit_mb' => 'nullable|integer|min:0',
            'modules'          => 'nullable|array',
            'status'           => 'required|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules(), $this->validationMessages());
        $validated['modules'] = $request->input('modules', []);

        $plan = Plan::create($validated);

        ActivityLog::log('created', 'plan', "Plan '{$plan->name}' created", $plan);

        return redirect()->route('subscription.plans.index')->with('success', "{$plan->name} plan created!");
    }

    public function getJson(Plan $plan)
    {
        return response()->json($plan);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate($this->validationRules($plan->id), $this->validationMessages());
        $validated['modules'] = $request->input('modules', []);

        $plan->update($validated);

        ActivityLog::log('updated', 'plan', "Plan '{$plan->name}' updated", $plan);

        return redirect()->route('subscription.plans.index')->with('success', "{$plan->name} plan updated!");
    }

    /** Quick Activate/Deactivate — no full edit form required. */
    public function setStatus(Request $request, Plan $plan)
    {
        $request->validate(['status' => 'required|in:active,inactive']);

        $plan->update(['status' => $request->status]);

        ActivityLog::log($request->status, 'plan', "Plan '{$plan->name}' set to {$request->status}", $plan);

        return response()->json(['success' => true, 'status' => $plan->status]);
    }

    /** Archive (soft delete), consistent with Hospital Management's pattern. */
    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'error' => 'Cannot archive a plan that still has active subscriptions. Move them to another plan first.',
            ], 422);
        }

        ActivityLog::log('archived', 'plan', "Plan '{$plan->name}' archived", $plan);
        $plan->delete();

        return response()->json(['success' => true]);
    }

    public function restore($id)
    {
        $plan = Plan::onlyTrashed()->findOrFail($id);
        $plan->restore();

        ActivityLog::log('restored', 'plan', "Plan '{$plan->name}' restored from the archive", $plan);

        return response()->json(['success' => true]);
    }
}
