<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\LabOrder;
use App\Models\LabTest;

class LabDashboardController extends Controller
{
    use BuildsDashboardStats;

    public function index()
    {
        $pending   = LabOrder::where('status', 'pending')->count();
        $doneToday = LabOrder::where('status', 'completed')->whereDate('updated_at', today())->count();

        $mainStats = [
            $this->stat('Pending Orders', $pending, 'warning', 'tabler-clock', 'Awaiting', null, route('lab.orders.index')),
            $this->stat('Completed Today', $doneToday, 'success', 'tabler-circle-check', 'Today', null, route('lab.orders.index')),
            $this->stat('Total Orders', LabOrder::count(), 'primary', 'tabler-flask', 'All time', null, route('lab.orders.index')),
            $this->stat('Lab Tests', LabTest::count(), 'info', 'tabler-test-pipe', 'Available', null, route('lab.tests.index')),
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => [],
            'departments'       => collect([]),
            'todayAppointments' => collect([]),
            'recentPatients'    => collect([]),
            'currentRole'       => 'lab_assistant',
            'activePatientsToday' => $this->activePatientsToday(),
        ]);
    }
}
