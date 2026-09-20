<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\DashboardService;
use App\Services\SupportTicketService;
use App\Services\SystemHealthService;


class SuperAdminDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $stats,
        private readonly SystemHealthService $health,
        private readonly SupportTicketService $support,
    ) {
    }

    public function index()
    {
        return view('super-admin.dashboard', [
            'platformStats'       => $this->stats->platformStats(),
            'systemHealth'        => $this->stats->systemHealth(),
            'hospitalGrowthTrend' => $this->stats->hospitalGrowthTrend(),
            'notifications'       => array_merge(
                $this->stats->platformNotifications(),
                $this->systemHealthNotifications(),
                $this->support->notifications(),
            ),
            'recentActivity'      => ActivityLog::with('user')
                ->whereIn('module', ['hospital', 'subscription'])
                ->latest()->take(8)->get(),
        ]);
    }

   
    private function systemHealthNotifications(): array
    {
        
        $alerts = array_filter($this->health->all()['alerts'], fn ($a) => ($a['source'] ?? null) === 'check');

        return array_map(fn ($alert) => [
            'level'   => $alert['level'],
            'icon'    => 'tabler-heartbeat',
            'message' => $alert['message'],
            'url'     => route('super-admin.health.index'),
        ], $alerts);
    }
}
