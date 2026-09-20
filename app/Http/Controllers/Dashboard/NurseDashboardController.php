<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\FluidLog;
use App\Models\FoodLog;
use App\Models\MedicationAdministration;
use App\Models\NursingNote;
use App\Models\Patient;
use App\Models\RoundAssignment;
use App\Models\ShiftHandover;
use App\Services\DashboardService;

class NurseDashboardController extends Controller
{
    use BuildsDashboardStats;

    public function __construct(private readonly DashboardService $stats)
    {
    }

    public function index()
    {
        $userId = auth()->id();

        $overdueRounds    = RoundAssignment::where('staff_id', $userId)->where('status', 'active')->where('next_round_at', '<=', now())->count();
        $todayFoodLogs    = FoodLog::whereDate('log_date', today())->count();
        $todayFluidLogs   = FluidLog::whereDate('log_date', today())->count();
        $admittedPatients = Patient::where('patient_type', 'ip')->active()->count();

        $mainStats = [
            $this->stat('IP Patients', $admittedPatients, 'primary', 'tabler-bed', 'Currently admitted', null, route('ward.admissions')),
            $this->stat('Overdue Rounds', $overdueRounds, $overdueRounds > 0 ? 'danger' : 'success', 'tabler-clock-exclamation',
                $overdueRounds > 0 ? 'Need attention!' : 'All on track',
                $overdueRounds > 0 ? 'tabler-alert-triangle' : 'tabler-check', route('staff-rounds.index')),
            $this->stat('Food Logs Today', $todayFoodLogs, 'warning', 'tabler-meat', 'Meals logged', null, route('food-intake.index')),
            $this->stat('Fluid Logs Today', $todayFluidLogs, 'info', 'tabler-droplet', 'Fluid entries', null, route('fluid-intake.index')),
        ];

        $pendingMeds = MedicationAdministration::where('status', 'scheduled')->count();
        $todayNotes  = NursingNote::whereDate('noted_at', today())->count();
        $needsVitals = Patient::where('patient_type', 'ip')->active()
            ->whereDoesntHave('vitals', fn($q) => $q->where('recorded_at', '>=', now()->subHours(4)))
            ->count();
        $pendingHandovers = ShiftHandover::where('incoming_nurse_id', $userId)->where('status', 'pending_acceptance')->count();
        $todayHandovers   = ShiftHandover::whereDate('handover_date', today())
            ->where(fn($q) => $q->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId))
            ->count();

        $secondaryStats = [
            $this->stat('Pending Medications', $pendingMeds, $pendingMeds > 0 ? 'warning' : 'success', 'tabler-injection', 'eMAR scheduled', null, route('emar.index')),
            $this->stat('Needs Vitals Check', $needsVitals, $needsVitals > 0 ? 'danger' : 'success', 'tabler-activity', 'No reading in 4h', null, route('vitals.index')),
            $this->stat('Nursing Notes Today', $todayNotes, 'info', 'tabler-notes', 'Written today', null, route('nursing.index')),
            $this->stat('Pending Handovers', $pendingHandovers, $pendingHandovers > 0 ? 'warning' : 'success', 'tabler-transfer-in', 'Awaiting your acceptance', null, route('shift-handover.index')),
            $this->stat("Today's Handovers", $todayHandovers, 'info', 'tabler-clipboard-list', 'Handed over today', null, route('shift-handover.index')),
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => $secondaryStats,
            'departments'       => collect([]),
            'todayAppointments' => $this->stats->todayAppointmentsList(),
            'recentPatients'    => $this->stats->recentPatients(null, 'ip'),
            'currentRole'       => 'nurse',
            'activePatientsToday' => $this->activePatientsToday(),
        ]);
    }
}
