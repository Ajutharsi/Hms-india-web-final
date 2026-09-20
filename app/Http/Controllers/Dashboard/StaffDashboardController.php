<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\FluidLog;
use App\Models\FoodLog;
use App\Models\RoundAssignment;
use App\Services\DashboardService;

class StaffDashboardController extends Controller
{
    use BuildsDashboardStats;

    public function __construct(private readonly DashboardService $stats)
    {
    }

    public function index()
    {
        $userId = auth()->id();

        $myRounds      = RoundAssignment::where('staff_id', $userId)->where('status', 'active')->count();
        $overdueRounds = RoundAssignment::where('staff_id', $userId)->where('status', 'active')->where('next_round_at', '<=', now())->count();
        $myFoodLogs    = FoodLog::whereDate('log_date', today())->where('logged_by', $userId)->count();
        $myFluidLogs   = FluidLog::whereDate('log_date', today())->where('logged_by', $userId)->count();

        $mainStats = [
            $this->stat('My Assigned Wards', $myRounds, 'primary', 'tabler-building-hospital', 'Active assignments', null, route('staff-rounds.my-rounds')),
            $this->stat('Overdue Rounds', $overdueRounds, $overdueRounds > 0 ? 'danger' : 'success', 'tabler-clock-exclamation',
                $overdueRounds > 0 ? 'Need attention!' : 'All on track',
                $overdueRounds > 0 ? 'tabler-alert-triangle' : 'tabler-check', route('staff-rounds.my-rounds')),
            $this->stat('My Food Logs Today', $myFoodLogs, 'warning', 'tabler-meat', 'Logged by me', null, route('food-intake.index')),
            $this->stat('My Fluid Logs Today', $myFluidLogs, 'info', 'tabler-droplet', 'Logged by me', null, route('fluid-intake.index')),
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => [],
            'departments'       => collect([]),
            'todayAppointments' => collect([]),
            'recentPatients'    => $this->stats->recentPatients(null, 'ip'),
            'currentRole'       => 'staff',
            'activePatientsToday' => $this->activePatientsToday(),
        ]);
    }
}
