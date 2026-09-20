<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\Appointment;
use App\Models\LabOrder;
use App\Models\OpdToken;
use App\Models\OtSchedule;
use App\Models\Prescription;
use App\Models\RadiologyOrder;
use App\Models\Vital;
use App\Services\DashboardService;
use App\Traits\AuthorizesAccess;

class DoctorDashboardController extends Controller
{
    use BuildsDashboardStats;
    use AuthorizesAccess;

    public function __construct(private readonly DashboardService $stats)
    {
    }

    public function index()
    {
        $doctorRecord = $this->getAuthDoctor();
        $doctorId     = $doctorRecord?->id;

        // Explicit guards, not when(): a null $doctorId (unlinked doctor-email
        // record) must yield all-zero "my" stats, never fall through to
        // hospital-wide counts mislabeled as this doctor's own data.
        $myAppointments    = $doctorId ? Appointment::where('doctor_id', $doctorId)->count() : 0;
        $todayAppointments = $doctorId ? Appointment::where('doctor_id', $doctorId)->whereDate('appointment_date', today())->count() : 0;
        $myPrescriptions    = $doctorId ? Prescription::where('doctor_id', $doctorId)->count() : 0;
        $myLabOrders       = $doctorId ? LabOrder::where('doctor_id', $doctorId)->count() : 0;

        $mainStats = [
            $this->stat('My Appointments', $myAppointments, 'primary', 'tabler-calendar', 'Total', null, route('appointments.index')),
            $this->stat("Today's Appts", $todayAppointments, 'warning', 'tabler-clock', 'Today', null, route('appointments.index')),
            // No prescriptions index route exists in the app (prescriptions are only
            // reachable individually via the consultation flow) — points at the
            // dashboard TODO placeholder instead of being left non-clickable.
            $this->stat('Prescriptions', $myPrescriptions, 'success', 'tabler-pill', 'Total', null, route('dashboard.placeholder', ['feature' => 'Prescriptions'])),
            $this->stat('Lab Orders', $myLabOrders, 'info', 'tabler-flask', 'Total', null, route('lab.orders.index')),
        ];

        $todayOt = $doctorId
            ? OtSchedule::where('surgeon_id', $doctorId)->whereDate('scheduled_start', today())->whereNotIn('status', ['cancelled'])->count()
            : 0;
        $pendingRadiology = $doctorId
            ? RadiologyOrder::where('doctor_id', $doctorId)->whereIn('status', ['ordered', 'scheduled', 'in_progress'])->count()
            : 0;
        $criticalVitals = Vital::whereDate('recorded_at', today())
            ->where(fn($q) => $q->where('spo2', '<', 94)
                ->orWhere(fn($q2) => $q2->whereNotNull('blood_pressure')->whereRaw('CAST(SUBSTRING_INDEX(blood_pressure, "/", 1) AS UNSIGNED) >= 140')))
            ->count();
        $queueCount = $doctorId
            ? OpdToken::where('doctor_id', $doctorId)->whereDate('token_date', today())->whereIn('status', ['waiting', 'in_consultation'])->count()
            : 0;

        $secondaryStats = [
            $this->stat("Today's OT", $todayOt, 'info', 'tabler-scissors', 'Scheduled', null, route('ot.index')),
            $this->stat('Pending Radiology', $pendingRadiology, $pendingRadiology > 0 ? 'warning' : 'success', 'tabler-radioactive', 'Awaiting', null, route('radiology.index')),
            $this->stat('Critical Vitals', $criticalVitals, $criticalVitals > 0 ? 'danger' : 'success', 'tabler-alert-triangle', 'Today, hospital-wide', null, route('vitals.index')),
            $this->stat('OPD Queue', $queueCount, 'primary', 'tabler-users', 'Waiting for me', null, route('opd.queue.index')),
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => $secondaryStats,
            'departments'       => collect([]),
            'todayAppointments' => $this->stats->todayAppointmentsList($doctorId, $doctorRecord?->name),
            'recentPatients'    => $this->stats->recentPatients($doctorId),
            'currentRole'       => 'doctor',
            'activePatientsToday' => $this->activePatientsToday(),
        ]);
    }
}
