<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Services\DashboardService;

class AdminDashboardController extends Controller
{
    use BuildsDashboardStats;

    public function __construct(private readonly DashboardService $stats)
    {
    }

    public function index()
    {
        $core   = $this->stats->coreCounts();
        $detail = $this->stats->detailStats();
        $trends = $this->stats->monthlyTrends();

        return view('admin.dashboard', [
            'mainStats'          => [],
            'secondaryStats'     => [],
            'kpiCategories'      => $this->kpiCategories($core, $detail),
            'operationalStats'   => $this->adminOpsStats($core),
            'departments'        => Department::active()->get(),
            'todayAppointments'  => $this->stats->todayAppointmentsList(),
            'recentPatients'     => $this->stats->recentPatients(),
            'currentRole'        => 'admin',
            'hospitalSettings'   => $this->hospitalSettings(),
            'systemAlerts'       => $this->stats->systemAlerts(),
            'recentActivity'     => ActivityLog::with('user')->latest()->take(8)->get(),
            'monthlyPatients'    => $trends['patients'],
            'monthlyRevenue'     => $trends['revenue'],
            // Only super_admin and admin UIs read the detail-modal drill-downs, so
            // only their controllers compute it — no view composer forces this for
            // every role anymore (see ViewServiceProvider).
            'detailStats'        => $detail,
            'activePatientsToday' => $core['activePatients'],
        ]);
    }
}
