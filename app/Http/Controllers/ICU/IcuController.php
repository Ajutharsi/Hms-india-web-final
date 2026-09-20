<?php

namespace App\Http\Controllers\ICU;

use App\Http\Controllers\Controller;
use App\Models\IcuChart;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class IcuController extends Controller
{
    public function index()
    {
        $icuPatientsCount = IcuChart::whereDate('charted_at', today())
            ->distinct('patient_id')->count('patient_id');
        $criticalCount = IcuChart::whereDate('charted_at', today())
            ->where('gcs_total', '<', 9)->distinct('patient_id')->count('patient_id');
        $onVentilatorCount = IcuChart::whereDate('charted_at', today())
            ->whereNotNull('ventilator_mode')->distinct('patient_id')->count('patient_id');
        $todayChartsCount = IcuChart::whereDate('charted_at', today())->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();

        return view('icu.index', compact(
            'icuPatientsCount', 'criticalCount', 'onVentilatorCount', 'todayChartsCount', 'patients'
        ));
    }

    public function data(Request $request)
    {
        $query = IcuChart::with(['patient:id,name,mrn', 'recordedBy:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->latest('charted_at');

        return DataTables::of($query)
            ->addColumn('patient_info', fn($c) => e($c->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($c->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('recorded_by_name', fn($c) => $c->recordedBy->name ?? 'N/A')
            ->editColumn('charted_at', fn($c) => $c->charted_at?->format('d M Y h:i A'))
            ->addColumn('actions', fn($c) => '
                <div class="d-flex gap-2">
                    <button onclick="deleteChart(' . $c->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
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
            'patient_id'           => 'required|exists:patients,id',
            'ventilator_mode'      => 'nullable|string|max:50',
            'fio2'                 => 'nullable|integer|min:0|max:100',
            'peep'                 => 'nullable|integer|min:0|max:50',
            'tidal_volume'         => 'nullable|integer|min:0',
            'respiratory_rate_set' => 'nullable|integer|min:0',
            'arterial_bp'          => 'nullable|string|max:20',
            'cvp'                  => 'nullable|integer',
            'heart_rate'           => 'nullable|integer|min:0|max:300',
            'rhythm'               => 'nullable|string|max:100',
            'lines_drains'         => 'nullable|string|max:1000',
            'gcs_eye'              => 'nullable|integer|min:1|max:4',
            'gcs_verbal'           => 'nullable|integer|min:1|max:5',
            'gcs_motor'            => 'nullable|integer|min:1|max:6',
            'intake_oral'          => 'nullable|numeric|min:0',
            'intake_iv'            => 'nullable|numeric|min:0',
            'output_urine'         => 'nullable|numeric|min:0',
            'output_drain'         => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:2000',
        ], $this->validationMessages());

        $chart = new IcuChart($request->only([
            'patient_id', 'ventilator_mode', 'fio2', 'peep', 'tidal_volume', 'respiratory_rate_set',
            'arterial_bp', 'cvp', 'heart_rate', 'rhythm', 'lines_drains',
            'gcs_eye', 'gcs_verbal', 'gcs_motor',
            'intake_oral', 'intake_iv', 'output_urine', 'output_drain', 'notes',
        ]));
        $chart->admission_id = Patient::find($request->patient_id)?->currentAdmission?->id;
        $chart->recorded_by  = auth()->id();
        $chart->charted_at   = now();
        $chart->save();

        return back()->with('success', 'ICU chart recorded successfully!');
    }

    public function show(IcuChart $chart)
    {
        $chart->load(['patient', 'recordedBy']);
        return response()->json($chart);
    }

    public function destroy(IcuChart $chart)
    {
        $chart->delete();
        return response()->json(['success' => true]);
    }
}
