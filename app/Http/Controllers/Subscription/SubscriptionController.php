<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Super Admin subscription management — hospital subscriptions, renewals,
 * plan changes, cancellation/reactivation. See
 * SUPER_ADMIN_SUBSCRIPTION_MANAGEMENT_REPORT.md. Plan catalog management
 * itself lives in PlanController.
 */
class SubscriptionController extends Controller
{
    public function index()
    {
        $byStatus = Subscription::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('subscription.index', [
            'statusFilter' => request('status'),
            'planFilter'   => request('plan_id'),
            'expiryFilter' => request('expiry'),
            'plans'        => Plan::active()->orderBy('price')->get(),
            'hospitals'    => Hospital::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'stats'        => [
                'total'     => (int) $byStatus->sum(),
                'active'    => (int) ($byStatus['active'] ?? 0),
                'expiring'  => Subscription::whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
                'cancelled' => (int) ($byStatus['cancelled'] ?? 0),
            ],
        ]);
    }

    public function data(Request $request)
    {
        $query = $request->get('status') === 'archived'
            ? Subscription::onlyTrashed()
            : Subscription::query();

        $query->with(['hospital', 'planCatalog'])->latest();

        if ($request->filled('status') && $request->get('status') !== 'archived') {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->get('plan_id'));
        }

