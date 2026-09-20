<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\BuildsDashboardStats;
use App\Models\Appointment;
use App\Models\LabOrder;
use App\Traits\AuthorizesAccess;

class PatientDashboardController extends Controller
{
    use BuildsDashboardStats;
    use AuthorizesAccess;

    public function index()
    {
        $patientRecord = $this->getAuthPatient();

        $appointments = collect([]);
        $total = $today = $labs = 0;

        if ($patientRecord) {
            $appointments = Appointment::with('doctor')
                ->where('patient_id', $patientRecord->id)
                ->orderBy('appointment_date', 'desc')
                ->take(5)->get();

            $total = Appointment::where('patient_id', $patientRecord->id)->count();
            $today = Appointment::where('patient_id', $patientRecord->id)->whereDate('appointment_date', today())->count();
            $labs  = LabOrder::where('patient_id', $patientRecord->id)->count();
        }

        $mainStats = [
            $this->stat('My Appointments', $total, 'primary', 'tabler-calendar', 'Total booked', null, route('appointments.index')),
            $this->stat("Today's Appts", $today, 'warning', 'tabler-clock', 'Today', null, route('appointments.index')),
            $this->stat('Lab Orders', $labs, 'info', 'tabler-flask', 'Total', null, route('lab.orders.index')),
            [
                // Identity display — links to the patient's own profile page.
                'label'     => 'MRN',
                'count'     => $patientRecord?->mrn ?? 'N/A',
                'target'    => 0,
                'color'     => 'success',
                'icon'      => 'tabler-id',
                'trend'     => 'Patient ID',
                'trendIcon' => 'tabler-id',
                'url'       => route('profile.show'),
            ],
        ];

        return view('admin.dashboard', [
            'mainStats'         => $mainStats,
            'secondaryStats'    => [],
            'departments'       => collect([]),
            'todayAppointments' => collect([]),
            'recentPatients'    => collect([]),
            'currentRole'       => 'patient',
            'patientRecord'     => $patientRecord,
            'appointments'      => $appointments,
            'activePatientsToday' => $this->activePatientsToday(),
        ]);
    }
}
