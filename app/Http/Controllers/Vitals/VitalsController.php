<?php

namespace App\Http\Controllers\Vitals;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Vital;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VitalsController extends Controller
{
    public function index(Request $request)
    {
        $fromPatient = $request->filled('patient_id') ? Patient::find($request->patient_id) : null;

        $todayCount    = Vital::whereDate('recorded_at', today())->count();
        $criticalCount = Vital::whereDate('recorded_at', today())->where('spo2', '<', 94)->count();
        $highBpCount   = Vital::whereDate('recorded_at', today())
            ->whereNotNull('blood_pressure')
            ->get()
            ->filter(fn($v) => (int) explode('/', $v->blood_pressure)[0] >= 140)
            ->count();
        $lowBpCount    = Vital::whereDate('recorded_at', today())
            ->whereNotNull('blood_pressure')
            ->get()
            ->filter(fn($v) => (int) explode('/', $v->blood_pressure)[0] < 90)
            ->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();

        return view('vitals.index', compact(
            'todayCount', 'criticalCount', 'highBpCount', 'lowBpCount', 'patients', 'fromPatient'
        ));
    }

    public function data(Request $request)
    {
        $query = Vital::with(['patient:id,name,mrn', 'recordedBy:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('admitted_only'), fn($q) => $q->whereHas('patient.currentAdmission'))
            ->latest('recorded_at');

        return DataTables::of($query)
            ->addColumn('patient_info', fn($v) => e($v->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($v->patient->mrn ?? 'N/A') . '</small>')
            ->editColumn('recorded_at', fn($v) => $v->recorded_at?->format('d M Y h:i A'))
            ->addColumn('recorded_by_name', fn($v) => $v->recordedBy->name ?? 'N/A')
            ->addColumn('actions', fn($v) => '
                <div class="d-flex gap-2">
                    <button onclick="viewTrend(' . $v->patient_id . ', ' . json_encode(e($v->patient->name ?? '')) . ')" class="btn btn-sm btn-icon btn-label-info" title="Trend"><i class="icon-base ti tabler-chart-line"></i></button>
                    <button onclick="deleteVital(' . $v->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
                </div>
            ')
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->rawColumns(['patient_info', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'blood_pressure'    => 'nullable|string|max:20',
            'temperature'       => 'nullable|numeric|min:20|max:45',
            'pulse_rate'        => 'nullable|integer|min:0|max:300',
            'respiratory_rate'  => 'nullable|integer|min:0|max:100',
            'spo2'              => 'nullable|integer|min:0|max:100',
            'weight'            => 'nullable|numeric|min:0|max:500',
            'height'            => 'nullable|numeric|min:0|max:300',
            'notes'             => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $vital = new Vital($request->only([
            'patient_id', 'blood_pressure', 'temperature', 'pulse_rate',
            'respiratory_rate', 'spo2', 'weight', 'height', 'notes',
        ]));
        $vital->admission_id = Patient::find($request->patient_id)?->currentAdmission?->id;
        $vital->recorded_by  = auth()->id();
        $vital->recorded_at  = now();
        $vital->news_score   = $vital->newsScore();
        $vital->save();

        return back()->with('success', 'Vitals recorded successfully!');
    }

    public function show(Patient $patient)
    {
        $vitals = Vital::where('patient_id', $patient->id)
            ->orderBy('recorded_at', 'desc')
            ->take(50)
            ->get();

        return response()->json(['data' => $vitals]);
    }

    public function destroy(Vital $vital)
    {
        $vital->delete();
        return response()->json(['success' => true]);
    }
}
