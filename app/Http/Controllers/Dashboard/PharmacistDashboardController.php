<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\Drug;
use App\Models\DrugDispensing;
use App\Services\DashboardService;

class PharmacistDashboardController extends Controller
{
    use BuildsDashboardStats;

    public function __construct(private readonly DashboardService $stats)
    {
    }

    public function index()
    {
        $lowStockThreshold = config('hospital.low_stock', 10);
        $nearExpiryDays    = config('hospital.near_expiry', 30);

        $lowStock   = Drug::where('current_stock', '<=', $lowStockThreshold)->count();
        $nearExpiry = $this->stats->nearExpiryDrugsCount();

        $mainStats = [
            $this->stat('Total Drugs', Drug::count(), 'primary', 'tabler-pill', 'In stock', null, route('pharmacy.drugs.index')),
            $this->stat('Low Stock', $lowStock, 'danger', 'tabler-alert-triangle', 'Need restock', null, route('pharmacy.drugs.index')),
            $this->stat('Dispensed Today', DrugDispensing::whereDate('created_at', today())->count(), 'success', 'tabler-clipboard-list', 'Today', null, route('pharmacy.dispensing.index')),
            $this->stat('Near Expiry', $nearExpiry, 'warning', 'tabler-calendar-x', "Within {$nearExpiryDays} days", null, route('pharmacy.drugs.index')),
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => [],
            'departments'       => collect([]),
            'todayAppointments' => collect([]),
            'recentPatients'    => collect([]),
            'currentRole'       => 'pharmacist',
            'activePatientsToday' => $this->activePatientsToday(),
        ]);
    }
}
