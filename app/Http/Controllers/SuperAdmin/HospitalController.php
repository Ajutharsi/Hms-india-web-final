<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

/**
 * Manages the platform's tenant registry (the hospitals that subscribe to
 * this software) — the Super Admin's own "customers", not to be confused
 * with any single hospital's clinical data (patients/doctors/appointments).
 *
 * See SUPER_ADMIN_HOSPITAL_MANAGEMENT_REPORT.md for the redesign notes,
 * including the deliberate scope boundary around per-hospital usage data
 * (Total Users/Doctors/Patients/Storage/Login history are only real for the
 * platform's default hospital — see Hospital::tenantMetrics()).
 */
class HospitalController extends Controller
{
    public function __construct(
        private readonly DashboardService $stats,
        private readonly SubscriptionLimitService $limits,
    ) {
    }

    public function index(Request $request)
    {
        return view('super-admin.hospitals.index', [
            'statusFilter'  => $request->get('status'),
            'planFilter'    => $request->get('plan'),
            'countryFilter' => $request->get('country'),
            'expiryFilter'  => $request->get('expiry'),
            'countries'     => Hospital::whereNotNull('country')->where('country', '!=', '')
                ->distinct()->orderBy('country')->pluck('country'),
            'platformStats' => $this->stats->platformStats(),
        ]);
    }

