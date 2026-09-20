<?php

namespace App\Http\Controllers\ShiftHandover;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\MedicationAdministration;
use App\Models\Patient;
use App\Models\ShiftHandover;
use App\Models\ShiftHandoverPatient;
use App\Models\ShiftHandoverTask;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ShiftHandoverController extends Controller
{
    /**
     * Nurses only see handovers they're party to (outgoing or incoming);
     * admin/super_admin see every ward's handovers. Shared by index/data/report.
     */
    private function scopedQuery()
    {
        $query = ShiftHandover::query();

        if (auth()->user()->hasRole('nurse')) {
            $query->where(fn($q) => $q->where('outgoing_nurse_id', auth()->id())->orWhere('incoming_nurse_id', auth()->id()));
        }

        return $query;
    }

    public function index()
    {
        $userId = auth()->id();

        $pendingCount   = ShiftHandover::where('incoming_nurse_id', $userId)->where('status', 'pending_acceptance')->count();
        $acceptedCount  = ShiftHandover::where('status', 'accepted')->where(fn($q) => $q->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId))->count();
        $todayCount     = $this->scopedQuery()->whereDate('handover_date', today())->count();
        $highPriorityCount = ShiftHandoverPatient::where('is_high_priority', true)
            ->whereHas('shiftHandover', fn($q) => $q->whereIn('status', ['pending_acceptance', 'accepted'])->where(fn($q2) => $q2->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId)))
            ->count();
        $pendingTasksCount = ShiftHandoverTask::where('is_completed', false)
            ->whereHas('shiftHandover', fn($q) => $q->where(fn($q2) => $q2->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId)))
            ->count();

        $recentAccepted = ShiftHandover::with(['ward', 'outgoingNurse', 'incomingNurse'])
            ->where('status', 'accepted')
            ->where(fn($q) => $q->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId))
            ->latest('accepted_at')
            ->take(5)
            ->get();

        // "My Current Shift" / "Next Shift" — no separate shift-roster module exists,
        // so this is read off this nurse's most recent handover involvement.
        $latestHandover = ShiftHandover::where(fn($q) => $q->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId))
            ->latest('handover_date')
            ->latest('id')
            ->first();

        $currentUser = auth()->user();

        return view('shift-handover.index', compact(
            'pendingCount', 'acceptedCount', 'todayCount', 'highPriorityCount', 'pendingTasksCount',
            'recentAccepted', 'latestHandover', 'currentUser'
        ));
    }

    public function data(Request $request)
    {
        $query = $this->scopedQuery()
            ->with(['ward:id,name', 'outgoingNurse:id,name', 'incomingNurse:id,name'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('ward_id'), fn($q) => $q->where('ward_id', $request->ward_id))
            ->latest('handover_date');

        return DataTables::of($query)
            ->addColumn('ward_name', fn($h) => $h->ward->name ?? 'N/A')
            ->addColumn('shift_info', fn($h) => ucfirst($h->current_shift) . ' &rarr; ' . ucfirst($h->next_shift))
            ->addColumn('outgoing_name', fn($h) => $h->outgoingNurse->name ?? 'N/A')
            ->addColumn('incoming_name', fn($h) => $h->incomingNurse->name ?? 'N/A')
            ->editColumn('handover_date', fn($h) => $h->handover_date?->format('d M Y'))
            ->addColumn('status_badge', function ($h) {
                $colors = ['draft' => 'secondary', 'pending_acceptance' => 'warning', 'accepted' => 'success', 'rejected' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$h->status] ?? 'secondary') . '">' . str_replace('_', ' ', $h->status) . '</span>';
            })
            ->addColumn('actions', fn($h) => '
                <div class="d-flex gap-2">
                    <a href="' . route('shift-handover.show', $h->id) . '" class="btn btn-sm btn-icon btn-label-info" title="View"><i class="icon-base ti tabler-eye"></i></a>
                    ' . ($h->status === 'draft' ? '<a href="' . route('shift-handover.edit', $h->id) . '" class="btn btn-sm btn-icon btn-label-warning" title="Edit"><i class="icon-base ti tabler-edit"></i></a>' : '') . '
                </div>
            ')
            ->rawColumns(['shift_info', 'status_badge', 'actions'])
            ->make(true);
    }

    /** AJAX: admitted patients in a ward, for the create-handover patient-card step. */
    public function wardPatients(Request $request)
    {
        $request->validate(['ward_id' => 'required|exists:wards,id']);

        $admissions = Admission::where('ward_id', $request->ward_id)->where('status', 'admitted')->get();

        return response()->json($admissions->map(
            fn($admission) => $this->cardPayload($admission->patient_id, $admission->id)
        )->values());
    }

    private function suggestedMedicationStatus(int $patientId): string
    {
        $today = MedicationAdministration::where('patient_id', $patientId)->whereDate('created_at', today());
        if ((clone $today)->where('status', 'missed')->exists()) return 'missed';
        if ((clone $today)->where('status', 'scheduled')->exists()) return 'pending';
        return 'completed';
    }

    public function create()
    {
        abort_unless(auth()->user()->can('create handovers'), 403);

        $wards  = Ward::active()->orderBy('name')->get(['id', 'name']);
        $nurses = User::role('nurse')->where('id', '!=', auth()->id())->orderBy('name')->get(['id', 'name']);

        // Redirected back here after a failed store() — rebuild the patient
        // cards from the flashed old() input so nothing typed gets lost.
        $existingPatients = old('patients')
            ? collect(old('patients'))->map(fn($row) => $this->cardPayload($row['patient_id'], $row['admission_id'] ?? null, $row))
            : collect();
        $existingTasks = collect(old('tasks', []));

        return view('shift-handover.create', [
            'handover' => null, 'existingPatients' => $existingPatients, 'existingTasks' => $existingTasks,
        ] + compact('wards', 'nurses'));
    }

    /** Normalized patient-card payload shared by wardPatients(), edit(), and the old()-input rebuild in create(). */
    private function cardPayload(int $patientId, ?int $admissionId, array $saved = []): array
    {
        $patient      = Patient::find($patientId);
        $admission    = $admissionId ? Admission::with('bed')->find($admissionId) : null;
        $latestVitals = $patient?->vitals()->latest('recorded_at')->first();

        return [
            'patient_id'           => $patientId,
            'admission_id'         => $admissionId,
            'name'                 => $patient->name ?? 'Unknown',
            'mrn'                  => $patient->mrn ?? 'N/A',
            'photo_url'            => $patient->photo_url ?? asset('images/defaults/patient.png'),
            'bed_no'               => $admission->bed->bed_no ?? 'N/A',
            'admission_date'       => $admission?->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : null,
            'vitals'               => $latestVitals ? [
                'blood_pressure'   => $latestVitals->blood_pressure,
                'pulse_rate'       => $latestVitals->pulse_rate,
                'temperature'      => $latestVitals->temperature,
                'respiratory_rate' => $latestVitals->respiratory_rate,
                'spo2'             => $latestVitals->spo2,
                'recorded_at'      => $latestVitals->recorded_at?->format('d M h:i A'),
            ] : null,
            'suggested_med_status' => $saved['medication_status'] ?? $this->suggestedMedicationStatus($patientId),
            'saved' => [
                'condition'           => $saved['condition'] ?? 'stable',
                'medication_status'   => $saved['medication_status'] ?? null,
                'iv_fluid_status'     => $saved['iv_fluid_status'] ?? null,
                'iv_next_change_time' => $saved['iv_next_change_time'] ?? null,
                'doctor_instructions' => $saved['doctor_instructions'] ?? null,
                'pending_procedures'  => $saved['pending_procedures'] ?? null,
                'pending_lab_reports' => $saved['pending_lab_reports'] ?? null,
                'pending_imaging'     => $saved['pending_imaging'] ?? null,
                'special_notes'       => $saved['special_notes'] ?? null,
                'is_high_priority'    => (bool) ($saved['is_high_priority'] ?? false),
            ],
        ];
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create handovers'), 403);

        $handover = $this->saveHandover($request);

        return redirect()->route('shift-handover.show', $handover)->with('success', 'Handover saved as draft.');
    }

    public function edit(ShiftHandover $shiftHandover)
    {
        abort_unless($shiftHandover->isEditable() && auth()->id() === $shiftHandover->outgoing_nurse_id, 403);

        $shiftHandover->load(['patients.patient', 'patients.admission.bed', 'tasks']);
        $wards  = Ward::active()->orderBy('name')->get(['id', 'name']);
        $nurses = User::role('nurse')->where('id', '!=', auth()->id())->orderBy('name')->get(['id', 'name']);

        // Same shape as wardPatients(), with saved field values merged in — so
        // the create/edit page can hydrate patient cards with one JS renderer
        // regardless of whether the data came from that AJAX call or here.
        $existingPatients = $shiftHandover->patients->map(fn($card) => $this->cardPayload($card->patient_id, $card->admission_id, [
            'condition'           => $card->condition,
            'medication_status'   => $card->medication_status,
            'iv_fluid_status'     => $card->iv_fluid_status,
            'iv_next_change_time' => $card->iv_next_change_time?->format('Y-m-d\TH:i'),
            'doctor_instructions' => $card->doctor_instructions,
            'pending_procedures'  => $card->pending_procedures,
            'pending_lab_reports' => $card->pending_lab_reports,
            'pending_imaging'     => $card->pending_imaging,
            'special_notes'       => $card->special_notes,
            'is_high_priority'    => $card->is_high_priority,
        ]));

        $existingTasks = $shiftHandover->tasks->map(fn($t) => ['patient_id' => $t->patient_id, 'task_description' => $t->task_description]);

        return view('shift-handover.create', ['handover' => $shiftHandover, 'existingPatients' => $existingPatients, 'existingTasks' => $existingTasks] + compact('wards', 'nurses'));
    }

    public function update(Request $request, ShiftHandover $shiftHandover)
    {
        abort_unless($shiftHandover->isEditable() && auth()->id() === $shiftHandover->outgoing_nurse_id, 403);

        $this->saveHandover($request, $shiftHandover);

        return redirect()->route('shift-handover.show', $shiftHandover)->with('success', 'Handover updated.');
    }

    private function saveHandover(Request $request, ?ShiftHandover $handover = null): ShiftHandover
    {
        $request->validate([
            'ward_id'               => 'required|exists:wards,id',
            'current_shift'         => 'required|in:morning,evening,night',
            'next_shift'            => 'required|in:morning,evening,night',
            'incoming_nurse_id'     => 'required|exists:users,id|different:outgoing_nurse_id',
            'handover_date'         => 'required|date',
            'shift_summary'         => 'nullable|string|max:5000',
            'patients'                              => 'required|array|min:1',
            'patients.*.patient_id'                 => 'required|exists:patients,id',
            'patients.*.admission_id'                => 'nullable|exists:admissions,id',
            'patients.*.condition'                   => 'required|in:stable,improving,critical,observation',
            'patients.*.medication_status'           => 'required|in:completed,pending,missed',
            'patients.*.iv_fluid_status'              => 'nullable|in:running,completed',
            'patients.*.iv_next_change_time'          => 'nullable|date',
            'patients.*.doctor_instructions'          => 'nullable|string|max:2000',
            'patients.*.pending_procedures'           => 'nullable|string|max:2000',
            'patients.*.pending_lab_reports'          => 'nullable|string|max:2000',
            'patients.*.pending_imaging'              => 'nullable|string|max:2000',
            'patients.*.special_notes'                => 'nullable|string|max:2000',
            'patients.*.is_high_priority'             => 'nullable|boolean',
            'tasks'                    => 'nullable|array',
            'tasks.*.patient_id'       => 'nullable|exists:patients,id',
            'tasks.*.task_description' => 'required_with:tasks|string|max:500',
        ], $this->validationMessages());

        $handover = DB::transaction(function () use ($request, $handover) {
            $data = [
                'ward_id'           => $request->ward_id,
                'current_shift'     => $request->current_shift,
                'next_shift'        => $request->next_shift,
                'incoming_nurse_id' => $request->incoming_nurse_id,
                'handover_date'     => $request->handover_date,
                'shift_summary'     => $request->shift_summary,
            ];

            if ($handover) {
                $handover->update($data);
                $handover->patients()->delete();
                $handover->tasks()->delete();
            } else {
                $data['outgoing_nurse_id'] = auth()->id();
                $data['status']            = 'draft';
                $handover = ShiftHandover::create($data);
            }

            foreach ($request->input('patients', []) as $card) {
                ShiftHandoverPatient::create([
                    'shift_handover_id'    => $handover->id,
                    'patient_id'           => $card['patient_id'],
                    'admission_id'         => $card['admission_id'] ?? null,
                    'condition'            => $card['condition'],
                    'medication_status'    => $card['medication_status'],
                    'iv_fluid_status'      => $card['iv_fluid_status'] ?? null,
                    'iv_next_change_time'  => $card['iv_next_change_time'] ?? null,
                    'doctor_instructions'  => $card['doctor_instructions'] ?? null,
                    'pending_procedures'   => $card['pending_procedures'] ?? null,
                    'pending_lab_reports'  => $card['pending_lab_reports'] ?? null,
                    'pending_imaging'      => $card['pending_imaging'] ?? null,
                    'special_notes'        => $card['special_notes'] ?? null,
                    'is_high_priority'     => (bool) ($card['is_high_priority'] ?? false),
                ]);
            }

            foreach ($request->input('tasks', []) as $task) {
                if (empty($task['task_description'])) continue;
                ShiftHandoverTask::create([
                    'shift_handover_id' => $handover->id,
                    'patient_id'        => $task['patient_id'] ?: null,
                    'task_description'  => $task['task_description'],
                ]);
            }

            return $handover;
        });

        return $handover;
    }

    public function show(ShiftHandover $shiftHandover)
    {
        abort_unless(
            auth()->user()->hasAnyRole(['super_admin', 'admin'])
            || in_array(auth()->id(), [$shiftHandover->outgoing_nurse_id, $shiftHandover->incoming_nurse_id]),
            403
        );

        $shiftHandover->load(['ward', 'outgoingNurse', 'incomingNurse', 'patients.patient', 'patients.admission.bed', 'tasks.patient']);

        return view('shift-handover.show', compact('shiftHandover'));
    }

    public function submit(ShiftHandover $shiftHandover)
    {
        abort_unless($shiftHandover->isEditable() && auth()->id() === $shiftHandover->outgoing_nurse_id, 403);
        abort_unless(auth()->user()->can('create handovers'), 403);

        $shiftHandover->update(['status' => 'pending_acceptance', 'submitted_at' => now()]);

        ActivityLog::log(
            'submitted', 'Handover',
            "Handover {$shiftHandover->handover_no} submitted for {$shiftHandover->ward->name} ({$shiftHandover->current_shift} \u{2192} {$shiftHandover->next_shift})",
            $shiftHandover
        );

        return redirect()->route('shift-handover.show', $shiftHandover)->with('success', 'Handover submitted — awaiting acceptance by the incoming nurse.');
    }

    public function accept(ShiftHandover $shiftHandover)
    {
        abort_unless(auth()->user()->can('accept handovers'), 403);
        abort_unless($shiftHandover->isPending() && auth()->id() === $shiftHandover->incoming_nurse_id, 403);

        $shiftHandover->update(['status' => 'accepted', 'accepted_at' => now()]);

        ActivityLog::log('accepted', 'Handover', "Handover {$shiftHandover->handover_no} accepted by " . auth()->user()->name, $shiftHandover);

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, ShiftHandover $shiftHandover)
    {
        abort_unless(auth()->user()->can('accept handovers'), 403);
        abort_unless($shiftHandover->isPending() && auth()->id() === $shiftHandover->incoming_nurse_id, 403);

        $request->validate(['reason' => 'required|string|max:1000'], $this->validationMessages());

        $shiftHandover->update([
            'status'            => 'rejected',
            'rejected_at'       => now(),
            'rejection_reason'  => $request->reason,
        ]);

        ActivityLog::log('rejected', 'Handover', "Handover {$shiftHandover->handover_no} rejected by " . auth()->user()->name . ': ' . $request->reason, $shiftHandover);

        return response()->json(['success' => true]);
    }

    /** Navbar badge — pending handovers awaiting this nurse's acceptance. */
    public function alertCount()
    {
        $count = ShiftHandover::where('incoming_nurse_id', auth()->id())->where('status', 'pending_acceptance')->count();

        return response()->json(['count' => $count]);
    }

    public function report(Request $request)
    {
        $wards  = Ward::active()->orderBy('name')->get(['id', 'name']);
        $nurses = User::role('nurse')->orderBy('name')->get(['id', 'name']);

        $handovers = $this->scopedQuery()
            ->with(['ward', 'outgoingNurse', 'incomingNurse', 'patients'])
            ->when($request->filled('from_date'), fn($q) => $q->whereDate('handover_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate('handover_date', '<=', $request->to_date))
            ->when($request->filled('ward_id'), fn($q) => $q->where('ward_id', $request->ward_id))
            ->when($request->filled('shift'), fn($q) => $q->where('current_shift', $request->shift))
            ->when($request->filled('nurse_id'), fn($q) => $q->where(fn($q2) => $q2->where('outgoing_nurse_id', $request->nurse_id)->orWhere('incoming_nurse_id', $request->nurse_id)))
            ->when($request->filled('patient_id'), fn($q) => $q->whereHas('patients', fn($p) => $p->where('patient_id', $request->patient_id)))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest('handover_date')
            ->get();

        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();

        return view('shift-handover.report', compact('handovers', 'wards', 'nurses', 'patients'));
    }

    public function print(ShiftHandover $shiftHandover)
    {
        abort_unless(
            auth()->user()->hasAnyRole(['super_admin', 'admin'])
            || in_array(auth()->id(), [$shiftHandover->outgoing_nurse_id, $shiftHandover->incoming_nurse_id]),
            403
        );

        $shiftHandover->load(['ward', 'outgoingNurse', 'incomingNurse', 'patients.patient', 'patients.admission.bed', 'tasks.patient']);

        return view('shift-handover.print', compact('shiftHandover'));
    }

    public function destroy(ShiftHandover $shiftHandover)
    {
        abort_unless($shiftHandover->isEditable() && auth()->id() === $shiftHandover->outgoing_nurse_id, 403);

        $shiftHandover->delete();

        return response()->json(['success' => true]);
    }
}
