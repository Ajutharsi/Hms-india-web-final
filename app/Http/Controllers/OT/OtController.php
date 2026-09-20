<?php

namespace App\Http\Controllers\OT;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\OtSchedule;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OtController extends Controller
{
    public function index()
    {
        $todayCount      = OtSchedule::whereDate('scheduled_start', today())->count();
        $scheduledCount  = OtSchedule::where('status', 'scheduled')->count();
        $inProgressCount = OtSchedule::where('status', 'in_progress')->count();
        $completedCount  = OtSchedule::where('status', 'completed')->whereDate('scheduled_start', today())->count();

        $todayOt = OtSchedule::with(['patient', 'surgeon'])
            ->whereDate('scheduled_start', today())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('scheduled_start')
            ->get();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $nurses   = User::role('nurse')->orderBy('name')->get(['id', 'name']);

        return view('ot.index', compact(
            'todayCount', 'scheduledCount', 'inProgressCount', 'completedCount',
            'todayOt', 'patients', 'doctors', 'nurses'
        ));
    }

    public function data(Request $request)
    {
        $query = OtSchedule::with(['patient:id,name,mrn', 'surgeon:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->latest('scheduled_start');

        return DataTables::of($query)
            ->addColumn('patient_info', fn($o) => e($o->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($o->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('surgeon_name', fn($o) => $o->surgeon->name ?? '—')
            ->editColumn('scheduled_start', fn($o) => $o->scheduled_start?->format('d M Y h:i A'))
            ->addColumn('priority_badge', function ($o) {
                $colors = ['emergency' => 'danger', 'urgent' => 'warning', 'elective' => 'success'];
                return '<span class="badge bg-label-' . ($colors[$o->priority] ?? 'secondary') . '">' . $o->priority . '</span>';
            })
            ->addColumn('status_badge', function ($o) {
                $map = ['scheduled' => 'secondary', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'danger', 'postponed' => 'warning'];
                return '<span class="badge bg-label-' . ($map[$o->status] ?? 'secondary') . '">' . str_replace('_', ' ', $o->status) . '</span>';
            })
            ->addColumn('actions', function ($o) {
                $btns = '<div class="d-flex gap-2">';
                if ($o->status === 'scheduled') {
                    $btns .= '<button onclick="startOt(' . $o->id . ')" class="btn btn-sm btn-icon btn-label-info" title="Start"><i class="icon-base ti tabler-player-play"></i></button>';
                }
                if ($o->status === 'in_progress') {
                    $btns .= '<button onclick="openComplete(' . $o->id . ')" class="btn btn-sm btn-icon btn-label-success" title="Complete"><i class="icon-base ti tabler-check"></i></button>';
                }
                if ($o->status === 'completed') {
                    $btns .= '<a href="' . route('ward.admissions', ['patient_id' => $o->patient_id]) . '" class="btn btn-sm btn-icon btn-label-primary" title="Admit to Ward"><i class="icon-base ti tabler-bed"></i></a>';
                }
                if ($o->status !== 'completed' && $o->status !== 'cancelled') {
                    $btns .= '<button onclick="deleteOt(' . $o->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Cancel"><i class="icon-base ti tabler-trash"></i></button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->rawColumns(['patient_info', 'priority_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'surgeon_id'        => 'nullable|exists:doctors,id',
            'anaesthetist_id'   => 'nullable|exists:doctors,id',
            'scrub_nurse_id'    => 'nullable|exists:users,id',
            'theatre_no'        => 'nullable|string|max:50',
            'procedure_name'    => 'required|string|max:255',
            'icd10_code'        => 'nullable|string|max:20',
            'anaesthesia_type'  => 'nullable|in:general,spinal,epidural,local,sedation',
            'priority'          => 'required|in:elective,urgent,emergency',
            'scheduled_start'   => 'required|date',
            'scheduled_end'     => 'nullable|date|after:scheduled_start',
            'pre_op_notes'      => 'nullable|string|max:2000',
        ], $this->validationMessages());

        OtSchedule::create([
            'patient_id'       => $request->patient_id,
            'surgeon_id'       => $request->surgeon_id,
            'anaesthetist_id'  => $request->anaesthetist_id,
            'scrub_nurse_id'   => $request->scrub_nurse_id,
            'theatre_no'       => $request->theatre_no,
            'procedure_name'   => $request->procedure_name,
            'icd10_code'       => $request->icd10_code,
            'anaesthesia_type' => $request->anaesthesia_type,
            'status'           => 'scheduled',
            'priority'         => $request->priority,
            'scheduled_start'  => $request->scheduled_start,
            'scheduled_end'    => $request->scheduled_end,
            'pre_op_notes'     => $request->pre_op_notes,
        ]);

        return back()->with('success', 'OT scheduled successfully!');
    }

    public function show(OtSchedule $ot)
    {
        $ot->load(['patient', 'surgeon', 'anaesthetist', 'scrubNurse']);
        return response()->json($ot);
    }

    public function update(Request $request, OtSchedule $ot)
    {
        $request->validate([
            'surgeon_id'        => 'nullable|exists:doctors,id',
            'anaesthetist_id'   => 'nullable|exists:doctors,id',
            'scrub_nurse_id'    => 'nullable|exists:users,id',
            'theatre_no'        => 'nullable|string|max:50',
            'procedure_name'    => 'required|string|max:255',
            'anaesthesia_type'  => 'nullable|in:general,spinal,epidural,local,sedation',
            'priority'          => 'required|in:elective,urgent,emergency',
            'status'            => 'required|in:scheduled,in_progress,completed,cancelled,postponed',
            'scheduled_start'   => 'required|date',
            'scheduled_end'     => 'nullable|date|after:scheduled_start',
        ], $this->validationMessages());

        $ot->update($request->only([
            'surgeon_id', 'anaesthetist_id', 'scrub_nurse_id', 'theatre_no',
            'procedure_name', 'anaesthesia_type', 'priority', 'status',
            'scheduled_start', 'scheduled_end',
        ]));

        return back()->with('success', 'OT schedule updated!');
    }

    public function start(OtSchedule $ot)
    {
        $ot->update([
            'status'       => 'in_progress',
            'actual_start' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function complete(Request $request, OtSchedule $ot)
    {
        $request->validate([
            'post_op_notes' => 'nullable|string|max:2000',
            'complications' => 'nullable|string|max:2000',
        ], $this->validationMessages());

        $ot->update([
            'status'         => 'completed',
            'actual_end'     => now(),
            'post_op_notes'  => $request->post_op_notes,
            'complications'  => $request->complications,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(OtSchedule $ot)
    {
        $ot->update(['status' => 'cancelled']);
        return response()->json(['success' => true]);
    }
}
