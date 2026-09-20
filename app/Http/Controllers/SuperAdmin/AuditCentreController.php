<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\Impersonation;
use App\Models\User;
use App\Services\AuditCentreService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Super Admin Audit & Activity Centre — see SUPER_ADMIN_AUDIT_ACTIVITY_REPORT.md.
 * Reuses the unified `ActivityLog`/`Impersonation` models via
 * `AuditCentreService`; no new audit store. Follows the exact
 * DataTables-list + CSV-streaming/print-view export conventions already
 * established by SupportTicketController/ReportCentreController.
 *
 * Audit logs are read-only from this controller's perspective — there is
 * deliberately no delete/clear/update action anywhere in this class.
 */
class AuditCentreController extends Controller
{
    private const FILTER_KEYS = ['from_date', 'to_date', 'user_id', 'hospital_id', 'role', 'module', 'action', 'status', 'event_type', 'search'];

    public function __construct(private readonly AuditCentreService $audit)
    {
    }

    private function filters(Request $request): array
    {
        return array_filter(
            $request->only(self::FILTER_KEYS),
            fn ($v) => $v !== null && $v !== ''
        );
    }

    private function filterOptions(): array
    {
        return [
            'hospitals' => Hospital::orderBy('name')->get(['id', 'name']),
            'users'     => User::orderBy('name')->get(['id', 'name']),
            'roles'     => \Spatie\Permission\Models\Role::orderBy('name')->pluck('name'),
            'modules'   => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions'   => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ];
    }

    public function index()
    {
        return view('super-admin.audit.index', [
            'kpis' => $this->audit->dashboardKpis(),
        ]);
    }

    // ==================== Activity Log ====================

    public function activity(Request $request)
    {
        return view('super-admin.audit.activity', array_merge([
            'filters' => $this->filters($request),
            'columns' => $this->audit->activityColumns(),
            'orderableColumns' => AuditCentreService::ACTIVITY_ORDERABLE_COLUMNS,
        ], $this->filterOptions()));
    }

    public function activityData(Request $request)
    {
        $filters = $this->filters($request);
        $dt = DataTables::of($this->audit->activityQuery($filters));

        foreach ($this->audit->activityColumns() as $col) {
            $key = $col['data'];
            $dt->addColumn($key, fn ($row) => $this->audit->activityRowTransform($row)[$key] ?? null);
        }
        $dt->addColumn('is_failure', fn ($row) => $this->audit->activityRowTransform($row)['is_failure']);
        $dt->only(array_merge(array_column($this->audit->activityColumns(), 'data'), ['is_failure']));

        foreach (AuditCentreService::ACTIVITY_ORDERABLE_COLUMNS as $col) {
            $dt->orderColumn($col, fn ($query, $direction) => $query->orderBy($col, $direction));
        }

        return $dt->make(true);
    }

    // ==================== Security Events ====================

    public function security(Request $request)
    {
        return view('super-admin.audit.security', array_merge([
            'filters' => $this->filters($request),
            'columns' => $this->audit->activityColumns(),
            'orderableColumns' => AuditCentreService::ACTIVITY_ORDERABLE_COLUMNS,
        ], $this->filterOptions()));
    }

    public function securityData(Request $request)
    {
        $filters = $this->filters($request);
        $dt = DataTables::of($this->audit->securityEventsQuery($filters));

        foreach ($this->audit->activityColumns() as $col) {
            $key = $col['data'];
            $dt->addColumn($key, fn ($row) => $this->audit->activityRowTransform($row)[$key] ?? null);
        }
        $dt->addColumn('is_failure', fn ($row) => $this->audit->activityRowTransform($row)['is_failure']);
        $dt->only(array_merge(array_column($this->audit->activityColumns(), 'data'), ['is_failure']));

        foreach (AuditCentreService::ACTIVITY_ORDERABLE_COLUMNS as $col) {
            $dt->orderColumn($col, fn ($query, $direction) => $query->orderBy($col, $direction));
        }

        return $dt->make(true);
    }

    // ==================== Impersonation History ====================

    public function impersonation(Request $request)
    {
        return view('super-admin.audit.impersonation', array_merge([
            'filters' => $this->filters($request),
            'columns' => $this->audit->impersonationColumns(),
            'orderableColumns' => AuditCentreService::IMPERSONATION_ORDERABLE_COLUMNS,
        ], $this->filterOptions()));
    }

    public function impersonationData(Request $request)
    {
        $filters = $this->filters($request);
        $dt = DataTables::of($this->audit->impersonationQuery($filters));

        foreach ($this->audit->impersonationColumns() as $col) {
            $key = $col['data'];
            $dt->addColumn($key, fn ($row) => $this->audit->impersonationRowTransform($row)[$key] ?? null);
        }
        $dt->addColumn('actions', fn ($row) => '
            <a href="' . route('super-admin.audit.impersonation.show', $row->id) . '" data-view-details class="btn btn-sm btn-icon btn-label-info" title="View Details">
                <i class="icon-base ti tabler-eye"></i>
            </a>
        ');
        $dt->rawColumns(['actions']);
        $dt->only(array_merge(array_column($this->audit->impersonationColumns(), 'data'), ['actions']));

        foreach (AuditCentreService::IMPERSONATION_ORDERABLE_COLUMNS as $col) {
            $dt->orderColumn($col, fn ($query, $direction) => $query->orderBy($col, $direction));
        }

        return $dt->make(true);
    }

    public function impersonationShow(Impersonation $impersonation)
    {
        $impersonation->load(['superAdmin', 'hospital', 'impersonatedUser']);

        return response()->json($this->audit->impersonationRowTransform($impersonation) + [
            'user_agent' => $impersonation->user_agent ?: '—',
        ]);
    }

    // ==================== Configuration History ====================

    public function configHistory(Request $request)
    {
        return view('super-admin.audit.config-history', array_merge([
            'filters' => $this->filters($request),
            'columns' => $this->audit->configHistoryColumns(),
            'orderableColumns' => AuditCentreService::CONFIG_ORDERABLE_COLUMNS,
        ], $this->filterOptions()));
    }

    public function configHistoryData(Request $request)
    {
        $filters = $this->filters($request);
        $dt = DataTables::of($this->audit->configHistoryQuery($filters));

        foreach ($this->audit->configHistoryColumns() as $col) {
            $key = $col['data'];
            $dt->addColumn($key, fn ($row) => $this->audit->configHistoryRowTransform($row)[$key] ?? null);
        }
        // Restrict output to the masked, formatted columns only — Yajra
        // otherwise also serializes the underlying model's raw attributes
        // (including the unmasked old_values/new_values JSON) alongside
        // the addColumn()'d ones. Found during testing; see
        // SUPER_ADMIN_AUDIT_ACTIVITY_REPORT.md.
        $dt->only(array_column($this->audit->configHistoryColumns(), 'data'));

        foreach (AuditCentreService::CONFIG_ORDERABLE_COLUMNS as $col) {
            $dt->orderColumn($col, fn ($query, $direction) => $query->orderBy($col, $direction));
        }

        return $dt->make(true);
    }

    // ==================== Export ====================

    private const VIEWS = ['activity', 'security', 'impersonation', 'config-history'];

    private function resolveRows(string $view, array $filters)
    {
        return match ($view) {
            'activity' => [$this->audit->activityQuery($filters)->get(), $this->audit->activityColumns(), fn ($r) => $this->audit->activityRowTransform($r)],
            'security' => [$this->audit->securityEventsQuery($filters)->get(), $this->audit->activityColumns(), fn ($r) => $this->audit->activityRowTransform($r)],
            'impersonation' => [$this->audit->impersonationQuery($filters)->get(), $this->audit->impersonationColumns(), fn ($r) => $this->audit->impersonationRowTransform($r)],
            'config-history' => [$this->audit->configHistoryQuery($filters)->get(), $this->audit->configHistoryColumns(), fn ($r) => $this->audit->configHistoryRowTransform($r)],
            default => abort(404),
        };
    }

    public function exportCsv(Request $request, string $view): StreamedResponse
    {
        abort_unless(in_array($view, self::VIEWS, true), 404);

        $filters = $this->filters($request);
        [$items, $columns, $transform] = $this->resolveRows($view, $filters);
        $rows = $items->map($transform);

        $this->audit->logExport($view, 'csv', $rows->count());

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map(fn ($c) => $c['title'], $columns));
            foreach ($rows as $row) {
                unset($row['id'], $row['user_agent'], $row['is_failure']);
                fputcsv($out, $row);
            }
            fclose($out);
        }, "audit-{$view}-" . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function print(Request $request, string $view)
    {
        abort_unless(in_array($view, self::VIEWS, true), 404);

        $filters = $this->filters($request);
        [$items, $columns, $transform] = $this->resolveRows($view, $filters);
        $rows = $items->map($transform);

        $this->audit->logExport($view, 'print', $rows->count());

        return response()->view('super-admin.audit.print', [
            'view' => $view,
            'title' => ucwords(str_replace('-', ' ', $view)),
            'columns' => $columns,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }
}
