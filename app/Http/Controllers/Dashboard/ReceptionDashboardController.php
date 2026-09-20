<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Services\DashboardService;

class ReceptionDashboardController extends Controller
{
    use BuildsDashboardStats;

    public function __construct(private readonly DashboardService $stats)
    {
    }

    public function index()
    {
        $core = $this->stats->coreCounts();

        $mainStats = [
            $this->stat("Today's Appointments", $core['todayAppointments'], 'primary', 'tabler-calendar', now()->format('d M Y'), null, route('appointments.index')),
            $this->stat('Total Patients', $core['totalPatients'], 'success', 'tabler-users', 'All registered', null, route('patients.index')),
            $this->stat('Pending Invoices', $core['pendingBills'], 'warning', 'tabler-file-invoice', 'Unpaid', null, route('billing.invoices.index')),
            $this->stat('Available Beds', $core['availableBeds'], 'info', 'tabler-bed', 'Ready', null, route('ward.index')),
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => [],
            'departments'       => collect([]),
            'todayAppointments' => $this->stats->todayAppointmentsList(),
            'recentPatients'    => $this->stats->recentPatients(),
            'currentRole'       => 'receptionist',
            'activePatientsToday' => $core['activePatients'],
        ]);
    }
}