    public function data(Request $request)
    {
        $query = $request->get('status') === 'archived'
            ? Hospital::onlyTrashed()
            : Hospital::query();

        // No default ->orderBy() here — Yajra appends the client-requested
        // sort rather than replacing one, so a server-side ->latest() would
        // silently dominate and make column sorting from the UI a no-op
        // (found via testing — see SUPER_ADMIN_HOSPITAL_MANAGEMENT_REPORT.md).
        // The DataTable's own default order (index.blade.php) covers the
        // "what do you see with no sort applied" case instead.
        $query->with('currentSubscription');

        if ($request->filled('status') && $request->get('status') !== 'archived') {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('plan')) {
            $query->where('plan', $request->get('plan'));
        }
        if ($request->filled('country')) {
            $query->where('country', $request->get('country'));
        }

        $expiry = $request->get('expiry');
        if ($expiry === '7') {
            $query->expiringWithin(7);
        } elseif ($expiry === '30') {
            $query->expiringWithin(30);
        } elseif ($expiry === 'expired') {
            $query->alreadyExpired();
        }
        // Kept for backward compatibility with the Super Admin dashboard's
        // existing "Expiring Subscriptions" KPI card deep link.
        if ($request->get('expiring') === '1') {
            $query->expiringWithin(30);
        }

        return DataTables::of($query)
            ->addColumn('logo', fn($h) => $h->logo_url
                ? '<img src="' . $h->logo_url . '" alt="" class="rounded" style="width:32px;height:32px;object-fit:cover;">'
                : '<span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:600;">' . strtoupper(substr($h->name, 0, 1)) . '</span>')
            ->addColumn('status_badge', function ($h) {
                if ($h->trashed()) {
                    return '<span class="badge bg-label-secondary">Archived</span>';
                }
                $colors = ['trial' => 'info', 'active' => 'success', 'suspended' => 'warning', 'cancelled' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$h->status] ?? 'secondary') . '">' . ucfirst($h->status) . '</span>';
            })
            ->addColumn('plan_badge', fn($h) => '<span class="badge bg-label-primary text-capitalize">' . $h->plan . '</span>')
            ->addColumn('sub_status_badge', function ($h) {
                $status = $h->currentSubscription->status ?? $h->status;
                $colors = ['active' => 'success', 'trial' => 'info', 'suspended' => 'warning', 'cancelled' => 'danger', 'expired' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$status] ?? 'secondary') . '">' . ucfirst($status) . '</span>';
            })
            ->addColumn('expiry_display', function ($h) {
                $date = $h->currentSubscription->expires_at ?? $h->subscription_ends_at;
                return $date ? $date->format('d M Y') : '—';
            })
            ->addColumn('fee_display', fn($h) => config('hospital.currency', 'LKR') . ' ' . number_format($h->monthly_fee, 2))
            ->addColumn('users_display', fn($h) => $h->tenantMetrics()['totalUsers'] ?? '—')
            ->addColumn('doctors_display', fn($h) => $h->tenantMetrics()['totalDoctors'] ?? '—')
            ->addColumn('patients_display', fn($h) => $h->tenantMetrics()['totalPatients'] ?? '—')
            ->addColumn('storage_display', fn($h) => $h->tenantMetrics()['storageUsage'] ?? '—')
            ->addColumn('last_login_display', function ($h) {
                if (! $h->is_default) return '—';
                $latest = User::whereNotNull('last_login')->latest('last_login')->first();
                return $latest ? $latest->last_login->diffForHumans() : 'Never';
            })
            ->addColumn('actions', fn($h) => $this->actionButtons($h))
            ->rawColumns(['logo', 'status_badge', 'plan_badge', 'sub_status_badge', 'actions'])
            ->make(true);
    }

    private function actionButtons(Hospital $h): string
    {
        $id = $h->id;

        if ($h->trashed()) {
            return '
                <div class="d-flex gap-2">
                    <a href="/super-admin/hospitals/' . $id . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                        <i class="icon-base ti tabler-eye"></i>
                    </a>
                    <button onclick="restoreHospital(' . $id . ')" class="btn btn-sm btn-icon btn-label-success" title="Restore">
                        <i class="icon-base ti tabler-refresh"></i>
                    </button>
                </div>
            ';
        }

        $statusAction = $h->status === 'suspended'
            ? '<li><a class="dropdown-item" href="javascript:void(0)" onclick="setHospitalStatus(' . $id . ',\'active\')"><i class="icon-base ti tabler-player-play me-2"></i>Activate</a></li>'
            : '<li><a class="dropdown-item" href="javascript:void(0)" onclick="setHospitalStatus(' . $id . ',\'suspended\')"><i class="icon-base ti tabler-player-pause me-2"></i>Suspend</a></li>';

        $extend = $h->currentSubscription
            ? '<li><a class="dropdown-item" href="javascript:void(0)" onclick="openExtendModal(' . $h->currentSubscription->id . ',\'' . e($h->name) . '\')"><i class="icon-base ti tabler-calendar-plus me-2"></i>Extend Subscription</a></li>'
            : '';

        return '
            <div class="d-flex gap-2">
                <a href="/super-admin/hospitals/' . $id . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                    <i class="icon-base ti tabler-eye"></i>
                </a>
                <button onclick="editHospital(' . $id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                    <i class="icon-base ti tabler-edit"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-label-secondary" data-bs-toggle="dropdown" title="More actions">
                        <i class="icon-base ti tabler-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        ' . $statusAction . '
                        ' . $extend . '
                        <li><a class="dropdown-item" href="/super-admin/hospitals/' . $id . '#subscription"><i class="icon-base ti tabler-crown me-2"></i>View Subscription</a></li>
                        <li><a class="dropdown-item" href="/super-admin/hospitals/' . $id . '#usage"><i class="icon-base ti tabler-chart-bar me-2"></i>View Usage</a></li>
                        <li><a class="dropdown-item" href="/super-admin/hospitals/' . $id . '#audit"><i class="icon-base ti tabler-history me-2"></i>View Audit Logs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="archiveHospital(' . $id . ')"><i class="icon-base ti tabler-archive me-2"></i>Archive</a></li>
                    </ul>
                </div>
            </div>
        ';
    }

    private function validationRules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:20',
            'city'           => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:255',
            'status'         => 'required|in:trial,active,suspended,cancelled',
            'plan'           => 'required|in:basic,professional,enterprise',
            'monthly_fee'    => 'required|numeric|min:0',
            'trial_ends_at'          => 'nullable|date',
            'subscription_starts_at' => 'nullable|date',
            'subscription_ends_at'   => 'nullable|date',
            'notes'          => 'nullable|string',
            'logo'           => 'nullable|image|max:2048',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules(), $this->validationMessages());

        $validated['logo_path'] = $request->hasFile('logo') ? $request->file('logo')->store('hospitals', 'public') : null;
        unset($validated['logo']);

        $hospital = Hospital::create($validated);

        ActivityLog::log('registered', 'hospital', "{$hospital->name} registered on the platform", $hospital);

        return redirect()->route('super-admin.hospitals.index')
            ->with('success', $hospital->name . ' added successfully! (Code: ' . $hospital->code . ')');
    }

    public function show(Hospital $hospital)
    {
        $recentActivity   = $hospital->activityLogs()->with('user')->latest('created_at')->take(10)->get();
        $fullActivityLog  = $hospital->activityLogs()->with('user')->latest('created_at')->take(50)->get();
        $subscription     = $hospital->currentSubscription;
        $tenantMetrics    = $hospital->tenantMetrics();
        // Plan usage/limits — see SUPER_ADMIN_USAGE_LIMIT_ENFORCEMENT_REPORT.md.
        // Only meaningful for the same hospital tenantMetrics() itself is
        // real for: SubscriptionLimitService counts usage against this
        // single-tenant installation's actual data (Subscription::resolveCurrent()),
        // so showing it under a non-default hospital's page would misleadingly
        // present the default hospital's numbers as if they were this one's.
        $usageSummary     = $hospital->is_default ? $this->limits->usageSummary() : null;
        $recentUsers      = $hospital->is_default
            ? User::with('roles')->latest('last_login')->take(10)->get()
            : collect();
        // Supports the "Login as Hospital Admin" action — see
        // SUPER_ADMIN_IMPERSONATION_REPORT.md.
        $adminUser        = $hospital->adminUser();

        return view('super-admin.hospitals.show', compact(
            'hospital', 'recentActivity', 'fullActivityLog', 'subscription', 'tenantMetrics', 'usageSummary', 'recentUsers', 'adminUser'
        ));
    }

    public function getJson(Hospital $hospital)
    {
        return response()->json($hospital);
    }

    public function update(Request $request, Hospital $hospital)
    {
        $validated = $request->validate($this->validationRules(), $this->validationMessages());

        if ($request->hasFile('logo')) {
            if ($hospital->logo_path) {
                Storage::disk('public')->delete($hospital->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('hospitals', 'public');
        }
        unset($validated['logo']);

        $statusChanged = $hospital->status !== $validated['status'];
        $oldStatus = $hospital->status;

        $hospital->update($validated);

        if ($statusChanged) {
            ActivityLog::log(
                $validated['status'],
                'hospital',
                "{$hospital->name} status changed from " . ucfirst($oldStatus) . ' to ' . ucfirst($validated['status']),
                $hospital
            );
        }

        return redirect()->route('super-admin.hospitals.index')
            ->with('success', $hospital->name . ' updated successfully!');
    }

    /** Quick Activate/Suspend action from the list or detail page — no full edit form required. */
    public function setStatus(Request $request, Hospital $hospital)
    {
        $request->validate(['status' => 'required|in:active,suspended']);

        $oldStatus = $hospital->status;
        $hospital->update(['status' => $request->status]);

        ActivityLog::log(
            $request->status,
            'hospital',
            "{$hospital->name} status changed from " . ucfirst($oldStatus) . ' to ' . ucfirst($request->status),
            $hospital
        );

        return response()->json(['success' => true, 'status' => $hospital->status]);
    }

    /** Archive (soft delete) in place of the previous permanent delete. */
    public function destroy(Hospital $hospital)
    {
        if ($hospital->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'error' => 'Cannot archive a hospital with an active subscription. Cancel or reassign its subscription first.',
            ], 422);
        }

        ActivityLog::log('archived', 'hospital', "{$hospital->name} archived", $hospital);
        $hospital->delete();

        return response()->json(['success' => true]);
    }

    public function restore($id)
    {
        $hospital = Hospital::onlyTrashed()->findOrFail($id);
        $hospital->restore();

        ActivityLog::log('restored', 'hospital', "{$hospital->name} restored from the archive", $hospital);

        return response()->json(['success' => true]);
    }

    /** SaaS subscription revenue report — MRR trend + breakdown by plan, for the Monthly Revenue dashboard KPI/chart. */
    public function revenue()
    {
        $paying = Hospital::whereIn('status', ['active', 'suspended'])->get();

        $currentMrr = Hospital::where('status', 'active')->sum('monthly_fee');

        $byPlan = Hospital::where('status', 'active')
            ->selectRaw('plan, COUNT(*) as hospitals, SUM(monthly_fee) as mrr')
            ->groupBy('plan')
            ->get()
            ->keyBy('plan');

        // MRR trend: for each of the last 6 months, sum the monthly_fee of every
        // hospital whose subscription had already started by that month (a
        // simple but real approximation of "revenue we were collecting then").
        $trend = collect(range(5, 0))->map(function ($monthsAgo) {
            $month = now()->subMonths($monthsAgo);
            $mrr = Hospital::whereIn('status', ['active', 'suspended', 'cancelled'])
                ->whereNotNull('subscription_starts_at')
                ->where('subscription_starts_at', '<=', $month->copy()->endOfMonth())
                ->sum('monthly_fee');

            return ['month' => $month->format('M Y'), 'mrr' => (float) $mrr];
        });

        return view('super-admin.revenue', compact('paying', 'currentMrr', 'byPlan', 'trend'));
    }
}