        $expiry = $request->get('expiry');
        if ($expiry === '7') {
            $query->whereBetween('expires_at', [now(), now()->addDays(7)]);
        } elseif ($expiry === '30') {
            $query->whereBetween('expires_at', [now(), now()->addDays(30)]);
        } elseif ($expiry === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        return DataTables::of($query)
            ->addColumn('hospital_display', fn($s) => $s->hospital?->name ?? $s->hospital_name)
            ->addColumn('plan_display', fn($s) => $s->planCatalog?->name ?? ucfirst(str_replace('_', ' ', $s->plan)))
            ->addColumn('status_badge', function ($s) {
                if ($s->trashed()) return '<span class="badge bg-label-secondary">Archived</span>';
                $colors = ['active' => 'success', 'expired' => 'danger', 'suspended' => 'warning', 'cancelled' => 'secondary'];
                return '<span class="badge bg-label-' . ($colors[$s->status] ?? 'secondary') . ' text-capitalize">' . $s->status . '</span>';
            })
            ->addColumn('payment_status_badge', function ($s) {
                $colors = ['paid' => 'success', 'unpaid' => 'warning', 'overdue' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$s->payment_status] ?? 'secondary') . ' text-capitalize">' . $s->payment_status . '</span>';
            })
            ->addColumn('start_display', fn($s) => optional($s->trial_started_at ?? $s->created_at)->format('d M Y'))
            ->addColumn('expiry_display', fn($s) => optional($s->expires_at)->format('d M Y') ?? '—')
            ->addColumn('renewal_display', fn($s) => optional($s->last_renewed_at)->format('d M Y') ?? '—')
            ->addColumn('billing_cycle_display', fn($s) => ucfirst($s->billing_cycle ?? 'monthly'))
            ->addColumn('amount_display', fn($s) => ($s->currency ?? 'LKR') . ' ' . number_format($s->total_amount ?? $s->calculateTotal(), 2))
            ->addColumn('actions', fn($s) => $this->actionButtons($s))
            ->rawColumns(['status_badge', 'payment_status_badge', 'actions'])
            ->make(true);
    }

    private function actionButtons(Subscription $s): string
    {
        $id = $s->id;

        if ($s->trashed()) {
            return '<div class="d-flex gap-2">
                <button onclick="restoreSubscription(' . $id . ')" class="btn btn-sm btn-icon btn-label-success" title="Restore"><i class="icon-base ti tabler-refresh"></i></button>
            </div>';
        }

        $reactivate = $s->status === 'cancelled'
            ? '<li><a class="dropdown-item" href="javascript:void(0)" onclick="openReactivateModal(' . $id . ',\'' . e($s->hospital_name) . '\')"><i class="icon-base ti tabler-rotate me-2"></i>Reactivate</a></li>'
            : '';
        $cancel = $s->status !== 'cancelled'
            ? '<li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="cancelSubscription(' . $id . ')"><i class="icon-base ti tabler-ban me-2"></i>Cancel</a></li>'
            : '';

        return '
            <div class="d-flex gap-2">
                <a href="/subscription/' . $id . '" class="btn btn-sm btn-icon btn-label-info" title="View"><i class="icon-base ti tabler-eye"></i></a>
                <button onclick="openExtendModal(' . $id . ',\'' . e($s->hospital_name) . '\')" class="btn btn-sm btn-icon btn-label-primary" title="Extend"><i class="icon-base ti tabler-calendar-plus"></i></button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-label-secondary" data-bs-toggle="dropdown" title="More actions"><i class="icon-base ti tabler-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openChangePlanModal(' . $id . ',\'' . e($s->hospital_name) . '\',' . ($s->plan_id ?? 'null') . ')"><i class="icon-base ti tabler-replace me-2"></i>Change Plan</a></li>
                        ' . $reactivate . $cancel . '
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="archiveSubscription(' . $id . ')"><i class="icon-base ti tabler-archive me-2"></i>Archive</a></li>
                    </ul>
                </div>
            </div>
        ';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hospital_id'    => 'required|exists:hospitals,id',
            'plan_id'        => 'required|exists:plans,id',
            'status'         => 'required|in:active,expired,suspended,cancelled',
            'payment_status' => 'nullable|in:unpaid,paid,overdue',
            'billing_cycle'  => 'nullable|in:monthly,yearly',
            'currency'       => 'nullable|string|max:10',
            'expires_at'     => 'nullable|date',
            'notes'          => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $hospital = Hospital::findOrFail($validated['hospital_id']);
        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription = Subscription::create([
            'hospital_id'      => $hospital->id,
            'hospital_name'    => $hospital->name,
            'hospital_email'   => $hospital->email,
            'hospital_phone'   => $hospital->phone,
            'plan'             => $plan->key,
            'plan_id'          => $plan->id,
            'allowed_modules'  => $plan->modules,
            'billing_cycle'    => $validated['billing_cycle'] ?? $plan->billing_cycle,
            'currency'         => $validated['currency'] ?? 'LKR',
            'status'           => $validated['status'],
            'payment_status'   => $validated['payment_status'] ?? 'unpaid',
            'trial_started_at' => $plan->trial_days ? now() : null,
            'expires_at'       => $validated['expires_at'] ?? ($plan->trial_days ? now()->addDays($plan->trial_days) : now()->addMonth()),
            'notes'            => $validated['notes'] ?? null,
            'total_amount'     => $plan->price,
        ]);

        $subscription->syncToSettings();

        ActivityLog::log('created', 'subscription', "{$hospital->name} subscribed to the {$plan->name} plan", $subscription);

        return redirect()->route('subscription.index')->with('success', 'Subscription created successfully!');
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['hospital', 'planCatalog']);

        $auditEvents = ActivityLog::with('user')
            ->where('model_type', Subscription::class)
            ->where('model_id', $subscription->id)
            ->latest('created_at')->take(50)->get();

        return view('subscription.show', compact('subscription', 'auditEvents'));
    }

    public function getJson(Subscription $subscription)
    {
        return response()->json($subscription);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'status'         => 'required|in:active,expired,suspended,cancelled',
            'payment_status' => 'nullable|in:unpaid,paid,overdue',
            'billing_cycle'  => 'nullable|in:monthly,yearly',
            'currency'       => 'nullable|string|max:10',
            'expires_at'     => 'nullable|date',
            'notes'          => 'nullable|string|max:1000',
        ], $this->validationMessages());

        // Reactivating a cancelled subscription must go through the explicit
        // reactivate() action (with its own confirmation + required new
        // expiry date), never as a side effect of a general edit — see
        // SUPER_ADMIN_SUBSCRIPTION_MANAGEMENT_REPORT.md §Security.
        if ($subscription->status === 'cancelled' && $validated['status'] !== 'cancelled') {
            return redirect()->back()
                ->withErrors(['status' => 'Cannot reactivate a cancelled subscription from Edit — use the Reactivate action instead.'])
                ->withInput();
        }

        $oldStatus = $subscription->status;
        $oldPlanName = $subscription->planCatalog?->name ?? $subscription->plan;
        $plan = Plan::findOrFail($validated['plan_id']);
        $planChanged = $subscription->plan_id !== $plan->id;

        $subscription->update([
            'plan'            => $plan->key,
            'plan_id'         => $plan->id,
            'allowed_modules' => $plan->modules,
            'billing_cycle'   => $validated['billing_cycle'] ?? $plan->billing_cycle,
            'currency'        => $validated['currency'] ?? $subscription->currency,
            'status'          => $validated['status'],
            'payment_status'  => $validated['payment_status'] ?? $subscription->payment_status,
            'expires_at'      => $validated['expires_at'] ?? $subscription->expires_at,
            'notes'           => $validated['notes'] ?? $subscription->notes,
            // $plan->price directly, not calculateTotal() — that derives the
            // price via the planCatalog relation, which Eloquent already
            // cached (holding the OLD plan) from the $oldPlanName lookup
            // above, and wouldn't pick up the just-changed plan_id.
            'total_amount'    => $plan->price,
        ]);
        $subscription->syncToSettings();

        if ($oldStatus !== $validated['status']) {
            ActivityLog::log($validated['status'], 'subscription', "{$subscription->hospital_name} subscription status changed from " . ucfirst($oldStatus) . ' to ' . ucfirst($validated['status']), $subscription);
        }
        if ($planChanged) {
            ActivityLog::log('plan_changed', 'subscription', "{$subscription->hospital_name} subscription changed from {$oldPlanName} to {$plan->name}", $subscription, ['plan' => $oldPlanName], ['plan' => $plan->name]);
        }

        return redirect()->route('subscription.index')->with('success', 'Subscription updated successfully!');
    }

    /** Dedicated, lightweight Change Plan action (vs. the full edit form). */
    public function changePlan(Request $request, Subscription $subscription)
    {
        $validated = $request->validate(['plan_id' => 'required|exists:plans,id']);
        $newPlan = Plan::findOrFail($validated['plan_id']);
        $oldPlanName = $subscription->planCatalog?->name ?? $subscription->plan;

        $subscription->update([
            'plan'            => $newPlan->key,
            'plan_id'         => $newPlan->id,
            'allowed_modules' => $newPlan->modules,
            'billing_cycle'   => $newPlan->billing_cycle,
            'total_amount'    => $newPlan->price,
        ]);
        $subscription->syncToSettings();

        ActivityLog::log('plan_changed', 'subscription', "{$subscription->hospital_name} subscription changed from {$oldPlanName} to {$newPlan->name}", $subscription, ['plan' => $oldPlanName], ['plan' => $newPlan->name]);

        return redirect()->back()->with('success', "Plan changed to {$newPlan->name}!");
    }

    public function extend(Request $request, Subscription $subscription)
    {
        $request->validate(['expires_at' => 'required|date|after:today']);

        if ($subscription->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Cannot extend a cancelled subscription — reactivate it via the Reactivate action first.');
        }

        $subscription->update([
            'expires_at'      => $request->expires_at,
            'status'          => 'active',
            'last_renewed_at' => now(),
        ]);
        $subscription->syncToSettings();

        ActivityLog::log('extended', 'subscription', "{$subscription->hospital_name} subscription extended to {$subscription->expires_at->format('d M Y')}", $subscription);

        // redirect()->back() rather than a hardcoded route so this works
        // correctly both from its own Subscription Management page and from
        // the Hospital Management "Extend Subscription" action.
        return redirect()->back()
            ->with('success', 'Subscription extended successfully!');
    }

    /**
     * Explicit, deliberate cancellation — distinct from an edit-form status
     * change. Returns JSON (not a redirect) since it's triggered as a quick
     * one-click action (fetch + confirm()) from the subscription list,
     * consistent with destroy()/restore()/setStatus()-style actions
     * elsewhere in this app, rather than a full form submission.
     */
    public function cancel(Subscription $subscription)
    {
        if ($subscription->status === 'cancelled') {
            return response()->json(['error' => 'This subscription is already cancelled.'], 422);
        }

        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $subscription->syncToSettings();

        ActivityLog::log('cancelled', 'subscription', "{$subscription->hospital_name} subscription cancelled", $subscription);

        return response()->json(['success' => true]);
    }

    /**
     * Explicit, deliberate reactivation — the ONLY path back to 'active'
     * from 'cancelled' (update() and extend() both refuse to do this as a
     * side effect). Requires a fresh expiry date, same as a genuine new
     * signup would.
     */
    public function reactivate(Request $request, Subscription $subscription)
    {
        if ($subscription->status !== 'cancelled') {
            return redirect()->back()->with('error', 'Only a cancelled subscription can be reactivated.');
        }

        $validated = $request->validate(['expires_at' => 'required|date|after:today']);

        $subscription->update([
            'status'          => 'active',
            'expires_at'      => $validated['expires_at'],
            'last_renewed_at' => now(),
            'cancelled_at'    => null,
        ]);
        $subscription->syncToSettings();

        ActivityLog::log('reactivated', 'subscription', "{$subscription->hospital_name} subscription reactivated, extended to {$subscription->expires_at->format('d M Y')}", $subscription);

        return redirect()->back()->with('success', 'Subscription reactivated!');
    }

    /** Archive (soft delete). Must be cancelled/expired first — archiving an active subscription silently would hide a paying customer. */
    public function destroy(Subscription $subscription)
    {
        if (in_array($subscription->status, ['active', 'suspended'])) {
            return response()->json([
                'error' => 'Cancel this subscription before archiving it.',
            ], 422);
        }

        $hospital = $subscription->hospital;
        $isOnlySubscriptionForDefaultHospital = $hospital?->is_default
            && $hospital->subscriptions()->count() === 1;

        if ($isOnlySubscriptionForDefaultHospital) {
            return response()->json([
                'error' => "Cannot archive the only subscription for the platform's default hospital — this would lock out all non-super-admin users. Create a replacement subscription first.",
            ], 422);
        }

        ActivityLog::log('archived', 'subscription', "{$subscription->hospital_name} subscription archived", $subscription);
        $subscription->delete();

        return response()->json(['success' => true]);
    }

    public function restore($id)
    {
        $subscription = Subscription::onlyTrashed()->findOrFail($id);
        $subscription->restore();

        ActivityLog::log('restored', 'subscription', "{$subscription->hospital_name} subscription restored from the archive", $subscription);

        return response()->json(['success' => true]);
    }

    public function current()
    {
        $subscription = Subscription::resolveCurrent();
        return view('subscription.current', compact('subscription'));
    }

    public function expired()
    {
        return view('subscription.expired');
    }

    public function suspended()
    {
        return view('subscription.suspended');
    }
}
