<?php

namespace App\Http\Controllers\IPD;

use App\Http\Controllers\Controller;
use App\Models\IpdDeposit;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class IpdDepositController extends Controller
{
    public function index()
    {
        $totalCollected = IpdDeposit::where('type', 'advance')->sum('amount');
        $totalRefunded  = IpdDeposit::where('type', 'refund')->sum('amount');
        $netBalance     = $totalCollected - $totalRefunded;
        $todayCollection = IpdDeposit::where('type', 'advance')->whereDate('deposited_at', today())->sum('amount');

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn'])->sortBy(fn($p) => $p->name)->values();

        return view('ipd.deposits.index', compact(
            'totalCollected', 'totalRefunded', 'netBalance', 'todayCollection', 'patients'
        ));
    }

    public function data(Request $request)
    {
        $query = IpdDeposit::with(['patient:id,name,mrn', 'collectedBy:id,name'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->latest('deposited_at');

        return DataTables::of($query)
            ->addColumn('patient_info', fn($d) => e($d->patient->name ?? 'N/A') . '<br><small class="text-muted">' . e($d->patient->mrn ?? 'N/A') . '</small>')
            ->addColumn('balance', fn($d) => IpdDeposit::getBalanceForPatient($d->patient_id))
            ->addColumn('collected_by_name', fn($d) => $d->collectedBy->name ?? 'N/A')
            ->editColumn('deposited_at', fn($d) => $d->deposited_at?->format('d M Y h:i A'))
            ->addColumn('actions', fn($d) => $d->type === 'advance'
                ? '<div class="d-flex gap-2"><button onclick="openRefund(' . $d->id . ', ' . $d->patient_id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Refund"><i class="icon-base ti tabler-receipt-refund"></i></button><button onclick="deleteDeposit(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button></div>'
                : '<div class="d-flex gap-2"><button onclick="deleteDeposit(' . $d->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button></div>')
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->rawColumns(['patient_info', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque',
            'reference_no'   => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
        ], $this->validationMessages());

        IpdDeposit::create([
            'patient_id'     => $request->patient_id,
            'admission_id'   => Patient::find($request->patient_id)?->currentAdmission?->id,
            'collected_by'   => auth()->id(),
            'amount'         => $request->amount,
            'type'           => 'advance',
            'payment_method' => $request->payment_method,
            'reference_no'   => $request->reference_no,
            'notes'          => $request->notes,
            'deposited_at'   => now(),
        ]);

        return back()->with('success', 'Deposit recorded successfully!');
    }

    public function show(IpdDeposit $deposit)
    {
        $deposit->load(['patient', 'collectedBy']);
        return response()->json([
            'deposit' => $deposit,
            'balance' => IpdDeposit::getBalanceForPatient($deposit->patient_id),
        ]);
    }

    public function refund(Request $request, IpdDeposit $deposit)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque',
            'notes'          => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $balance = IpdDeposit::getBalanceForPatient($deposit->patient_id);
        if ($request->amount > $balance) {
            return response()->json(['error' => 'Refund amount exceeds available balance of ' . number_format($balance, 2)], 422);
        }

        IpdDeposit::create([
            'patient_id'     => $deposit->patient_id,
            'admission_id'   => $deposit->admission_id,
            'collected_by'   => auth()->id(),
            'amount'         => $request->amount,
            'type'           => 'refund',
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
            'deposited_at'   => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(IpdDeposit $deposit)
    {
        $deposit->delete();
        return response()->json(['success' => true]);
    }
}
