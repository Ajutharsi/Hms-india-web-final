<?php

namespace App\Http\Controllers\Radiology;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\RadiologyOrder;
use App\Support\DataTableFormat;
use App\Traits\AuthorizesAccess;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RadiologyController extends Controller
{
    use AuthorizesAccess;

    private function authorizeOrderAccess(RadiologyOrder $order): void
    {
        if ($this->isPatientRole()) {
            $patient = $this->getAuthPatient();
            $this->abortIfNotOwner(
                (bool) ($patient && $order->patient_id === $patient->id),
                'You are not authorized to view this radiology order.'
            );
        }

        if ($this->isDoctorRole()) {
            $doctor = $this->getAuthDoctor();
            $this->abortIfNotOwner(
                (bool) ($doctor && $order->doctor_id === $doctor->id),
                'You are not authorized to view this radiology order.'
            );
        }
    }

    public function index(Request $request)
    {
        $fromPatient = $request->filled('patient_id') ? Patient::find($request->patient_id) : null;
        $fromDoctor  = $request->filled('doctor_id') ? Doctor::find($request->doctor_id) : null;

        $statsQuery = RadiologyOrder::query();
        if ($this->isPatientRole()) {
            $patient = $this->getAuthPatient();
            $patient ? $statsQuery->where('patient_id', $patient->id) : $statsQuery->whereRaw('1 = 0');
        } elseif ($this->isDoctorRole()) {
            $doctor = $this->getAuthDoctor();
            $doctor ? $statsQuery->where('doctor_id', $doctor->id) : $statsQuery->whereRaw('1 = 0');
        }

        $totalOrders     = (clone $statsQuery)->count();
        $pendingOrders   = (clone $statsQuery)->whereIn('status', ['ordered', 'scheduled', 'in_progress'])->count();
        $completedOrders = (clone $statsQuery)->where('status', 'completed')->count();
        $emergencyOrders = (clone $statsQuery)->where('priority', 'emergency')
            ->whereNotIn('status', ['completed', 'cancelled'])->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'photo']);

        return view('radiology.index', compact(
            'totalOrders', 'pendingOrders', 'completedOrders', 'emergencyOrders', 'patients', 'doctors', 'fromPatient', 'fromDoctor'
        ));
    }

    public function data(Request $request)
    {
        $query = RadiologyOrder::with(['patient:id,name,mrn', 'doctor:id,name,photo'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->latest();

        if ($this->isPatientRole()) {
            $patient = $this->getAuthPatient();
            $patient ? $query->where('patient_id', $patient->id) : $query->whereRaw('1 = 0');
        } elseif ($this->isDoctorRole()) {
            $doctor = $this->getAuthDoctor();
            $doctor ? $query->where('doctor_id', $doctor->id) : $query->whereRaw('1 = 0');
        }

        return DataTables::of($query)
            ->addColumn('patient_info', fn($o) => e($o->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($o->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('doctor_name', fn($o) => DataTableFormat::doctorInfo($o->doctor))
            ->addColumn('study_info', fn($o) => e($o->study_description) . '<br><small class="text-muted">' . e($o->body_part) . '</small>')
            ->editColumn('order_date', fn($o) => $o->order_date?->format('d M Y'))
            ->addColumn('actions', fn($o) => '
                <div class="d-flex gap-2">
                    ' . ($o->status !== 'completed' && $o->status !== 'cancelled' ? '<button onclick="openFindings(' . $o->id . ')" class="btn btn-sm btn-icon btn-label-success" title="Enter Findings"><i class="icon-base ti tabler-report-medical"></i></button>' : '') . '
                    <button onclick="deleteOrder(' . $o->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
                </div>
            ')
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->rawColumns(['patient_info', 'doctor_name', 'study_info', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'           => 'required|exists:patients,id',
            'doctor_id'            => 'nullable|exists:doctors,id',
            'modality'             => 'required|string|max:100',
            'body_part'            => 'required|string|max:100',
            'study_description'    => 'required|string|max:255',
            'clinical_indication'  => 'nullable|string|max:1000',
            'priority'             => 'required|in:routine,urgent,emergency',
            'scheduled_at'         => 'nullable|date',
        ], $this->validationMessages());

        RadiologyOrder::create([
            'patient_id'          => $request->patient_id,
            'doctor_id'           => $request->doctor_id,
            'modality'            => $request->modality,
            'body_part'           => $request->body_part,
            'study_description'   => $request->study_description,
            'clinical_indication' => $request->clinical_indication,
            'priority'            => $request->priority,
            'status'              => 'ordered',
            'order_date'          => now()->toDateString(),
            'scheduled_at'        => $request->scheduled_at,
        ]);

        return back()->with('success', 'Radiology order created!');
    }

    public function show(RadiologyOrder $order)
    {
        $this->authorizeOrderAccess($order);

        $order->load(['patient', 'doctor', 'performedBy']);
        return response()->json($order);
    }

    public function update(Request $request, RadiologyOrder $order)
    {
        $request->validate([
            'modality'             => 'required|string|max:100',
            'body_part'            => 'required|string|max:100',
            'study_description'    => 'required|string|max:255',
            'clinical_indication'  => 'nullable|string|max:1000',
            'priority'             => 'required|in:routine,urgent,emergency',
            'status'               => 'required|in:ordered,scheduled,in_progress,completed,cancelled',
            'scheduled_at'         => 'nullable|date',
        ], $this->validationMessages());

        $order->update($request->only([
            'modality', 'body_part', 'study_description', 'clinical_indication',
            'priority', 'status', 'scheduled_at',
        ]));

        return back()->with('success', 'Radiology order updated!');
    }

    public function enterFindings(Request $request, RadiologyOrder $order)
    {
        $request->validate([
            'findings'         => 'nullable|string|max:5000',
            'impression'       => 'nullable|string|max:2000',
            'radiologist_name' => 'nullable|string|max:255',
        ], $this->validationMessages());

        $order->update([
            'findings'         => $request->findings,
            'impression'       => $request->impression,
            'radiologist_name' => $request->radiologist_name,
            'performed_by'     => auth()->id(),
            'status'           => 'completed',
            'completed_at'     => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(RadiologyOrder $order)
    {
        $order->delete();
        return response()->json(['success' => true]);
    }
}
