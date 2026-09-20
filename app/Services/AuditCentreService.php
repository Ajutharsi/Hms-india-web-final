<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Impersonation;
use Illuminate\Support\Facades\Cache;


class AuditCentreService
{
    private const CACHE_TTL = 60;

    
    public const SECURITY_MODULES = ['auth', 'impersonation', 'platform_settings', 'subscription', 'plan'];

  
    public const ADMIN_MODULES = ['hospital', 'subscription', 'plan', 'support_ticket', 'impersonation', 'analytics', 'reports', 'platform_settings'];

    
    public const CONFIG_MODULES = ['platform_settings', 'subscription', 'plan'];

    
    public const FAILURE_ACTIONS = ['login_failed', 'permission_denied', 'test_email_failed'];

   
    private const SENSITIVE_KEY_PATTERN = '/password|secret|token|api_key|private_key/i';

  

    public function dashboardKpis(): array
    {
        return Cache::remember('audit.dashboard_kpis', self::CACHE_TTL, function () {
            $today = now()->startOfDay();

            return [
                'totalActivities'      => ActivityLog::count(),
                'todayActivities'      => ActivityLog::where('created_at', '>=', $today)->count(),
                'failedActions'        => ActivityLog::whereIn('action', self::FAILURE_ACTIONS)->count(),
                'securityEvents'       => ActivityLog::whereIn('module', self::SECURITY_MODULES)->count(),
                'loginEvents'          => ActivityLog::whereIn('action', ['login_success', 'login_failed'])->count(),
                'impersonationEvents'  => Impersonation::count(),
                'configurationChanges' => ActivityLog::whereIn('module', self::CONFIG_MODULES)->whereNotNull('old_values')->count(),
                'exportActivities'     => ActivityLog::whereIn('action', ['report_exported', 'analytics_exported', 'audit_exported'])->count(),
            ];
        });
    }

    
    private function dateRange($query, array $f, string $column = 'created_at')
    {
        return $query
            ->when($f['from_date'] ?? null, fn ($q, $v) => $q->whereDate($column, '>=', $v))
            ->when($f['to_date'] ?? null, fn ($q, $v) => $q->whereDate($column, '<=', $v));
    }

