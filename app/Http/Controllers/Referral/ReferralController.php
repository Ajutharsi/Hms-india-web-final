<?php

namespace App\Http\Controllers\Referral;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Traits\AuthorizesAccess;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReferralController extends Controller
{
    use AuthorizesAccess;

    private function authorizeReferralAccess(Referral $referral): void
    {
        if ($this->isDoctorRole()) {
            $doctor = $this->getAuthDoctor();
            $this->abortIfNotOwner(
                (bool) ($doctor && ($referral->referred_by === $doctor->id || $referral->referred_to === $doctor->id)),
                'You are not authorized to access this referral.'
            );
        }
    }

    public function index()
    {
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn']);
        $doctors  = Doctor::where('status', 'active')->get();

        $statsQuery = Referral::query();

        // Doctor role — stats scoped to referrals they made or received
        if (auth()->user()->hasRole('doctor')) {
            $doctorRecord = Doctor::where('email', auth()->user()->email)->first();
            if ($doctorRecord) {
                $statsQuery->where(function ($q) use ($doctorRecord) {
                    $q->where('referred_by', $doctorRecord->id)
                      ->orWhere('referred_to', $doctorRecord->id);
                });
            } else {
                $statsQuery->whereRaw('1 = 0');
            }
        }

        $totalReferrals     = (clone $statsQuery)->count();
        $pendingReferrals   = (clone $statsQuery)->where('status', 'pending')->count();
        $acceptedReferrals  = (clone $statsQuery)->where('status', 'accepted')->count();
        $completedReferrals = (clone $statsQuery)->where('status', 'completed')->count();

        return view('referrals.index', compact(
            'patients', 'doctors',
            'totalReferrals', 'pendingReferrals', 'acceptedReferrals', 'completedReferrals'
        ));
    }

    public function data()
    {
        $query = Referral::with(['patient:id,name,mrn', 'referredBy:id,name', 'referredTo:id,name'])
            ->orderBy('created_at', 'desc');


        if (auth()->user()->hasRole('doctor')) {
            $doctorRecord = \App\Models\Doctor::where('email', auth()->user()->email)->first();
            if ($doctorRecord) {
                $query->where(function ($q) use ($doctorRecord) {
                    $q->where('referred_by', $doctorRecord->id)
                      ->orWhere('referred_to', $doctorRecord->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return DataTables::of($query)
            ->addColumn('patient_info', fn($r) => '<div><strong>' . e($r->patient->name ?? 'N/A') . '</strong><br><small class="text-muted">' . e($r->patient->mrn ?? 'N/A') . '</small></div>')
            ->editColumn('referred_by', fn($r) => 'Dr. ' . ($r->referredBy->name ?? 'N/A'))
            ->editColumn('referred_to', fn($r) => 'Dr. ' . ($r->referredTo->name ?? 'N/A'))
            ->addColumn('actions', fn($r) => '
                <div class="d-flex gap-1">
                    <select onchange="updateStatus(' . $r->id . ', this.value)" class="form-select form-select-sm" style="width:110px;">
                        <option value="pending" ' . ($r->status === 'pending' ? 'selected' : '') . '>Pending</option>
                        <option value="accepted" ' . ($r->status === 'accepted' ? 'selected' : '') . '>Accepted</option>
                        <option value="completed" ' . ($r->status === 'completed' ? 'selected' : '') . '>Completed</option>
                        <option value="cancelled" ' . ($r->status === 'cancelled' ? 'selected' : '') . '>Cancelled</option>
                    </select>
                    <button onclick="deleteReferral(' . $r->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
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
            'patient_id'  => 'required|exists:patients,id',
            'referred_by' => 'required|exists:doctors,id',
            'referred_to' => 'required|exists:doctors,id|different:referred_by',
            'reason'      => 'required|string|max:1000',
            'notes'       => 'nullable|string|max:1000',
            'priority'    => 'required|in:routine,urgent,emergency',
            'referral_date' => 'required|date',
        ], $this->validationMessages());

        // Auto generate referral no
        $last     = Referral::withTrashed()->latest('id')->first();
        $number   = $last ? (intval(substr($last->referral_no, 4)) + 1) : 1;
        $refNo    = 'REF-' . str_pad($number, 4, '0', STR_PAD_LEFT);

        Referral::create([
            'referral_no'   => $refNo,
            'patient_id'    => $request->patient_id,
            'referred_by'   => $request->referred_by,
            'referred_to'   => $request->referred_to,
            'reason'        => $request->reason,
            'notes'         => $request->notes,
            'priority'      => $request->priority,
            'status'        => 'pending',
            'referral_date' => $request->referral_date,
        ]);

        return redirect()->route('referrals.index')
            ->with('success', 'Referral ' . $refNo . ' created successfully!');
    }

    public function updateStatus(Request $request, Referral $referral)
    {
        $this->authorizeReferralAccess($referral);

        $request->validate([
            'status' => 'required|in:pending,accepted,completed,cancelled',
        ], $this->validationMessages());

        $referral->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function destroy(Referral $referral)
    {
        $this->authorizeReferralAccess($referral);

        $referral->delete();
        return response()->json(['success' => true]);
    }
}