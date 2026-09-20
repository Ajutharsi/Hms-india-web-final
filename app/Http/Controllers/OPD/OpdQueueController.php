<?php

namespace App\Http\Controllers\OPD;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\OpdToken;
use App\Models\Patient;
use App\Support\DataTableFormat;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OpdQueueController extends Controller
{
    public function index()
    {
        $totalToday      = OpdToken::whereDate('token_date', today())->count();
        $waitingCount    = OpdToken::whereDate('token_date', today())->where('status', 'waiting')->count();
        $inConsultCount  = OpdToken::whereDate('token_date', today())->where('status', 'in_consultation')->count();
        $completedCount  = OpdToken::whereDate('token_date', today())->where('status', 'completed')->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'photo']);

        return view('opd.queue.index', compact(
            'totalToday', 'waitingCount', 'inConsultCount', 'completedCount', 'patients', 'doctors'
        ));
    }

    public function data()
    {
        $query = OpdToken::with(['patient:id,name,mrn', 'doctor:id,name,photo'])
            ->whereDate('token_date', today())
            ->orderBy('queue_position');

        return DataTables::of($query)
            ->addColumn('patient_info', fn($t) => e($t->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($t->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('doctor_name', fn($t) => DataTableFormat::doctorInfo($t->doctor))
            ->editColumn('called_at', fn($t) => $t->called_at?->format('h:i A'))
            ->addColumn('status_badge', function ($t) {
                $map = ['waiting' => 'warning', 'in_consultation' => 'info', 'completed' => 'success', 'skipped' => 'secondary', 'cancelled' => 'danger'];
                return '<span class="badge bg-label-' . ($map[$t->status] ?? 'secondary') . '">' . str_replace('_', ' ', $t->status) . '</span>';
            })
            ->addColumn('actions', function ($t) {
                $btns = '<div class="d-flex gap-2">';
                if ($t->status === 'waiting') {
                    $btns .= '<button onclick="setStatus(' . $t->id . ',\'in_consultation\')" class="btn btn-sm btn-icon btn-label-info" title="Call In"><i class="icon-base ti tabler-phone-call"></i></button>';
                    $btns .= '<button onclick="setStatus(' . $t->id . ',\'skipped\')" class="btn btn-sm btn-icon btn-label-warning" title="Skip"><i class="icon-base ti tabler-player-skip-forward"></i></button>';
                }
                if ($t->status === 'in_consultation') {
                    $btns .= '<button onclick="setStatus(' . $t->id . ',\'completed\')" class="btn btn-sm btn-icon btn-label-success" title="Complete"><i class="icon-base ti tabler-check"></i></button>';
                }
                $btns .= '<button onclick="deleteToken(' . $t->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Cancel"><i class="icon-base ti tabler-trash"></i></button>';
                $btns .= '</div>';
                return $btns;
            })
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->filterColumn('doctor_name', fn($q, $kw) => $q->whereHas('doctor', fn($d) => $d->where('name', 'like', "%{$kw}%")))
            ->rawColumns(['patient_info', 'doctor_name', 'status_badge', 'actions'])
            ->make(true);
    }

    public function stats()
    {
        return response()->json([
            'total'        => OpdToken::whereDate('token_date', today())->count(),
            'waiting'      => OpdToken::whereDate('token_date', today())->where('status', 'waiting')->count(),
            'in_consult'   => OpdToken::whereDate('token_date', today())->where('status', 'in_consultation')->count(),
            'completed'    => OpdToken::whereDate('token_date', today())->where('status', 'completed')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id'  => 'required|exists:doctors,id',
            'notes'      => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $position = OpdToken::whereDate('token_date', today())
            ->where('doctor_id', $request->doctor_id)
            ->max('queue_position') + 1;

        OpdToken::create([
            'patient_id'     => $request->patient_id,
            'doctor_id'      => $request->doctor_id,
            'token_date'     => today(),
            'status'         => 'waiting',
            'queue_position' => $position,
            'notes'          => $request->notes,
        ]);

        return back()->with('success', 'Token issued successfully!');
    }

    public function callNext(Doctor $doctor)
    {
        $current = OpdToken::whereDate('token_date', today())
            ->where('doctor_id', $doctor->id)
            ->where('status', 'in_consultation')
            ->first();

        if ($current) {
            $current->update(['status' => 'completed', 'completed_at' => now()]);
        }

        $next = OpdToken::whereDate('token_date', today())
            ->where('doctor_id', $doctor->id)
            ->where('status', 'waiting')
            ->orderBy('queue_position')
            ->first();

        if (!$next) {
            return response()->json(['message' => 'No patients waiting in queue.'], 404);
        }

        $next->update(['status' => 'in_consultation', 'called_at' => now()]);

        return response()->json(['success' => true, 'token' => $next]);
    }

    public function updateStatus(Request $request, OpdToken $token)
    {
        $request->validate([
            'status' => 'required|in:waiting,in_consultation,completed,skipped,cancelled',
        ], $this->validationMessages());

        $updates = ['status' => $request->status];

        if ($request->status === 'in_consultation') {
            $updates['called_at'] = now();
        }
        if (in_array($request->status, ['completed', 'skipped', 'cancelled'])) {
            $updates['completed_at'] = now();
        }

        $token->update($updates);

        return response()->json(['success' => true]);
    }

    public function display()
    {
        $tokens = OpdToken::with(['patient', 'doctor'])
            ->whereDate('token_date', today())
            ->whereIn('status', ['waiting', 'in_consultation'])
            ->orderBy('queue_position')
            ->get();

        return view('opd.queue.display', compact('tokens'));
    }

    public function destroy(OpdToken $token)
    {
        $token->delete();
        return response()->json(['success' => true]);
    }
}