    private function applyCommonActivityFilters($query, array $f)
    {
        $query = $this->dateRange($query, $f);

        return $query
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['module'] ?? null, fn ($q, $v) => $q->where('module', $v))
            ->when($f['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($f['hospital_id'] ?? null, fn ($q, $v) => $q->whereHas('user', fn ($w) => $w->where('hospital_id', $v)))
            ->when($f['role'] ?? null, fn ($q, $v) => $q->whereHas('user.roles', fn ($w) => $w->where('name', $v)))
            ->when($f['status'] ?? null, fn ($q, $v) => $v === 'failure'
                ? $q->whereIn('action', self::FAILURE_ACTIONS)
                : $q->whereNotIn('action', self::FAILURE_ACTIONS))
            ->when($f['event_type'] ?? null, fn ($q, $v) => match ($v) {
                'security' => $q->whereIn('module', self::SECURITY_MODULES),
                'config' => $q->whereIn('module', self::CONFIG_MODULES)->whereNotNull('old_values'),
                'export' => $q->whereIn('action', ['report_exported', 'analytics_exported', 'audit_exported']),
                'login' => $q->whereIn('action', ['login_success', 'login_failed']),
                'impersonation' => $q->where('module', 'impersonation'),
                default => $q,
            })
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('description', 'like', "%{$v}%")
                ->orWhere('action', 'like', "%{$v}%")
                ->orWhere('user_name', 'like', "%{$v}%")));
    }

    
    public function activityQuery(array $filters)
    {
        return $this->applyCommonActivityFilters(ActivityLog::with(['user.roles', 'user.hospital']), $filters);
    }

    /** @return array<int, array{data:string, title:string}> */
    public function activityColumns(): array
    {
        return [
            ['data' => 'created_at', 'title' => 'Timestamp'],
            ['data' => 'user', 'title' => 'User'],
            ['data' => 'role', 'title' => 'Role'],
            ['data' => 'hospital', 'title' => 'Hospital'],
            ['data' => 'action', 'title' => 'Action'],
            ['data' => 'module', 'title' => 'Module'],
            ['data' => 'description', 'title' => 'Description'],
            ['data' => 'ip_address', 'title' => 'IP Address'],
        ];
    }

    
    public const ACTIVITY_ORDERABLE_COLUMNS = ['created_at', 'action', 'module', 'ip_address'];
    public const IMPERSONATION_ORDERABLE_COLUMNS = ['started_at', 'ended_at'];
    public const CONFIG_ORDERABLE_COLUMNS = ['created_at', 'module', 'action'];

    public function activityRowTransform(ActivityLog $row): array
    {
        return [
            'id' => $row->id,
            'created_at' => $row->created_at->format('d M Y H:i:s'),
            'user' => $row->user_name ?: ($row->user?->name ?? 'System'),
            'role' => $row->user?->roles->pluck('name')->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->implode(', ') ?: '—',
            'hospital' => $row->user?->hospital?->name ?? '—',
            'action' => ucwords(str_replace('_', ' ', $row->action)),
            'module' => ucwords(str_replace('_', ' ', $row->module)),
            'description' => $row->description,
            'ip_address' => $row->ip_address ?: '—',
            'user_agent' => $row->user_agent ?: '—',
            'is_failure' => in_array($row->action, self::FAILURE_ACTIONS, true),
        ];
    }

   
    public function securityEventsQuery(array $filters)
    {
        $query = ActivityLog::with(['user.roles'])->whereIn('module', self::SECURITY_MODULES);

        return $this->applyCommonActivityFilters($query, $filters);
    }

   
    public function impersonationQuery(array $filters)
    {
        $query = Impersonation::with(['superAdmin:id,name', 'hospital:id,name', 'impersonatedUser:id,name']);
        $query = $this->dateRange($query, $filters, 'started_at');

        return $query
            ->when($filters['hospital_id'] ?? null, fn ($q, $v) => $q->where('hospital_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('super_admin_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->whereHas('hospital', fn ($w) => $w->where('name', 'like', "%{$v}%")));
    }

    public function impersonationColumns(): array
    {
        return [
            ['data' => 'super_admin', 'title' => 'Super Admin'],
            ['data' => 'hospital', 'title' => 'Target Hospital'],
            ['data' => 'impersonated_user', 'title' => 'Target User'],
            ['data' => 'started_at', 'title' => 'Start Time'],
            ['data' => 'ended_at', 'title' => 'End Time'],
            ['data' => 'duration', 'title' => 'Duration'],
            ['data' => 'ip_address', 'title' => 'IP Address'],
        ];
    }

    public function impersonationRowTransform(Impersonation $row): array
    {
        return [
            'id' => $row->id,
            'super_admin' => $row->superAdmin?->name ?? '—',
            'hospital' => $row->hospital?->name ?? '—',
            'impersonated_user' => $row->impersonatedUser?->name ?? '—',
            'started_at' => $row->started_at?->format('d M Y H:i:s') ?? '—',
            'ended_at' => $row->ended_at?->format('d M Y H:i:s') ?? 'Active',
            'duration' => $row->durationForHumans(),
            'ip_address' => $row->ip_address ?: '—',
        ];
    }

    

    public function configHistoryQuery(array $filters)
    {
        $query = ActivityLog::with('user')
            ->whereIn('module', self::CONFIG_MODULES)
            ->whereNotNull('old_values');

        return $this->applyCommonActivityFilters($query, $filters);
    }

    public function configHistoryColumns(): array
    {
        return [
            ['data' => 'created_at', 'title' => 'Date'],
            ['data' => 'user', 'title' => 'Changed By'],
            ['data' => 'module', 'title' => 'Module'],
            ['data' => 'action', 'title' => 'Action'],
            ['data' => 'changes', 'title' => 'Changes'],
        ];
    }

    public function configHistoryRowTransform(ActivityLog $row): array
    {
        return [
            'id' => $row->id,
            'created_at' => $row->created_at->format('d M Y H:i:s'),
            'user' => $row->user_name ?: ($row->user?->name ?? 'System'),
            'module' => ucwords(str_replace('_', ' ', $row->module)),
            'action' => ucwords(str_replace('_', ' ', $row->action)),
            'changes' => $this->formatChanges($this->maskSensitive($row->old_values ?? []), $this->maskSensitive($row->new_values ?? [])),
        ];
    }

   
    public function maskSensitive(?array $values): array
    {
        if (! $values) {
            return [];
        }

        $masked = [];
        foreach ($values as $key => $value) {
            $masked[$key] = preg_match(self::SENSITIVE_KEY_PATTERN, (string) $key) ? '••••••••' : $value;
        }

        return $masked;
    }

    private function formatChanges(array $old, array $new): string
    {
        $lines = [];
        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;
            $label = ucwords(str_replace('_', ' ', $key));
            $lines[] = "{$label}: " . $this->displayValue($oldValue) . ' → ' . $this->displayValue($value);
        }

        return implode('; ', $lines);
    }

    private function displayValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return $value === null || $value === '' ? '—' : (string) $value;
    }

   
    public function logExport(string $view, string $format, int $rowCount): void
    {
        ActivityLog::log(
            'audit_exported',
            'audit',
            auth()->user()->name . " exported the {$view} view ({$format}), {$rowCount} row(s)"
        );
    }
}
