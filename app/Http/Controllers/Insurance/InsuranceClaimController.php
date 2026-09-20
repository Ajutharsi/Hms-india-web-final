<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InsuranceClaimController extends Controller
{
    public function index()
    {
        $totalClaims    = InsuranceClaim::count();
        $pendingClaims  = InsuranceClaim::whereIn('status', ['submitted', 'pre_auth_pending', 'claim_submitted'])->count();
        $approvedClaims = InsuranceClaim::whereIn('status', ['approved', 'partially_approved', 'settled'])->count();
        $rejectedClaims = InsuranceClaim::whereIn('status', ['pre_auth_rejected', 'rejected'])->count();
        $totalApproved  = InsuranceClaim::sum('approved_amount');

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();
        $invoices = Invoice::orderBy('invoice_no')->get(['id', 'invoice_no', 'patient_id', 'total']);

        return view('insurance.index', compact(
            'totalClaims', 'pendingClaims', 'approvedClaims', 'rejectedClaims', 'totalApproved',
            'patients', 'invoices'
        ));
    }

    public function data(Request $request)
    {
        $query = InsuranceClaim::with('patient:id,name,mrn')
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->latest();

        return DataTables::of($query)
            ->addColumn('patient_info', fn($c) => e($c->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($c->patient->mrn ?? 'N/A') . '</small>')
            ->editColumn('created_at', fn($c) => $c->created_at->format('d M Y'))
            ->addColumn('actions', fn($c) => '
                <div class="d-flex gap-2">
                    <button onclick="openStatus(' . $c->id . ')" class="btn btn-sm btn-icon btn-label-info" title="Update Status"><i class="icon-base ti tabler-edit"></i></button>
                    <button onclick="deleteClaim(' . $c->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
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
            'patient_id'         => 'required|exists:patients,id',
            'invoice_id'         => 'nullable|exists:invoices,id',
            'insurance_company'  => 'required|string|max:255',
            'tpa_name'           => 'nullable|string|max:255',
            'policy_number'      => 'required|string|max:100',
            'member_id'          => 'nullable|string|max:100',
            'claim_amount'       => 'required|numeric|min:0',
            'claim_type'         => 'required|in:cashless,reimbursement',
            'admission_date'     => 'nullable|date',
            'discharge_date'     => 'nullable|date|after_or_equal:admission_date',
            'diagnosis'          => 'nullable|string|max:2000',
            'treatment_details'  => 'nullable|string|max:2000',
            'icd10_code'         => 'nullable|string|max:20',
        ], $this->validationMessages());

        InsuranceClaim::create([
            'patient_id'        => $request->patient_id,
            'invoice_id'        => $request->invoice_id,
            'insurance_company' => $request->insurance_company,
            'tpa_name'          => $request->tpa_name,
            'policy_number'     => $request->policy_number,
            'member_id'         => $request->member_id,
            'claim_amount'      => $request->claim_amount,
            'claim_type'        => $request->claim_type,
            'status'            => 'draft',
            'admission_date'    => $request->admission_date,
            'discharge_date'    => $request->discharge_date,
            'diagnosis'         => $request->diagnosis,
            'treatment_details' => $request->treatment_details,
            'icd10_code'        => $request->icd10_code,
        ]);

        return back()->with('success', 'Insurance claim created!');
    }

    public function show(InsuranceClaim $claim)
    {
        $claim->load(['patient', 'invoice']);
        return response()->json($claim);
    }

    public function update(Request $request, InsuranceClaim $claim)
    {
        $request->validate([
            'insurance_company'  => 'required|string|max:255',
            'tpa_name'           => 'nullable|string|max:255',
            'policy_number'      => 'required|string|max:100',
            'member_id'          => 'nullable|string|max:100',
            'claim_amount'       => 'required|numeric|min:0',
            'claim_type'         => 'required|in:cashless,reimbursement',
            'admission_date'     => 'nullable|date',
            'discharge_date'     => 'nullable|date|after_or_equal:admission_date',
            'diagnosis'          => 'nullable|string|max:2000',
            'treatment_details'  => 'nullable|string|max:2000',
        ], $this->validationMessages());

        $claim->update($request->only([
            'insurance_company', 'tpa_name', 'policy_number', 'member_id',
            'claim_amount', 'claim_type', 'admission_date', 'discharge_date',
            'diagnosis', 'treatment_details',
        ]));

        return back()->with('success', 'Insurance claim updated!');
    }

    public function updateStatus(Request $request, InsuranceClaim $claim)
    {
        $request->validate([
            'status'           => 'required|in:draft,submitted,pre_auth_pending,pre_auth_approved,pre_auth_rejected,claim_submitted,approved,partially_approved,rejected,settled',
            'approved_amount'  => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $claim->update([
            'status'            => $request->status,
            'approved_amount'   => $request->approved_amount ?? $claim->approved_amount,
            'patient_liability' => $request->approved_amount
                ? max(0, $claim->claim_amount - $request->approved_amount)
                : $claim->patient_liability,
            'rejection_reason'  => $request->rejection_reason,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(InsuranceClaim $claim)
    {
        $claim->delete();
        return response()->json(['success' => true]);
    }
}
