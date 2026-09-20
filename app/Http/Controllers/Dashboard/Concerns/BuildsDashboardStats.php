<?php

namespace App\Http\Controllers\Dashboard\Concerns;

use App\Models\Attendance;
use App\Models\ErRegistration;
use App\Models\HospitalSetting;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\Patient;
use Illuminate\Support\Facades\Cache;

trait BuildsDashboardStats
{
   
    private function stat(string $label, $count, string $color, string $icon, string $trend, ?string $trendIcon = null, ?string $url = null, ?string $modalKey = null): array
    {
        return [
            'label'     => $label,
            'count'     => $count,
            'target'    => $count,
            'color'     => $color,
            'icon'      => $icon,
            'trend'     => $trend,
            'trendIcon' => $trendIcon ?? $icon,
            'url'       => $url,
            'modalKey'  => $modalKey,
        ];
    }

    private function hospitalSettings(): HospitalSetting
    {
        
        return Cache::get('hospital_settings') ?? HospitalSetting::refreshCache() ?? new HospitalSetting();
    }

    /** Single COUNT, shared + cached across all 9 dashboards for the header "Active Today" stat. */
    private function activePatientsToday(): int
    {
        return Cache::remember('dashboard.active_patients_today', 60, fn() =>
            Patient::where('status', 'active')->count()
        );
    }

   
    private function adminOpsStats(array $core): array
    {
       
        $presentToday  = Cache::remember('dashboard.present_today', 60, fn () =>
            Attendance::whereDate('attendance_date', today())->where('status', 'present')->count()
        );
        $pendingLeaves = Cache::remember('dashboard.pending_leaves', 60, fn () =>
            LeaveRequest::where('status', 'pending')->count()
        );
        $erWaiting = Cache::remember('dashboard.er_waiting', 60, fn () =>
            ErRegistration::where('status', 'waiting')->count()
        );
        $lowInventory = Cache::remember('dashboard.low_inventory', 60, fn () =>
            InventoryItem::whereColumn('current_stock', '<=', 'min_stock_level')->count()
        );

       
        return [
            $this->stat('Available Beds', $core['availableBeds'], 'info', 'tabler-bed', 'Ready for admission', null, route('ward.index')),
            $this->stat('Pending Bills', $core['pendingBills'], $core['pendingBills'] > 0 ? 'warning' : 'success', 'tabler-file-invoice', 'Unpaid invoices'),
            $this->stat("Present Today", $presentToday, 'success', 'tabler-clock-check', 'Staff attendance', null, route('hr.attendance.index')),
            $this->stat('Pending Leaves', $pendingLeaves, $pendingLeaves > 0 ? 'warning' : 'success', 'tabler-calendar-off', 'Awaiting approval', null, route('hr.leaves.index')),
            $this->stat('ER Waiting', $erWaiting, $erWaiting > 0 ? 'danger' : 'success', 'tabler-ambulance', 'Patients waiting', null, route('er.index')),
            $this->stat('Low Stock Inventory', $lowInventory, $lowInventory > 0 ? 'danger' : 'success', 'tabler-box', 'Below min level', null, route('inventory.index')),
        ];
    }

  
    private function kpiCategories(array $core, array $detail): array
    {
        $currency = config('hospital.currency', 'LKR');

        return [
            [
                'category' => 'Patients', 'icon' => 'tabler-users', 'modal' => 'kpi_patients',
                'metrics' => [
                    ['label' => 'Total Patients', 'value' => $core['totalPatients']],
                    ['label' => 'Active Patients', 'value' => $core['activePatients']],
                ],
            ],
            [
                'category' => 'Doctors', 'icon' => 'tabler-stethoscope', 'modal' => 'kpi_doctors',
                'metrics' => [
                    ['label' => 'Total Doctors', 'value' => $core['totalDoctors']],
                    ['label' => 'Available Today', 'value' => $detail['doctors']['availableToday']],
                ],
            ],
            [
                'category' => 'Appointments', 'icon' => 'tabler-calendar', 'modal' => 'kpi_appointments',
                'metrics' => [
                    ['label' => "Today's Appointments", 'value' => $core['todayAppointments']],
                    ['label' => 'Completed Today', 'value' => $detail['appointments']['completedToday']],
                ],
            ],
            [
                'category' => 'Admissions', 'icon' => 'tabler-building-hospital', 'modal' => 'kpi_admissions',
                'metrics' => [
                    ['label' => 'Total Admissions', 'value' => $detail['admissions']['total']],
                    ['label' => 'Active Admissions', 'value' => $detail['admissions']['active']],
                ],
            ],
            [
                'category' => 'Revenue', 'icon' => 'tabler-currency-dollar', 'modal' => 'kpi_revenue',
                'metrics' => [
                    ['label' => 'Monthly Revenue', 'value' => $currency . ' ' . number_format($detail['revenue']['month'])],
                    ['label' => "Today's Revenue", 'value' => $currency . ' ' . number_format($detail['revenue']['today'])],
                ],
            ],
            [
                'category' => 'Laboratory', 'icon' => 'tabler-flask', 'modal' => 'kpi_laboratory',
                'metrics' => [
                    ['label' => 'Total Tests', 'value' => $detail['labs']['total']],
                    ['label' => 'Pending Tests', 'value' => $detail['labs']['pending']],
                ],
            ],
            [
                'category' => 'Pharmacy', 'icon' => 'tabler-pill', 'modal' => 'kpi_pharmacy',
                'metrics' => [
                    ['label' => 'Total Medicines', 'value' => $detail['drugs']['total']],
                    ['label' => 'Low Stock', 'value' => $core['lowStockDrugs']],
                ],
            ],
            [
                'category' => 'Blood Bank', 'icon' => 'tabler-droplet', 'modal' => 'kpi_blood_bank',
                'metrics' => [
                    ['label' => 'Available Units', 'value' => $detail['bloodBank']['available']],
                    ['label' => 'Low Stock Groups', 'value' => $detail['bloodBank']['lowGroups']],
                ],
            ],
        ];
    }

}
