<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\OpdToken;
use App\Models\Patient;
use App\Support\DataTableFormat;
use App\Traits\AuthorizesAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AppointmentController extends Controller
{
    use AuthorizesAccess;

    public function index(Request $request)
    {
        $fromPatient = $request->filled('patient_id') ? Patient::find($request->patient_id) : null;
        $fromType    = $request->get('type');

        $patients = Patient::select('id', 'name', 'mrn')->get();
        $doctors  = Doctor::active()->select('id', 'name', 'specialization', 'photo')->get();

        $statsQuery = Appointment::query();

        if (auth()->user()->hasRole('doctor')) {
            $statsQuery->where('doctor_id', $this->getAuthDoctor()?->id);
        }

        $todayAppointments     = (clone $statsQuery)->whereDate('appointment_date', today())->count();
        $scheduledAppointments = (clone $statsQuery)->where('status', 'scheduled')->count();
        $completedAppointments = (clone $statsQuery)->where('status', 'completed')->count();
        $cancelledAppointments = (clone $statsQuery)->where('status', 'cancelled')->count();

        return view('appointments.index', compact(
            'patients', 'doctors', 'fromPatient', 'fromType',
            'todayAppointments', 'scheduledAppointments', 'completedAppointments', 'cancelledAppointments'
        ));
    }

    public function data()
    {
        $query = Appointment::with(['patient:id,name,mrn', 'doctor:id,name,specialization,photo']);

        // Patient role — only their appointments
        if ($this->isPatientRole()) {
            $patient = $this->getAuthPatient();
            $patient ? $query->where('patient_id', $patient->id) : $query->whereRaw('1 = 0');
        }
        // Doctor role — only their appointments
        elseif ($this->isDoctorRole()) {
            $doctor = $this->getAuthDoctor();
            if ($doctor) {
                $query->where('doctor_id', $doctor->id);
            }
        }

        return DataTables::of($query)
            ->addColumn('patient_info', fn($a) => DataTableFormat::patientInfo($a->patient))
            ->addColumn('doctor_info', fn($a) => DataTableFormat::doctorInfo($a->doctor))
            ->addColumn('token_badge', fn($a) => $a->token_number ? '<span class="badge bg-label-primary">#' . $a->token_number . '</span>' : 'N/A')
            ->addColumn('type_badge', fn($a) => DataTableFormat::badge($a->type, [
                'op' => 'info', 'consult' => 'primary', 'emergency' => 'danger', 'followup' => 'success',
            ]))
            ->addColumn('status_badge', fn($a) => DataTableFormat::badge($a->status, [
                'scheduled' => 'warning', 'completed' => 'success', 'cancelled' => 'danger', 'no_show' => 'secondary',
            ]))
            ->addColumn('actions', fn($a) => view('_partials.dt-actions', ['buttons' => [
                ['type' => 'link', 'url' => route('consultations.create', $a), 'icon' => 'tabler-stethoscope', 'class' => 'btn-label-info', 'title' => 'Consultation'],
                ['type' => 'button', 'onclick' => "editAppointment({$a->id})", 'icon' => 'tabler-edit', 'class' => 'btn-label-warning', 'title' => 'Edit'],
                ['type' => 'button', 'onclick' => "deleteAppointment({$a->id})", 'icon' => 'tabler-trash', 'class' => 'btn-label-danger', 'title' => 'Delete', 'can' => 'delete appointments'],
            ]])->render())
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => DataTableFormat::patientFilter($q, $kw))
            ->filterColumn('doctor_info', fn($q, $kw) => $q->whereHas('doctor', fn($d) => $d->where('name', 'like', "%{$kw}%")))
            ->rawColumns(['patient_info', 'doctor_info', 'token_badge', 'type_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function getJson(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        return response()->json($appointment->load(['patient', 'doctor']));
    }

    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();

        // Transaction so two simultaneous bookings can't get the same token.
        $opdToken = DB::transaction(function () use ($validated) {
            $tokenCount = Appointment::where('doctor_id', $validated['doctor_id'])
                ->where('appointment_date', $validated['appointment_date'])
                ->lockForUpdate()
                ->count();

            $appointment = Appointment::create([
                ...$validated,
                'token_number' => $tokenCount + 1,
            ]);

            if ($validated['type'] === 'op') {
                $position = OpdToken::whereDate('token_date', $validated['appointment_date'])
                    ->where('doctor_id', $validated['doctor_id'])
                    ->lockForUpdate()
                    ->max('queue_position') + 1;

                return OpdToken::create([
                    'patient_id'     => $validated['patient_id'],
                    'doctor_id'      => $validated['doctor_id'],
                    'appointment_id' => $appointment->id,
                    'token_date'     => $validated['appointment_date'],
                    'status'         => 'waiting',
                    'queue_position' => $position,
                ]);
            }

            return null;
        });

        $message = 'Appointment booked successfully!';
        if ($opdToken) {
            $message .= ' OPD token ' . $opdToken->token_no . ' issued.';
        }

        return redirect()->route('appointments.index')->with('success', $message);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment updated successfully!');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete(); // soft delete

        return response()->json(['success' => true]);
    }

    // Available slots
    public function getSlots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date'      => 'required|date',
        ], $this->validationMessages());

        $doctor = Doctor::find($request->doctor_id);
        $day    = strtolower(date('l', strtotime($request->date)));

        $schedule = $doctor->schedules()
            ->where('day', $day)
            ->where('is_available', true)
            ->first();

        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        $slots    = [];
        $start    = strtotime($schedule->start_time);
        $end      = strtotime($schedule->end_time);
        $duration = $schedule->slot_duration * 60;

        $booked = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('appointment_time')
            ->toArray();

        for ($time = $start; $time < $end; $time += $duration) {
            $timeStr = date('H:i', $time);
            $slots[] = [
                'time'      => $timeStr,
                'available' => !in_array($timeStr, $booked),
            ];
        }

        return response()->json(['slots' => $slots]);
    }

    public function calendar()
    {
        return view('appointments.calendar');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'patient.allergies',
            'doctor',
            'consultation.prescriptions.items',
        ]);

        return view('appointments.show', compact('appointment'));
    }

    public function calendarData(Request $request)
    {
        $query = Appointment::with(['patient:id,name,mrn', 'doctor:id,name']);

        // Only the month/week/day range currently visible in the calendar —
        // was previously loading the entire (ever-growing) appointments table.
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('appointment_date', [
                \Carbon\Carbon::parse($request->start)->toDateString(),
                \Carbon\Carbon::parse($request->end)->toDateString(),
            ]);
        }

        // Doctor role — only show this doctor's own appointments on the calendar
        if ($this->isDoctorRole()) {
            $query->where('doctor_id', $this->getAuthDoctor()?->id);
        }

        $events = $query->get()->map(function ($a) {
            $colors = [
                'scheduled' => '#ff9f43',
                'completed' => '#28c76f',
                'cancelled' => '#ea5455',
                'no_show'   => '#82868b',
            ];

            $color = $a->type === 'emergency'
                ? '#ea5455'
                : ($colors[$a->status] ?? '#696cff');

            return [
                'id'    => $a->id,
                'title' => $a->patient->name . ' — Dr.' . $a->doctor->name,
                'start' => $a->appointment_date . 'T' . $a->appointment_time,
                'color' => $color,
                'extendedProps' => [
                    'patient' => $a->patient->name,
                    'mrn'     => $a->patient->mrn,
                    'doctor'  => $a->doctor->name,
                    'time'    => $a->appointment_time,
                    'token'   => $a->token_number,
                    'type'    => $a->type,
                    'status'  => $a->status,
                ],
            ];
        });

        return response()->json($events);
    }
}
