<?php

namespace App\Http\Controllers\BloodBank;

use App\Http\Controllers\Controller;
use App\Models\BloodTransfusion;
use App\Models\BloodUnit;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BloodBankController extends Controller
{
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function index()
    {
        $totalUnits = BloodUnit::count();

        $byGroup = collect(self::BLOOD_GROUPS)->mapWithKeys(fn($g) => [
            $g => BloodUnit::where('blood_group', $g)->where('status', 'available')->count(),
        ]);

        $nearExpiryCount = BloodUnit::where('status', 'available')
            ->whereDate('expiry_date', '<=', now()->addDays(7))
            ->whereDate('expiry_date', '>=', now())
            ->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();

        return view('blood-bank.index', compact('totalUnits', 'byGroup', 'nearExpiryCount', 'patients'));
    }

    public function data()
    {
        $query = BloodUnit::with('reservedForPatient:id,name')->latest();

        return DataTables::of($query)
            ->addColumn('blood_group_badge', fn($u) => '<span class="badge bg-label-primary">' . $u->blood_group . '</span>')
            ->addColumn('component_display', fn($u) => str_replace('_', ' ', $u->component))
            ->editColumn('collection_date', fn($u) => $u->collection_date?->format('d M Y'))
            ->addColumn('expiry_display', fn($u) => $u->isNearExpiry()
                ? '<span class="text-danger fw-bold">' . $u->expiry_date?->format('d M Y') . '</span>'
                : $u->expiry_date?->format('d M Y'))
            ->addColumn('status_badge', function ($u) {
                $map = ['available' => 'success', 'reserved' => 'warning', 'transfused' => 'info', 'expired' => 'danger', 'discarded' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$u->status] ?? 'secondary') . '">' . $u->status . '</span>';
            })
            ->addColumn('actions', fn($u) => '
                <div class="d-flex gap-2">
                    ' . ($u->status === 'available' ? '<button onclick="openTransfuse(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-info" title="Transfuse"><i class="icon-base ti tabler-droplet"></i></button>' : '') . '
                    <button onclick="deleteUnit(' . $u->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
                </div>
            ')
            ->rawColumns(['blood_group_badge', 'expiry_display', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'blood_group'     => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'component'       => 'required|in:whole_blood,packed_rbc,ffp,platelets,cryoprecipitate',
            'donor_name'      => 'nullable|string|max:255',
            'collection_date' => 'required|date',
            'expiry_date'     => 'required|date|after:collection_date',
            'notes'           => 'nullable|string|max:1000',
        ], $this->validationMessages());

        BloodUnit::create([
            'blood_group'     => $request->blood_group,
            'component'       => $request->component,
            'donor_name'      => $request->donor_name,
            'collection_date' => $request->collection_date,
            'expiry_date'     => $request->expiry_date,
            'status'          => 'available',
            'notes'           => $request->notes,
        ]);

        return back()->with('success', 'Blood unit added to inventory!');
    }

    // Separate from data() (which lists BloodUnit inventory): this lists a
    // single patient's BloodTransfusion history, used by the patient show page tab.
    public function transfusionsData(Request $request)
    {
        $query = BloodTransfusion::with(['bloodUnit:id,unit_no,blood_group', 'administeredBy:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->latest('started_at');

        return DataTables::of($query)
            ->addColumn('blood_group', fn($t) => $t->bloodUnit->blood_group ?? 'N/A')
            ->addColumn('administered_by_name', fn($t) => $t->administeredBy->name ?? 'N/A')
            ->editColumn('started_at', fn($t) => $t->started_at?->format('d M Y h:i A'))
            ->addColumn('status_badge', function ($t) {
                $map = ['pending' => 'secondary', 'in_progress' => 'info', 'completed' => 'success', 'adverse_reaction' => 'danger'];
                return '<span class="badge bg-label-' . ($map[$t->status] ?? 'secondary') . '">' . str_replace('_', ' ', $t->status) . '</span>';
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function transfuse(Request $request, BloodUnit $unit)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'indication' => 'nullable|string|max:255',
        ], $this->validationMessages());

        if ($unit->status !== 'available' && $unit->status !== 'reserved') {
            return response()->json(['error' => 'This unit is not available for transfusion.'], 422);
        }

        BloodTransfusion::create([
            'patient_id'      => $request->patient_id,
            'blood_unit_id'   => $unit->id,
            'administered_by' => auth()->id(),
            'indication'      => $request->indication,
            'status'          => 'in_progress',
            'started_at'      => now(),
        ]);

        $unit->update(['status' => 'transfused', 'reserved_for' => $request->patient_id]);

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, BloodUnit $unit)
    {
        $request->validate([
            'status'       => 'required|in:available,reserved,transfused,expired,discarded',
            'reserved_for' => 'nullable|exists:patients,id',
        ], $this->validationMessages());

        $unit->update([
            'status'       => $request->status,
            'reserved_for' => $request->status === 'reserved' ? $request->reserved_for : null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(BloodUnit $unit)
    {
        $unit->delete();
        return response()->json(['success' => true]);
    }
}
