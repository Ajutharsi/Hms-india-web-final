<?php

namespace App\Http\Controllers\Nursing;

use App\Http\Controllers\Controller;
use App\Models\NursingNote;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class NursingNoteController extends Controller
{
    public function index(Request $request)
    {
        $fromPatient = $request->filled('patient_id') ? Patient::find($request->patient_id) : null;

        $todayCount    = NursingNote::whereDate('noted_at', today())->count();
        $handoverCount = NursingNote::where('note_type', 'handover')->whereDate('noted_at', today())->count();
        $urgentCount   = NursingNote::whereIn('priority', ['urgent', 'critical'])->whereDate('noted_at', today())->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();

        return view('nursing.index', compact('todayCount', 'handoverCount', 'urgentCount', 'patients', 'fromPatient'));
    }

    public function data(Request $request)
    {
        $query = NursingNote::with(['patient:id,name,mrn', 'writtenBy:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('admitted_only'), fn($q) => $q->whereHas('patient.currentAdmission'))
            ->latest('noted_at');

        return DataTables::of($query)
            ->addColumn('patient_info', fn($n) => e($n->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($n->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('note_preview', fn($n) => \Illuminate\Support\Str::limit($n->note, 80))
            ->addColumn('written_by_name', fn($n) => $n->writtenBy->name ?? 'N/A')
            ->editColumn('noted_at', fn($n) => $n->noted_at?->format('d M Y h:i A'))
            ->addColumn('actions', fn($n) => '
                <div class="d-flex gap-2">
                    <button onclick="deleteNote(' . $n->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
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
            'patient_id' => 'required|exists:patients,id',
            'note_type'  => 'required|in:nursing,progress,handover,assessment,discharge_summary',
            'shift'      => 'nullable|in:morning,afternoon,night',
            'note'       => 'required|string|max:5000',
            'priority'   => 'required|in:routine,urgent,critical',
        ], $this->validationMessages());

        NursingNote::create([
            'patient_id'   => $request->patient_id,
            'written_by'   => auth()->id(),
            'admission_id' => Patient::find($request->patient_id)?->currentAdmission?->id,
            'note_type'    => $request->note_type,
            'shift'        => $request->shift,
            'note'         => $request->note,
            'priority'     => $request->priority,
            'noted_at'     => now(),
        ]);

        return back()->with('success', 'Note added successfully!');
    }

    public function show(NursingNote $note)
    {
        $note->load(['patient', 'writtenBy']);
        return response()->json($note);
    }

    public function update(Request $request, NursingNote $note)
    {
        $request->validate([
            'note_type' => 'required|in:nursing,progress,handover,assessment,discharge_summary',
            'shift'     => 'nullable|in:morning,afternoon,night',
            'note'      => 'required|string|max:5000',
            'priority'  => 'required|in:routine,urgent,critical',
        ], $this->validationMessages());

        $note->update($request->only(['note_type', 'shift', 'note', 'priority']));

        return back()->with('success', 'Note updated successfully!');
    }

    public function destroy(NursingNote $note)
    {
        $note->delete();
        return response()->json(['success' => true]);
    }
}
