<?php

namespace App\Services\Reports;


class ReportCatalog
{
    public const CATEGORIES = [
        'platform'     => ['label' => 'Platform Reports', 'icon' => 'tabler-building-skyscraper'],
        'hospital'     => ['label' => 'Hospital Reports', 'icon' => 'tabler-building-hospital'],
        'subscription' => ['label' => 'Subscription Reports', 'icon' => 'tabler-crown'],
        'revenue'      => ['label' => 'Revenue Reports', 'icon' => 'tabler-currency-dollar'],
        'user'         => ['label' => 'User Reports', 'icon' => 'tabler-users'],
        'support'      => ['label' => 'Support Reports', 'icon' => 'tabler-headset'],
        'audit'        => ['label' => 'Audit Reports', 'icon' => 'tabler-shield-check'],
        'analytics'    => ['label' => 'Analytics Reports', 'icon' => 'tabler-chart-infographic'],
    ];

    /**
     * @return array<string, array{label:string, description:string, icon:string, categories:array<int,string>, type:'table'|'summary', chart:bool, filters:array<int,string>}>
     */
    public static function all(): array
    {
        return [
            
            'platform-overview' => [
                'label' => 'Platform Overview', 'categories' => ['platform'], 'type' => 'summary', 'chart' => false,
                'icon' => 'tabler-dashboard', 'filters' => [],
                'description' => 'Total, active, trial, suspended and archived hospitals, plus headline revenue.',
            ],
            'hospital-growth' => [
                'label' => 'Hospital Growth', 'categories' => ['platform', 'hospital'], 'type' => 'summary', 'chart' => true,
                'icon' => 'tabler-trending-up', 'filters' => [],
                'description' => 'New hospital signups over the last 6 months.',
            ],
            'platform-activity' => [
                'label' => 'Platform Activity', 'categories' => ['platform'], 'type' => 'summary', 'chart' => false,
                'icon' => 'tabler-activity', 'filters' => [],
                'description' => 'Audit-log volume by module over the last 30 days, plus the most recent actions.',
            ],
            'system-health-summary' => [
                'label' => 'System Health Summary', 'categories' => ['platform'], 'type' => 'summary', 'chart' => false,
                'icon' => 'tabler-heartbeat', 'filters' => [],
                'description' => 'Live application, database, cache, queue and storage health snapshot.',
            ],

           
            'hospital-list' => [
                'label' => 'Hospital List', 'categories' => ['hospital'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-building-hospital', 'filters' => ['date', 'status', 'country', 'search'],
                'description' => 'Every hospital on the platform, with status, plan and country.',
            ],
            'active-hospitals' => [
                'label' => 'Active Hospitals', 'categories' => ['hospital'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-circle-check', 'filters' => ['date', 'country', 'search'],
                'description' => 'Hospitals currently in active status.',
            ],
            'trial-hospitals' => [
                'label' => 'Trial Hospitals', 'categories' => ['hospital'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-clock-hour-4', 'filters' => ['date', 'country', 'search'],
                'description' => 'Hospitals currently on trial, with trial end date.',
            ],
            'suspended-hospitals' => [
                'label' => 'Suspended Hospitals', 'categories' => ['hospital'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-ban', 'filters' => ['date', 'country', 'search'],
                'description' => 'Hospitals currently suspended.',
            ],
            'archived-hospitals' => [
                'label' => 'Archived Hospitals', 'categories' => ['hospital'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-archive', 'filters' => ['date', 'search'],
                'description' => 'Soft-deleted (archived) hospitals.',
            ],

           
            'active-subscriptions' => [
                'label' => 'Active Subscriptions', 'categories' => ['subscription'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-circle-check', 'filters' => ['date', 'hospital', 'plan', 'search'],
                'description' => 'Subscriptions currently active, with plan and expiry.',
            ],
            'expiring-subscriptions' => [
                'label' => 'Expiring Subscriptions', 'categories' => ['subscription'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-calendar-due', 'filters' => ['hospital', 'plan', 'search'],
                'description' => 'Active subscriptions expiring within the next 30 days.',
            ],
            'cancelled-subscriptions' => [
                'label' => 'Cancelled Subscriptions', 'categories' => ['subscription'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-x', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Subscriptions that have been cancelled.',
            ],
            'revenue-by-plan' => [
                'label' => 'Revenue by Plan', 'categories' => ['subscription', 'revenue'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-report-money', 'filters' => [],
                'description' => 'Active subscription count and revenue, grouped by plan.',
            ],
            'plan-distribution' => [
                'label' => 'Plan Distribution', 'categories' => ['subscription'], 'type' => 'summary', 'chart' => true,
                'icon' => 'tabler-chart-pie', 'filters' => [],
                'description' => 'Share of active subscriptions by plan.',
            ],

           
            'monthly-revenue' => [
                'label' => 'Monthly Revenue', 'categories' => ['revenue'], 'type' => 'summary', 'chart' => false,
                'icon' => 'tabler-report-money', 'filters' => [],
                'description' => 'Current month-equivalent revenue from active subscriptions.',
            ],
            'annual-revenue' => [
                'label' => 'Annual Revenue', 'categories' => ['revenue'], 'type' => 'summary', 'chart' => false,
                'icon' => 'tabler-calendar-dollar', 'filters' => [],
                'description' => 'Billing-cycle-aware annualised revenue projection.',
            ],
            'revenue-trends' => [
                'label' => 'Revenue Trends', 'categories' => ['revenue'], 'type' => 'summary', 'chart' => true,
                'icon' => 'tabler-chart-line', 'filters' => [],
                'description' => 'Active-subscription revenue booked per month, last 6 months.',
            ],
            'subscription-revenue' => [
                'label' => 'Subscription Revenue', 'categories' => ['revenue'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-receipt', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Every active or expired subscription with its billed amount.',
            ],
            'revenue-by-hospital' => [
                'label' => 'Revenue by Hospital', 'categories' => ['revenue'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-building-bank', 'filters' => [],
                'description' => 'Total subscription revenue, grouped by hospital.',
            ],

           
            'platform-users' => [
                'label' => 'Platform Users', 'categories' => ['user'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-users', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Every user account on the platform, with role and hospital.',
            ],
            'active-users' => [
                'label' => 'Active Users', 'categories' => ['user'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-user-check', 'filters' => ['hospital', 'search'],
                'description' => 'Users who have logged in within the last 30 days.',
            ],
            'doctors' => [
                'label' => 'Doctors', 'categories' => ['user'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-stethoscope', 'filters' => ['date', 'status', 'search'],
                'description' => 'Every doctor record, with specialization and status.',
            ],
            'staff' => [
                'label' => 'Staff', 'categories' => ['user'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-users-group', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Nurses, receptionists, lab and pharmacy staff.',
            ],
            'patients' => [
                'label' => 'Patients', 'categories' => ['user'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-user', 'filters' => ['date', 'search'],
                'description' => 'Every patient record on the platform.',
            ],
            'user-registrations' => [
                'label' => 'User Registrations', 'categories' => ['user'], 'type' => 'summary', 'chart' => true,
                'icon' => 'tabler-user-plus', 'filters' => [],
                'description' => 'New user signups over the last 6 months.',
            ],

           
            'ticket-summary' => [
                'label' => 'Ticket Summary', 'categories' => ['support'], 'type' => 'summary', 'chart' => false,
                'icon' => 'tabler-ticket', 'filters' => [],
                'description' => 'Support ticket counts by status and priority, and average resolution time.',
            ],
            'resolution-time' => [
                'label' => 'Resolution Time', 'categories' => ['support'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-clock-check', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Every resolved or closed ticket with its actual resolution time in hours.',
            ],
            'open-tickets' => [
                'label' => 'Open Tickets', 'categories' => ['support'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-ticket', 'filters' => ['date', 'hospital', 'status', 'search'],
                'description' => 'Tickets not yet resolved or closed.',
            ],
            'closed-tickets' => [
                'label' => 'Closed Tickets', 'categories' => ['support'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-circle-check', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Tickets that have been resolved or closed.',
            ],
            'support-performance' => [
                'label' => 'Support Performance', 'categories' => ['support'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-award', 'filters' => [],
                'description' => 'Tickets assigned/resolved and average resolution time, per staff member.',
            ],

            
            'activity-logs' => [
                'label' => 'Activity Logs', 'categories' => ['audit'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-list-details', 'filters' => ['date', 'search'],
                'description' => 'The full platform-wide audit trail — every logged administrative action.',
            ],
            'login-history' => [
                'label' => 'Login History', 'categories' => ['audit'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-login', 'filters' => ['search'],
                'description' => 'Each user\'s most recent login. Per-event login history is not tracked.',
            ],
            'impersonation-history' => [
                'label' => 'Impersonation History', 'categories' => ['audit'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-user-shield', 'filters' => ['date', 'hospital', 'search'],
                'description' => 'Every "Login as Hospital Admin" session, who started it, and when it ended.',
            ],
            'security-events' => [
                'label' => 'Security Events', 'categories' => ['audit'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-shield-exclamation', 'filters' => ['date', 'search'],
                'description' => 'Impersonation and account-security-relevant audit-log entries.',
            ],
            'administrative-actions' => [
                'label' => 'Administrative Actions', 'categories' => ['audit'], 'type' => 'table', 'chart' => false,
                'icon' => 'tabler-gavel', 'filters' => ['date', 'search'],
                'description' => 'Audit-log entries from platform-management modules (hospitals, subscriptions, plans, support, impersonation).',
            ],

            
            'analytics-summary' => [
                'label' => 'Analytics Summary', 'categories' => ['analytics'], 'type' => 'summary', 'chart' => true,
                'icon' => 'tabler-chart-infographic', 'filters' => [],
                'description' => 'Condensed view of Platform Analytics\' growth and plan-distribution data — see Platform Analytics for the full breakdown.',
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::all());
    }

    /** Reports belonging to a given category, keyed by slug — preserves catalog order. */
    public static function byCategory(string $category): array
    {
        return array_filter(self::all(), fn (array $r) => in_array($category, $r['categories'], true));
    }
}
