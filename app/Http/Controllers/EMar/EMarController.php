<?php

namespace App\Http\Controllers\EMar;

use App\Http\Controllers\Controller;
use App\Models\MedicationAdministration;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EMarController extends Controller
{
    public function index(Request $request)
    {
        $fromPatient = $request->filled('patient_id') ? Patient::find($request->patient_id) : null;

        $scheduledCount   = MedicationAdministration::where('status', 'scheduled')->count();
        $administeredCount = MedicationAdministration::where('status', 'administered')->whereDate('administered_at', today())->count();
        $missedCount      = MedicationAdministration::where('status', 'missed')->count();
        $prnCount         = MedicationAdministration::where('is_prn', true)->where('status', 'scheduled')->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')
            ->get(['id', 'name', 'mrn'])
            ->sortBy(fn($p) => $p->name)
            ->values();

        return view('emar.index', compact(
            'scheduledCount', 'administeredCount', 'missedCount', 'prnCount', 'patients', 'fromPatient'
        ));
    }

    public function data(Request $request)
    {
        $query = MedicationAdministration::with(['patient:id,name,mrn', 'nurse:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('admitted_only'), fn($q) => $q->whereHas('patient.currentAdmission'))
            ->latest();

        return DataTables::of($query)
            ->addColumn('patient_info', fn($m) => e($m->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($m->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('drug_display', fn($m) => e($m->drug_name) . ($m->is_controlled ? ' <span class="badge bg-label-dark ms-1">Controlled</span>' : ''))
            ->addColumn('frequency_display', fn($m) => ($m->frequency ?: '—') . ($m->is_prn ? ' <span class="badge bg-label-warning ms-1">PRN</span>' : ''))
            ->editColumn('scheduled_time', fn($m) => $m->scheduled_time ? substr($m->scheduled_time, 0, 5) : '—')
            ->addColumn('status_badge', function ($m) {
                $map = ['scheduled' => 'primary', 'administered' => 'success', 'missed' => 'danger', 'refused' => 'secondary', 'held' => 'warning'];
                return '<span class="badge bg-label-' . ($map[$m->status] ?? 'secondary') . '">' . $m->status . '</span>';
            })
            ->addColumn('actions', fn($m) => '
                <div class="d-flex gap-2">
                    ' . ($m->status === 'scheduled' ? '<button onclick="openAdminister(' . $m->id . ')" class="btn btn-sm btn-icon btn-label-success" title="Administer"><i class="icon-base ti tabler-injection"></i></button>' : '') . '
                    <button onclick="deleteMar(' . $m->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
                </div>
            ')
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->rawColumns(['patient_info', 'drug_display', 'frequency_display', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'drug_name'      => 'required|string|max:255',
            'dosage'         => 'required|string|max:100',
            'route'          => 'nullable|string|max:50',
            'frequency'      => 'nullable|string|max:50',
            'scheduled_time' => 'nullable|date_format:H:i',
            'notes'          => 'nullable|string|max:1000',
            'is_prn'         => 'nullable|boolean',
            'is_controlled'  => 'nullable|boolean',
        ], $this->validationMessages());

        MedicationAdministration::create([
            'patient_id'     => $request->patient_id,
            'admission_id'   => Patient::find($request->patient_id)?->currentAdmission?->id,
            'drug_name'      => $request->drug_name,
            'dosage'         => $request->dosage,
            'route'          => $request->route,
            'frequency'      => $request->frequency,
            'scheduled_time' => $request->scheduled_time,
            'status'         => 'scheduled',
            'notes'          => $request->notes,
            'is_prn'         => $request->boolean('is_prn'),
            'is_controlled'  => $request->boolean('is_controlled'),
        ]);

        return back()->with('success', 'Medication order added!');
    }

    public function administer(Request $request, MedicationAdministration $mar)
    {
        $request->validate([
            'status' => 'required|in:administered,missed,refused,held',
            'notes'  => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $mar->update([
            'status'          => $request->status,
            'nurse_id'        => auth()->id(),
            'administered_at' => $request->status === 'administered' ? now() : null,
            'notes'           => $request->notes ?? $mar->notes,
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, MedicationAdministration $mar)
    {
        $request->validate([
            'drug_name'      => 'required|string|max:255',
            'dosage'         => 'required|string|max:100',
            'route'          => 'nullable|string|max:50',
            'frequency'      => 'nullable|string|max:50',
            'scheduled_time' => 'nullable|date_format:H:i',
            'notes'          => 'nullable|string|max:1000',
            'is_prn'         => 'nullable|boolean',
            'is_controlled'  => 'nullable|boolean',
        ], $this->validationMessages());

        $mar->update([
            'drug_name'      => $request->drug_name,
            'dosage'         => $request->dosage,
            'route'          => $request->route,
            'frequency'      => $request->frequency,
            'scheduled_time' => $request->scheduled_time,
            'notes'          => $request->notes,
            'is_prn'         => $request->boolean('is_prn'),
            'is_controlled'  => $request->boolean('is_controlled'),
        ]);

        return back()->with('success', 'Medication order updated!');
    }

    public function destroy(MedicationAdministration $mar)
    {
        $mar->delete();
        return response()->json(['success' => true]);
    }
}
