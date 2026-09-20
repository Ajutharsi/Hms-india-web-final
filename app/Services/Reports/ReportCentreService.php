<?php

namespace App\Services\Reports;

use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Impersonation;
use App\Models\Patient;
use App\Models\ReportExport;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\PlatformAnalyticsService;
use App\Services\SupportTicketService;
use App\Services\SystemHealthService;


class ReportCentreService
{
    /** Audit-log modules that belong to platform-management, not clinical operations. */
    private const ADMIN_MODULES = ['hospital', 'subscription', 'plan', 'support_ticket', 'impersonation', 'analytics', 'reports'];

    /** Audit-log modules whose entries are genuinely security-relevant. */
    private const SECURITY_MODULES = ['impersonation'];

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly PlatformAnalyticsService $analytics,
        private readonly SupportTicketService $support,
        private readonly SystemHealthService $health,
    ) {
    }

    
    public function dashboardKpis(): array
    {
        return [
            'totalReports'     => count(ReportCatalog::all()),
           
            'scheduledReports' => null,
            'totalExports'     => ReportExport::count(),
            'recentExports'    => ReportExport::with('generatedBy')->latest()->take(10)->get(),
            'mostGenerated'    => ReportExport::selectRaw('report_slug, report_label, COUNT(*) as generated_count')
                ->groupBy('report_slug', 'report_label')
                ->orderByDesc('generated_count')
                ->take(5)->get(),
            'exportActivity'   => $this->exportActivityTrend(),
            'categoryCounts'   => collect(ReportCatalog::CATEGORIES)->map(fn ($meta, $key) => [
                'label' => $meta['label'], 'icon' => $meta['icon'], 'count' => count(ReportCatalog::byCategory($key)),
            ])->all(),
        ];
    }

    /** Exports per day, last 7 days — real counts from report_exports, not fabricated. */
    private function exportActivityTrend(): array
    {
        $from   = now()->subDays(6)->startOfDay();
        $counts = ReportExport::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $trend[] = ['day' => $day->format('D'), 'count' => (int) ($counts[$day->format('Y-m-d')] ?? 0)];
        }

        return $trend;
    }

    

    private function dateRange($query, array $f, string $column = 'created_at')
    {
        return $query
            ->when($f['from_date'] ?? null, fn ($q, $v) => $q->whereDate($column, '>=', $v))
            ->when($f['to_date'] ?? null, fn ($q, $v) => $q->whereDate($column, '<=', $v));
    }

    private function hospitalBase(array $f, bool $onlyTrashed = false)
    {
        $query = $onlyTrashed ? Hospital::onlyTrashed() : Hospital::query();
        $query = $this->dateRange($query, $f);

        return $query
            ->when($f['country'] ?? null, fn ($q, $v) => $q->where('country', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")));
    }

    private function subscriptionBase(array $f)
    {
        $query = Subscription::with(['hospital:id,name,country', 'planCatalog:id,name']);
        $query = $this->dateRange($query, $f, 'subscriptions.created_at');

        return $query
            ->when($f['hospital_id'] ?? null, fn ($q, $v) => $q->where('hospital_id', $v))
            ->when($f['plan'] ?? null, fn ($q, $v) => $q->where('plan_id', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->whereHas('hospital', fn ($w) => $w->where('name', 'like', "%{$v}%")));
    }

    private function userBase(array $f)
    {
        $query = User::with(['roles', 'hospital:id,name']);
        $query = $this->dateRange($query, $f);

        return $query
            ->when($f['hospital_id'] ?? null, fn ($q, $v) => $q->where('hospital_id', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")));
    }

    private function doctorBase(array $f)
    {
        $query = Doctor::query();
        $query = $this->dateRange($query, $f);

        return $query
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$v}%")->orWhere('specialization', 'like', "%{$v}%")));
    }

    private function patientBase(array $f)
    {
        $query = Patient::query();
        $query = $this->dateRange($query, $f);

        return $query->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
            ->where('name', 'like', "%{$v}%")->orWhere('mrn', 'like', "%{$v}%")));
    }

    private function ticketBase(array $f)
    {
        $query = SupportTicket::with(['hospital:id,name', 'assignedStaff:id,name']);
        $query = $this->dateRange($query, $f);

        return $query
            ->when($f['hospital_id'] ?? null, fn ($q, $v) => $q->where('hospital_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('subject', 'like', "%{$v}%")->orWhere('ticket_number', 'like', "%{$v}%")));
    }

    private function activityLogBase(array $f)
    {
        $query = ActivityLog::with('user');
        $query = $this->dateRange($query, $f);

        return $query->when($f['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
            ->where('description', 'like', "%{$v}%")->orWhere('action', 'like', "%{$v}%")->orWhere('user_name', 'like', "%{$v}%")));
    }

    private function impersonationBase(array $f)
    {
        $query = Impersonation::with(['superAdmin:id,name', 'impersonatedUser:id,name', 'hospital:id,name']);
        $query = $this->dateRange($query, $f, 'started_at');

        return $query
            ->when($f['hospital_id'] ?? null, fn ($q, $v) => $q->where('hospital_id', $v))
            ->when($f['search'] ?? null, fn ($q, $v) => $q->whereHas('hospital', fn ($w) => $w->where('name', 'like', "%{$v}%")));
    }

    
    private function revenueByPlanRows()
    {
        return $this->analytics->planAnalytics()['distribution'];
    }

    private function revenueByHospitalRows(array $f)
    {
        return Subscription::where('subscriptions.status', 'active')
            ->join('hospitals', 'hospitals.id', '=', 'subscriptions.hospital_id')
            ->when($f['hospital_id'] ?? null, fn ($q, $v) => $q->where('hospitals.id', $v))
            ->selectRaw('hospitals.id as hospital_id, hospitals.name as hospital_name, COUNT(*) as subscriptions_count, SUM(subscriptions.total_amount) as revenue')
            ->groupBy('hospitals.id', 'hospitals.name')
            ->orderByDesc('revenue')
            ->get();
    }

    private function supportPerformanceRows()
    {
        return SupportTicket::whereNotNull('assigned_to')
            ->join('users', 'users.id', '=', 'support_tickets.assigned_to')
            ->selectRaw('users.id as staff_id, users.name as staff_name,
                COUNT(*) as assigned_count,
                SUM(CASE WHEN support_tickets.status IN (\'resolved\',\'closed\') THEN 1 ELSE 0 END) as resolved_count,
                AVG(CASE WHEN support_tickets.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, support_tickets.created_at, support_tickets.resolved_at) END) as avg_resolution_hours')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('assigned_count')
            ->get();
    }

    

    public function tableQuery(string $slug, array $filters)
    {
        return match ($slug) {
            'hospital-list'       => $this->hospitalBase($filters),
            'active-hospitals'    => $this->hospitalBase($filters)->where('status', 'active'),
            'trial-hospitals'     => $this->hospitalBase($filters)->where('status', 'trial'),
            'suspended-hospitals' => $this->hospitalBase($filters)->where('status', 'suspended'),
            'archived-hospitals'  => $this->hospitalBase($filters, onlyTrashed: true),

            'active-subscriptions'    => $this->subscriptionBase($filters)->where('subscriptions.status', 'active'),
            'expiring-subscriptions'  => $this->subscriptionBase($filters)->where('subscriptions.status', 'active')
                ->whereBetween('expires_at', [now(), now()->addDays(30)]),
            'cancelled-subscriptions' => $this->subscriptionBase($filters)->where('subscriptions.status', 'cancelled'),
            'revenue-by-plan'         => $this->revenueByPlanRows(),
            'subscription-revenue'    => $this->subscriptionBase($filters)->whereIn('subscriptions.status', ['active', 'expired']),
            'revenue-by-hospital'     => $this->revenueByHospitalRows($filters),

            'platform-users' => $this->userBase($filters),
            'active-users'   => $this->userBase($filters)->whereNotNull('last_login')->where('last_login', '>=', now()->subDays(30)),
            'doctors'        => $this->doctorBase($filters),
            'staff'          => $this->userBase($filters)->role(['nurse', 'staff', 'receptionist', 'lab_assistant', 'pharmacist']),
            'patients'       => $this->patientBase($filters),

            'resolution-time'     => $this->ticketBase($filters)->whereIn('status', ['resolved', 'closed'])->whereNotNull('resolved_at'),
            'open-tickets'        => $this->ticketBase($filters)->whereIn('status', ['open', 'assigned', 'in_progress', 'waiting_customer']),
            'closed-tickets'      => $this->ticketBase($filters)->whereIn('status', ['resolved', 'closed']),
            'support-performance' => $this->supportPerformanceRows(),

            // Real data, honestly scoped: this app only tracks each user's most
            // recent login (users.last_login), not a per-event login log.
            'login-history'          => $this->userBase($filters)->whereNotNull('last_login')->orderByDesc('last_login'),
            'activity-logs'          => $this->activityLogBase($filters),
            'impersonation-history'  => $this->impersonationBase($filters),
            'security-events'        => $this->activityLogBase($filters)->whereIn('module', self::SECURITY_MODULES),
            'administrative-actions' => $this->activityLogBase($filters)->whereIn('module', self::ADMIN_MODULES),

            default => throw new \InvalidArgumentException("[{$slug}] is not a table report."),
        };
    }

    /** @return array<int, array{data:string, title:string}> */
    public function tableColumns(string $slug): array
    {
        return match (true) {
            in_array($slug, ['hospital-list', 'active-hospitals', 'trial-hospitals', 'suspended-hospitals', 'archived-hospitals'], true) => [
                ['data' => 'code', 'title' => 'Code'],
                ['data' => 'name', 'title' => 'Hospital'],
                ['data' => 'country', 'title' => 'Country'],
                ['data' => 'status', 'title' => 'Status'],
                ['data' => 'plan', 'title' => 'Plan'],
                ['data' => 'created_at', 'title' => 'Registered'],
            ],
            in_array($slug, ['active-subscriptions', 'expiring-subscriptions', 'cancelled-subscriptions', 'subscription-revenue'], true) => [
                ['data' => 'hospital', 'title' => 'Hospital'],
                ['data' => 'plan', 'title' => 'Plan'],
                ['data' => 'billing_cycle', 'title' => 'Billing Cycle'],
                ['data' => 'amount', 'title' => 'Amount'],
                ['data' => 'status', 'title' => 'Status'],
                ['data' => 'expires_at', 'title' => 'Expires'],
            ],
            $slug === 'revenue-by-plan' => [
                ['data' => 'plan', 'title' => 'Plan'],
                ['data' => 'subscriptions', 'title' => 'Subscriptions'],
                ['data' => 'revenue', 'title' => 'Revenue'],
            ],
            $slug === 'revenue-by-hospital' => [
                ['data' => 'hospital', 'title' => 'Hospital'],
                ['data' => 'subscriptions', 'title' => 'Subscriptions'],
                ['data' => 'revenue', 'title' => 'Revenue'],
            ],
            in_array($slug, ['platform-users', 'active-users', 'staff'], true) => [
                ['data' => 'name', 'title' => 'Name'],
                ['data' => 'email', 'title' => 'Email'],
                ['data' => 'role', 'title' => 'Role'],
                ['data' => 'hospital', 'title' => 'Hospital'],
                ['data' => 'last_login', 'title' => 'Last Login'],
                ['data' => 'created_at', 'title' => 'Registered'],
            ],
            $slug === 'doctors' => [
                ['data' => 'doctor_id', 'title' => 'Doctor ID'],
                ['data' => 'name', 'title' => 'Name'],
                ['data' => 'specialization', 'title' => 'Specialization'],
                ['data' => 'department', 'title' => 'Department'],
                ['data' => 'status', 'title' => 'Status'],
                ['data' => 'phone', 'title' => 'Phone'],
            ],
            $slug === 'patients' => [
                ['data' => 'mrn', 'title' => 'MRN'],
                ['data' => 'name', 'title' => 'Name'],
                ['data' => 'phone', 'title' => 'Phone'],
                ['data' => 'email', 'title' => 'Email'],
                ['data' => 'gender', 'title' => 'Gender'],
                ['data' => 'created_at', 'title' => 'Registered'],
            ],
            $slug === 'resolution-time' => [
                ['data' => 'ticket_number', 'title' => 'Ticket #'],
                ['data' => 'subject', 'title' => 'Subject'],
                ['data' => 'hospital', 'title' => 'Hospital'],
                ['data' => 'priority', 'title' => 'Priority'],
                ['data' => 'created_at', 'title' => 'Created'],
                ['data' => 'resolved_at', 'title' => 'Resolved'],
                ['data' => 'resolution_hours', 'title' => 'Resolution (hrs)'],
            ],
            in_array($slug, ['open-tickets', 'closed-tickets'], true) => [
                ['data' => 'ticket_number', 'title' => 'Ticket #'],
                ['data' => 'subject', 'title' => 'Subject'],
                ['data' => 'hospital', 'title' => 'Hospital'],
                ['data' => 'priority', 'title' => 'Priority'],
                ['data' => 'status', 'title' => 'Status'],
                ['data' => 'assigned', 'title' => 'Assigned To'],
                ['data' => 'created_at', 'title' => 'Created'],
            ],
            $slug === 'support-performance' => [
                ['data' => 'staff', 'title' => 'Staff'],
                ['data' => 'assigned', 'title' => 'Assigned'],
                ['data' => 'resolved', 'title' => 'Resolved'],
                ['data' => 'avg_resolution_hours', 'title' => 'Avg. Resolution (hrs)'],
            ],
            in_array($slug, ['activity-logs', 'security-events', 'administrative-actions'], true) => [
                ['data' => 'date', 'title' => 'Date'],
                ['data' => 'user', 'title' => 'User'],
                ['data' => 'action', 'title' => 'Action'],
                ['data' => 'module', 'title' => 'Module'],
                ['data' => 'description', 'title' => 'Description'],
            ],
            $slug === 'login-history' => [
                ['data' => 'name', 'title' => 'Name'],
                ['data' => 'email', 'title' => 'Email'],
                ['data' => 'role', 'title' => 'Role'],
                ['data' => 'last_login', 'title' => 'Last Login'],
            ],
            $slug === 'impersonation-history' => [
                ['data' => 'super_admin', 'title' => 'Super Admin'],
                ['data' => 'hospital', 'title' => 'Hospital'],
                ['data' => 'impersonated_user', 'title' => 'Impersonated'],
                ['data' => 'started_at', 'title' => 'Started'],
                ['data' => 'ended_at', 'title' => 'Ended'],
                ['data' => 'duration', 'title' => 'Duration'],
            ],
            default => throw new \InvalidArgumentException("[{$slug}] has no table columns defined."),
        };
    }

    
    public function orderableColumns(string $slug): array
    {
        return match ($slug) {
            'hospital-list', 'active-hospitals', 'trial-hospitals', 'suspended-hospitals', 'archived-hospitals'
                => ['code', 'name', 'country', 'status', 'plan', 'created_at'],
            'active-subscriptions', 'expiring-subscriptions', 'cancelled-subscriptions', 'subscription-revenue'
                => ['status', 'expires_at'],
            'platform-users', 'active-users', 'staff', 'login-history'
                => ['name', 'email', 'last_login', 'created_at'],
            'doctors' => ['doctor_id', 'name', 'specialization', 'department', 'status', 'phone'],
            'patients' => ['mrn', 'name', 'phone', 'email', 'gender', 'created_at'],
            'resolution-time' => ['ticket_number', 'subject', 'priority', 'created_at', 'resolved_at'],
            'open-tickets', 'closed-tickets' => ['ticket_number', 'subject', 'priority', 'status', 'created_at'],
            'activity-logs', 'security-events', 'administrative-actions' => ['date', 'action', 'module'],
            'impersonation-history' => ['started_at', 'ended_at'],
            default => [], // aggregate/Collection-backed reports — every column is safely orderable client-side.
        };
    }

    /** One row → a flat assoc array matching tableColumns()'s `data` keys. Reused identically by the DataTables JSON endpoint and CSV export. */
    public function rowTransform(string $slug, $row): array
    {
        return match (true) {
            in_array($slug, ['hospital-list', 'active-hospitals', 'trial-hospitals', 'suspended-hospitals', 'archived-hospitals'], true) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'country' => $row->country ?: '—',
                'status' => $row->status ? ucfirst($row->status) : '—',
                'plan' => $row->plan ? ucfirst($row->plan) : '—',
                'created_at' => optional($row->created_at)->format('d M Y') ?? '—',
            ],
            in_array($slug, ['active-subscriptions', 'expiring-subscriptions', 'cancelled-subscriptions', 'subscription-revenue'], true) => [
                'id' => $row->id,
                'hospital' => $row->hospital?->name ?? '—',
                'plan' => $row->planCatalog?->name ?? ($row->plan ? ucfirst($row->plan) : '—'),
                'billing_cycle' => $row->billing_cycle ? ucfirst($row->billing_cycle) : '—',
                'amount' => number_format((float) ($row->total_amount ?? 0), 2),
                'status' => $row->status ? ucfirst($row->status) : '—',
                'expires_at' => optional($row->expires_at)->format('d M Y') ?? '—',
            ],
            $slug === 'revenue-by-plan' => [
                'plan' => $row->name,
                'subscriptions' => $row->subscriptions,
                'revenue' => number_format((float) $row->revenue, 2),
            ],
            $slug === 'revenue-by-hospital' => [
                'id' => $row->hospital_id,
                'hospital' => $row->hospital_name,
                'subscriptions' => $row->subscriptions_count,
                'revenue' => number_format((float) $row->revenue, 2),
            ],
            in_array($slug, ['platform-users', 'active-users', 'staff', 'login-history'], true) => [
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'role' => $row->roles->pluck('name')->map(fn ($r) => ucwords(str_replace('_', ' ', $r)))->implode(', ') ?: '—',
                'hospital' => $row->hospital?->name ?? '—',
                'last_login' => optional($row->last_login)->format('d M Y H:i') ?? 'Never',
                'created_at' => optional($row->created_at)->format('d M Y') ?? '—',
            ],
            $slug === 'doctors' => [
                'id' => $row->id,
                'doctor_id' => $row->doctor_id,
                'name' => $row->name,
                'specialization' => $row->specialization ?: '—',
                'department' => $row->department ?: '—',
                'status' => $row->status ? ucfirst($row->status) : '—',
                'phone' => $row->phone ?: '—',
            ],
            $slug === 'patients' => [
                'id' => $row->id,
                'mrn' => $row->mrn,
                'name' => $row->name,
                'phone' => $row->phone ?: '—',
                'email' => $row->email ?: '—',
                'gender' => $row->gender ? ucfirst($row->gender) : '—',
                'created_at' => optional($row->created_at)->format('d M Y') ?? '—',
            ],
            $slug === 'resolution-time' => [
                'id' => $row->id,
                'ticket_number' => $row->ticket_number,
                'subject' => $row->subject,
                'hospital' => $row->hospital?->name ?? '—',
                'priority' => ucfirst($row->priority),
                'created_at' => $row->created_at->format('d M Y'),
                'resolved_at' => optional($row->resolved_at)->format('d M Y') ?? '—',
                'resolution_hours' => $row->resolved_at ? round($row->created_at->diffInHours($row->resolved_at), 1) : '—',
            ],
            in_array($slug, ['open-tickets', 'closed-tickets'], true) => [
                'id' => $row->id,
                'ticket_number' => $row->ticket_number,
                'subject' => $row->subject,
                'hospital' => $row->hospital?->name ?? '—',
                'priority' => ucfirst($row->priority),
                'status' => ucwords(str_replace('_', ' ', $row->status)),
                'assigned' => $row->assignedStaff?->name ?? 'Unassigned',
                'created_at' => $row->created_at->format('d M Y'),
            ],
            $slug === 'support-performance' => [
                'id' => $row->staff_id,
                'staff' => $row->staff_name,
                'assigned' => (int) $row->assigned_count,
                'resolved' => (int) $row->resolved_count,
                'avg_resolution_hours' => $row->avg_resolution_hours !== null ? round((float) $row->avg_resolution_hours, 1) : 'Not tracked yet',
            ],
            in_array($slug, ['activity-logs', 'security-events', 'administrative-actions'], true) => [
                'id' => $row->id,
                'date' => $row->created_at->format('d M Y H:i'),
                'user' => $row->user_name ?: ($row->user?->name ?? 'System'),
                'action' => ucwords(str_replace('_', ' ', $row->action)),
                'module' => ucwords(str_replace('_', ' ', $row->module)),
                'description' => $row->description,
            ],
            $slug === 'impersonation-history' => [
                'id' => $row->id,
                'super_admin' => $row->superAdmin?->name ?? '—',
                'hospital' => $row->hospital?->name ?? '—',
                'impersonated_user' => $row->impersonatedUser?->name ?? '—',
                'started_at' => optional($row->started_at)->format('d M Y H:i') ?? '—',
                'ended_at' => optional($row->ended_at)->format('d M Y H:i') ?? 'Active',
                'duration' => $row->durationForHumans(),
            ],
            default => throw new \InvalidArgumentException("[{$slug}] has no row transform defined."),
        };
    }

    // ==================== Summary reports ====================

    public function summary(string $slug, array $filters): array
    {
        return match ($slug) {
            'platform-overview'     => ['kpis' => $this->platformOverviewKpis(), 'chart' => null, 'table' => null],
            'hospital-growth'       => $this->hospitalGrowthSummary(),
            'platform-activity'     => $this->platformActivitySummary(),
            'system-health-summary' => $this->systemHealthSummary(),
            'plan-distribution'     => $this->planDistributionSummary(),
            'monthly-revenue'       => $this->monthlyRevenueSummary(),
            'annual-revenue'        => $this->annualRevenueSummary(),
            'revenue-trends'        => $this->revenueTrendsSummary(),
            'user-registrations'    => $this->userRegistrationsSummary(),
            'ticket-summary'        => $this->ticketSummarySummary(),
            'analytics-summary'     => $this->analyticsSummarySummary(),
            default => throw new \InvalidArgumentException("[{$slug}] is not a summary report."),
        };
    }

    private function platformOverviewKpis(): array
    {
        $stats   = $this->dashboard->platformStats();
        $revenue = $this->analytics->revenueAnalytics();
        $currency = config('hospital.currency', 'LKR');

        return [
            ['label' => 'Total Hospitals', 'value' => $stats['total'], 'icon' => 'tabler-building-hospital', 'color' => 'primary'],
            ['label' => 'Active', 'value' => $stats['active'], 'icon' => 'tabler-circle-check', 'color' => 'success'],
            ['label' => 'Trial', 'value' => $stats['trial'], 'icon' => 'tabler-clock-hour-4', 'color' => 'warning'],
            ['label' => 'Suspended', 'value' => $stats['suspended'], 'icon' => 'tabler-ban', 'color' => 'danger'],
            ['label' => 'Archived', 'value' => $stats['archived'], 'icon' => 'tabler-archive', 'color' => 'secondary'],
            ['label' => 'Monthly Revenue', 'value' => $currency . ' ' . number_format($revenue['monthlyRevenue'], 2), 'icon' => 'tabler-report-money', 'color' => 'success'],
        ];
    }

    private function hospitalGrowthSummary(): array
    {
        $trend = $this->dashboard->hospitalGrowthTrend();

        return [
            'kpis' => null,
            'chart' => ['type' => 'line', 'categories' => collect($trend)->pluck('month')->all(), 'series' => [
                ['name' => 'New Hospitals', 'data' => collect($trend)->pluck('count')->all()],
            ]],
            'table' => null,
        ];
    }

    private function platformActivitySummary(): array
    {
        $byModule = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('module, COUNT(*) as c')->groupBy('module')->orderByDesc('c')->get();
        $recent = ActivityLog::with('user')->latest()->take(10)->get();

        return [
            'kpis' => [
                ['label' => 'Actions (24h)', 'value' => ActivityLog::where('created_at', '>=', now()->subDay())->count(), 'icon' => 'tabler-activity', 'color' => 'primary'],
                ['label' => 'Actions (7d)', 'value' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(), 'icon' => 'tabler-calendar-week', 'color' => 'info'],
                ['label' => 'Actions (30d)', 'value' => (int) $byModule->sum('c'), 'icon' => 'tabler-calendar-month', 'color' => 'secondary'],
            ],
            'chart' => $byModule->isNotEmpty() ? ['type' => 'bar', 'categories' => $byModule->pluck('module')->map(fn ($m) => ucwords(str_replace('_', ' ', $m)))->all(), 'series' => [
                ['name' => 'Actions', 'data' => $byModule->pluck('c')->all()],
            ]] : null,
            'table' => [
                'columns' => [['data' => 'date', 'title' => 'Date'], ['data' => 'user', 'title' => 'User'], ['data' => 'action', 'title' => 'Action'], ['data' => 'description', 'title' => 'Description']],
                'rows' => $recent->map(fn ($r) => [
                    'date' => $r->created_at->format('d M Y H:i'),
                    'user' => $r->user_name ?: 'System',
                    'action' => ucwords(str_replace('_', ' ', $r->action)),
                    'description' => $r->description,
                ])->all(),
            ],
        ];
    }

    private function systemHealthSummary(): array
    {
        $health = $this->health->all();

        return [
            'kpis' => [
                ['label' => 'Laravel', 'value' => $health['application']['laravelVersion'], 'icon' => 'tabler-brand-laravel', 'color' => 'danger'],
                ['label' => 'PHP', 'value' => $health['application']['phpVersion'], 'icon' => 'tabler-brand-php', 'color' => 'primary'],
                ['label' => 'Database', 'value' => $health['database']['connected'] ? 'Connected' : 'Down', 'icon' => 'tabler-database', 'color' => $health['database']['connected'] ? 'success' : 'danger'],
                ['label' => 'DB Size', 'value' => $health['database']['sizeMb'] !== null ? number_format($health['database']['sizeMb'], 1) . ' MB' : 'Not tracked yet', 'icon' => 'tabler-server-2', 'color' => 'secondary'],
                ['label' => 'Cache', 'value' => $health['cache']['healthy'] ? ucfirst($health['cache']['driver']) . ' — healthy' : 'Unhealthy', 'icon' => 'tabler-bolt', 'color' => $health['cache']['healthy'] ? 'success' : 'danger'],
                ['label' => 'Failed Jobs', 'value' => $health['queue']['failed'] ?? 'Not tracked yet', 'icon' => 'tabler-alert-triangle', 'color' => ($health['queue']['failed'] ?? 0) > 0 ? 'danger' : 'success'],
                ['label' => 'Storage Used', 'value' => $health['storage']['usedMb'] !== null ? number_format($health['storage']['usedMb'], 1) . ' MB' : 'Not tracked yet', 'icon' => 'tabler-folder', 'color' => 'secondary'],
                ['label' => 'System Alerts', 'value' => count($health['alerts']), 'icon' => 'tabler-alert-circle', 'color' => count($health['alerts']) > 0 ? 'warning' : 'success'],
            ],
            'chart' => null,
            'table' => null,
        ];
    }

    private function planDistributionSummary(): array
    {
        $plans = $this->analytics->planAnalytics();
        $dist  = $plans['distribution'];

        return [
            'kpis' => [
                ['label' => 'Most Popular Plan', 'value' => $plans['mostPopular']->name ?? 'Not tracked yet', 'icon' => 'tabler-crown', 'color' => 'primary'],
                ['label' => 'Active Plans', 'value' => $plans['activePlans'], 'icon' => 'tabler-package', 'color' => 'info'],
            ],
            'chart' => $dist->isNotEmpty() ? ['type' => 'pie', 'categories' => $dist->pluck('name')->all(), 'series' => $dist->pluck('subscriptions')->all()] : null,
            'table' => [
                'columns' => [['data' => 'plan', 'title' => 'Plan'], ['data' => 'subscriptions', 'title' => 'Subscriptions'], ['data' => 'revenue', 'title' => 'Revenue']],
                'rows' => $dist->map(fn ($r) => ['plan' => $r->name, 'subscriptions' => $r->subscriptions, 'revenue' => number_format((float) $r->revenue, 2)])->all(),
            ],
        ];
    }

    private function monthlyRevenueSummary(): array
    {
        $revenue  = $this->analytics->revenueAnalytics();
        $currency = config('hospital.currency', 'LKR');

        return [
            'kpis' => [
                ['label' => 'Monthly Revenue', 'value' => $currency . ' ' . number_format($revenue['monthlyRevenue'], 2), 'icon' => 'tabler-report-money', 'color' => 'success'],
                ['label' => 'Active Subscription Value', 'value' => $currency . ' ' . number_format($revenue['activeSubscriptionValue'], 2), 'icon' => 'tabler-circle-check', 'color' => 'primary'],
            ],
            'chart' => null,
            'table' => null,
        ];
    }

    private function annualRevenueSummary(): array
    {
        $revenue  = $this->analytics->revenueAnalytics();
        $currency = config('hospital.currency', 'LKR');

        return [
            'kpis' => [
                ['label' => 'Annual Revenue', 'value' => $currency . ' ' . number_format($revenue['annualRevenue'], 2), 'icon' => 'tabler-calendar-dollar', 'color' => 'success'],
                ['label' => 'Expired Subscription Value', 'value' => $currency . ' ' . number_format($revenue['expiredSubscriptionValue'], 2), 'icon' => 'tabler-calendar-x', 'color' => 'warning'],
            ],
            'chart' => null,
            'table' => null,
        ];
    }

    /** Real revenue booked per month (subscriptions.total_amount grouped by creation month) — last 6 months, no fabricated trend. */
    private function revenueTrendsSummary(): array
    {
        $from = now()->subMonths(5)->startOfMonth();
        $sums = Subscription::where('status', 'active')->where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(total_amount) as amt")
            ->groupBy('ym')->pluck('amt', 'ym');

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $trend[] = ['month' => $month->format('M'), 'amount' => round((float) ($sums[$month->format('Y-m')] ?? 0), 2)];
        }

        return [
            'kpis' => null,
            'chart' => ['type' => 'line', 'categories' => collect($trend)->pluck('month')->all(), 'series' => [
                ['name' => 'Revenue', 'data' => collect($trend)->pluck('amount')->all()],
            ]],
            'table' => null,
        ];
    }

    private function userRegistrationsSummary(): array
    {
        $growth = $this->analytics->growthAnalytics();
        $reg    = $this->analytics->registrationAnalytics();

        return [
            'kpis' => [
                ['label' => 'New Users (30d)', 'value' => $reg['newUsers30d'], 'icon' => 'tabler-user-plus', 'color' => 'success'],
                ['label' => 'New Hospitals (30d)', 'value' => $reg['newHospitals30d'], 'icon' => 'tabler-building-hospital', 'color' => 'primary'],
            ],
            'chart' => ['type' => 'line', 'categories' => collect($growth['users'])->pluck('month')->all(), 'series' => [
                ['name' => 'New Users', 'data' => collect($growth['users'])->pluck('count')->all()],
            ]],
            'table' => null,
        ];
    }

    private function ticketSummarySummary(): array
    {
        $kpis = $this->support->kpis();

        return [
            'kpis' => [
                ['label' => 'Total Tickets', 'value' => $kpis['total'], 'icon' => 'tabler-ticket', 'color' => 'primary'],
                ['label' => 'Open', 'value' => $kpis['open'], 'icon' => 'tabler-ticket', 'color' => 'info'],
                ['label' => 'In Progress', 'value' => $kpis['inProgress'], 'icon' => 'tabler-progress', 'color' => 'warning'],
                ['label' => 'Resolved', 'value' => $kpis['resolved'], 'icon' => 'tabler-circle-check', 'color' => 'success'],
                ['label' => 'Closed', 'value' => $kpis['closed'], 'icon' => 'tabler-square-check', 'color' => 'secondary'],
                ['label' => 'Overdue', 'value' => $kpis['overdue'], 'icon' => 'tabler-clock-exclamation', 'color' => 'danger'],
                ['label' => 'Avg. Resolution', 'value' => $kpis['avgResolutionHours'] !== null ? $kpis['avgResolutionHours'] . 'h' : 'Not tracked yet', 'icon' => 'tabler-clock-check', 'color' => 'secondary'],
            ],
            'chart' => null,
            'table' => null,
        ];
    }

    private function analyticsSummarySummary(): array
    {
        $growth  = $this->analytics->growthAnalytics();
        $plans   = $this->analytics->planAnalytics();
        $overview = $this->dashboard->platformStats();

        return [
            'kpis' => [
                ['label' => 'Total Hospitals', 'value' => $overview['total'], 'icon' => 'tabler-building-hospital', 'color' => 'primary'],
                ['label' => 'Active Plans', 'value' => $plans['activePlans'], 'icon' => 'tabler-package', 'color' => 'info'],
                ['label' => 'Most Popular Plan', 'value' => $plans['mostPopular']->name ?? 'Not tracked yet', 'icon' => 'tabler-crown', 'color' => 'success'],
            ],
            'chart' => ['type' => 'line', 'categories' => collect($growth['hospitals'])->pluck('month')->all(), 'series' => [
                ['name' => 'Hospitals', 'data' => collect($growth['hospitals'])->pluck('count')->all()],
                ['name' => 'Users', 'data' => collect($growth['users'])->pluck('count')->all()],
            ]],
            'table' => null,
        ];
    }

   

    public function logExport(string $slug, string $format, array $filters, int $rowCount = 0): void
    {
        $meta = ReportCatalog::find($slug);

        ReportExport::create([
            'report_slug'  => $slug,
            'report_label' => $meta['label'] ?? $slug,
            'category'     => $meta['categories'][0] ?? 'platform',
            'format'       => $format,
            'filters'      => $filters ?: null,
            'row_count'    => $rowCount,
            'generated_by' => auth()->id(),
        ]);

        ActivityLog::log('report_exported', 'reports', auth()->user()->name . " exported \"{$meta['label']}\" ({$format})");
    }
}
