<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\SchemeBilling;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SchemeBillingController extends Controller
{
    public function index()
    {
        $totalClaims    = SchemeBilling::count();
        $pendingClaims  = SchemeBilling::whereIn('status', ['submitted'])->count();
        $approvedAmount = SchemeBilling::whereIn('status', ['approved', 'settled'])->sum('scheme_payment');
        $settledClaims  = SchemeBilling::where('status', 'settled')->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();
        $invoices = Invoice::orderBy('invoice_no')->get(['id', 'invoice_no', 'patient_id']);

        return view('billing.schemes.index', compact(
            'totalClaims', 'pendingClaims', 'approvedAmount', 'settledClaims', 'patients', 'invoices'
        ));
    }

    public function data()
    {
        $query = SchemeBilling::with('patient:id,name,mrn')->latest();

        return DataTables::of($query)
            ->addColumn('patient_info', fn($b) => e($b->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($b->patient->mrn ?? 'N/A') . '</small>')
            ->editColumn('created_at', fn($b) => $b->created_at->format('d M Y'))
            ->addColumn('actions', fn($b) => '
                <div class="d-flex gap-2">
                    <button onclick="openStatus(' . $b->id . ')" class="btn btn-sm btn-icon btn-label-info" title="Update Status"><i class="icon-base ti tabler-edit"></i></button>
                    <button onclick="deleteScheme(' . $b->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>
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
            'patient_id'      => 'required|exists:patients,id',
            'invoice_id'      => 'nullable|exists:invoices,id',
            'scheme'          => 'required|in:pmjay,ayushman,cghs,esi,state_scheme,other',
            'scheme_name'     => 'nullable|string|max:255',
            'beneficiary_id'  => 'nullable|string|max:100',
            'package_code'    => 'nullable|string|max:100',
            'package_name'    => 'nullable|string|max:255',
            'package_rate'    => 'required|numeric|min:0',
            'actual_bill'     => 'nullable|numeric|min:0',
            'icd10_code'      => 'nullable|string|max:20',
        ], $this->validationMessages());

        SchemeBilling::create([
            'patient_id'     => $request->patient_id,
            'invoice_id'     => $request->invoice_id,
            'scheme'         => $request->scheme,
            'scheme_name'    => $request->scheme_name,
            'beneficiary_id' => $request->beneficiary_id,
            'package_code'   => $request->package_code,
            'package_name'   => $request->package_name,
            'package_rate'   => $request->package_rate,
            'actual_bill'    => $request->actual_bill ?? 0,
            'status'         => 'draft',
            'icd10_code'     => $request->icd10_code,
        ]);

        return back()->with('success', 'Scheme bill created!');
    }

    public function show(SchemeBilling $scheme)
    {
        $scheme->load(['patient', 'invoice']);
        return response()->json($scheme);
    }

    public function update(Request $request, SchemeBilling $scheme)
    {
        $request->validate([
            'scheme'          => 'required|in:pmjay,ayushman,cghs,esi,state_scheme,other',
            'scheme_name'     => 'nullable|string|max:255',
            'beneficiary_id'  => 'nullable|string|max:100',
            'package_code'    => 'nullable|string|max:100',
            'package_name'    => 'nullable|string|max:255',
            'package_rate'    => 'required|numeric|min:0',
            'actual_bill'     => 'nullable|numeric|min:0',
        ], $this->validationMessages());

        $scheme->update($request->only([
            'scheme', 'scheme_name', 'beneficiary_id', 'package_code',
            'package_name', 'package_rate', 'actual_bill',
        ]));

        return back()->with('success', 'Scheme bill updated!');
    }

    public function updateStatus(Request $request, SchemeBilling $scheme)
    {
        $request->validate([
            'status'            => 'required|in:draft,submitted,approved,rejected,settled',
            'scheme_payment'    => 'nullable|numeric|min:0',
            'rejection_reason'  => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $updates = [
            'status'           => $request->status,
            'rejection_reason' => $request->rejection_reason,
        ];

        if ($request->scheme_payment !== null) {
            $updates['scheme_payment']    = $request->scheme_payment;
            $updates['patient_liability'] = max(0, $scheme->actual_bill - $request->scheme_payment);
        }

        if ($request->status === 'submitted' && !$scheme->submission_date) {
            $updates['submission_date'] = now()->toDateString();
        }
        if ($request->status === 'settled') {
            $updates['settlement_date'] = now()->toDateString();
        }

        $scheme->update($updates);

        return response()->json(['success' => true]);
    }

    public function destroy(SchemeBilling $scheme)
    {
        $scheme->delete();
        return response()->json(['success' => true]);
    }
}
