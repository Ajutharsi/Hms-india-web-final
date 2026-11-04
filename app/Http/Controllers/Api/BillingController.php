<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingService;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\IpdDeposit;
use App\Models\Patient;
use App\Models\SchemeBilling;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

/**
 * JSON endpoints for the Flutter receptionist app's Billing suite — mirrors
 * the web's Billing\InvoiceController, Insurance\InsuranceClaimController,
 * IPD\IpdDepositController, Billing\SchemeBillingController. All four are
 * receptionist-only on the web (`role:super_admin|admin|receptionist`).
 */
class BillingController extends Controller
{
    private function authReceptionist(Request $request): User
    {
        abort_unless($request->user()->hasRole('receptionist'), 403, 'This account is not a receptionist account.');

        return $request->user();
    }

    private function patientSearch($query, ?string $keyword)
    {
        if (!$keyword) {
            return $query;
        }
        $hash = Patient::hashFor($keyword);

        return $query->where(function ($q) use ($keyword, $hash) {
            $q->where('mrn', 'like', "{$keyword}%");
            if ($hash) $q->orWhere('name_hash', $hash);
        });
    }

    // ============================================================
    // Invoices (no delete — receptionist has no `delete billing` permission
    // on the web; delete is admin/super_admin + zero-paid only)
    // ============================================================

    public function invoicesStats(Request $request)
    {
        $this->authReceptionist($request);

        return response()->json([
            'total_invoices' => Invoice::count(),
            'paid' => (float) Invoice::sum('paid_amount'),
            'outstanding' => (float) Invoice::sum('balance'),
            'overdue' => Invoice::where('status', 'overdue')->count(),
        ]);
    }

    public function invoices(Request $request)
    {
        $this->authReceptionist($request);

        $query = Invoice::with('patient:id,name,mrn')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query = $this->patientSearch($query, $request->q);

        $invoices = $query->latest()->take(150)->get()->map(fn (Invoice $i) => $this->invoiceSummary($i));

        return response()->json(['invoices' => $invoices]);
    }

    private function invoiceSummary(Invoice $i): array
    {
        return [
            'id' => $i->id,
            'invoice_no' => $i->invoice_no,
            'patient_id' => $i->patient_id,
            'patient_name' => $i->patient?->name,
            'patient_mrn' => $i->patient?->mrn,
            'invoice_date' => $i->invoice_date?->format('Y-m-d'),
            'due_date' => $i->due_date?->format('Y-m-d'),
            'total' => (float) $i->total,
            'paid_amount' => (float) $i->paid_amount,
            'balance' => (float) $i->balance,
            'status' => $i->status,
        ];
    }

    public function invoiceServices(Request $request)
    {
        $this->authReceptionist($request);

        return response()->json(['services' => BillingService::where('status', 'active')->orderBy('service_name')->get(['id', 'service_code', 'service_name', 'category', 'price'])]);
    }

    public function invoiceStore(Request $request, InvoiceService $invoiceService)
    {
        $receptionist = $this->authReceptionist($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.billing_service_id' => ['nullable', 'exists:billing_services,id'],
            'items.*.item_name' => ['required_without:items.*.billing_service_id', 'nullable', 'string', 'max:255'],
            'items.*.unit_price' => ['required_without:items.*.billing_service_id', 'nullable', 'numeric', 'min:0'],
            'items.*.item_type' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice = $invoiceService->create($validated, $receptionist->id);

        return response()->json(['message' => 'Invoice created!', 'id' => $invoice->id, 'invoice_no' => $invoice->invoice_no], 201);
    }

    public function invoiceDetail(Request $request, Invoice $invoice)
    {
        $this->authReceptionist($request);
        $invoice->load(['patient', 'appointment.doctor', 'items', 'payments.receivedBy', 'createdBy']);

        return response()->json([
            ...$this->invoiceSummary($invoice),
            'subtotal' => (float) $invoice->subtotal,
            'discount' => (float) $invoice->discount,
            'tax' => (float) $invoice->tax,
            'notes' => $invoice->notes,
            'created_by_name' => $invoice->createdBy?->name,
            'items' => $invoice->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'item_type' => $item->item_type,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'total' => (float) $item->total,
            ]),
            'payments' => $invoice->payments->map(fn ($p) => [
                'payment_no' => $p->payment_no,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'reference_no' => $p->reference_no,
                'payment_date' => $p->payment_date,
                'received_by_name' => $p->receivedBy?->name,
            ]),
        ]);
    }

    public function invoiceAddPayment(Request $request, Invoice $invoice, InvoiceService $invoiceService)
    {
        $receptionist = $this->authReceptionist($request);
        abort_if($invoice->status === 'cancelled', 422, 'This invoice is cancelled.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            // `payments.payment_method` is an enum. Accepting any string up
            // to 50 characters let the value through to MySQL, which
            // rejected it and turned a user's typo into a 500. Every other
            // payment_method rule in the app already lists the column's
            // values; this one did not.
            'payment_method' => ['required', 'in:cash,card,bank_transfer,cheque,online,insurance,upi'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $invoiceService->addPayment($invoice, $validated, $receptionist->id);

        return response()->json(['message' => 'Payment recorded!', 'payment_no' => $payment->payment_no]);
    }

    // ============================================================
    // Insurance Claims (full CRUD)
    // ============================================================

    public function insuranceClaims(Request $request)
    {
        $this->authReceptionist($request);

        $query = InsuranceClaim::with('patient:id,name,mrn')
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id));

        $claims = $query->latest()->take(150)->get()->map(fn (InsuranceClaim $c) => $this->claimPayload($c));

        return response()->json([
            'stats' => [
                'total' => InsuranceClaim::count(),
                'pending' => InsuranceClaim::whereIn('status', ['submitted', 'pre_auth_pending', 'claim_submitted'])->count(),
                'approved' => InsuranceClaim::whereIn('status', ['approved', 'partially_approved', 'settled'])->count(),
                'rejected' => InsuranceClaim::whereIn('status', ['pre_auth_rejected', 'rejected'])->count(),
                'total_approved_amount' => (float) InsuranceClaim::sum('approved_amount'),
            ],
            'claims' => $claims,
        ]);
    }

    private function claimPayload(InsuranceClaim $c): array
    {
        return [
            'id' => $c->id,
            'claim_no' => $c->claim_no,
            'patient_id' => $c->patient_id,
            'patient_name' => $c->patient?->name,
            'patient_mrn' => $c->patient?->mrn,
            'invoice_id' => $c->invoice_id,
            'insurance_company' => $c->insurance_company,
            'tpa_name' => $c->tpa_name,
            'policy_number' => $c->policy_number,
            'member_id' => $c->member_id,
            'claim_amount' => (float) $c->claim_amount,
            'approved_amount' => $c->approved_amount !== null ? (float) $c->approved_amount : null,
            'patient_liability' => $c->patient_liability !== null ? (float) $c->patient_liability : null,
            'claim_type' => $c->claim_type,
            'status' => $c->status,
            'admission_date' => $c->admission_date,
            'discharge_date' => $c->discharge_date,
            'diagnosis' => $c->diagnosis,
            'treatment_details' => $c->treatment_details,
            'rejection_reason' => $c->rejection_reason,
            'icd10_code' => $c->icd10_code,
        ];
    }

    private array $claimRules = [
        'insurance_company' => ['required', 'string', 'max:255'],
        'tpa_name' => ['nullable', 'string', 'max:255'],
        'policy_number' => ['required', 'string', 'max:100'],
        'member_id' => ['nullable', 'string', 'max:100'],
        'claim_amount' => ['required', 'numeric', 'min:0'],
        'claim_type' => ['required', 'in:cashless,reimbursement'],
        'admission_date' => ['nullable', 'date'],
        'discharge_date' => ['nullable', 'date', 'after_or_equal:admission_date'],
        'diagnosis' => ['nullable', 'string', 'max:2000'],
        'treatment_details' => ['nullable', 'string', 'max:2000'],
    ];

    public function insuranceClaimStore(Request $request)
    {
        $this->authReceptionist($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
            ...$this->claimRules,
        ]);

        $claim = InsuranceClaim::create([...$validated, 'status' => 'draft']);

        return response()->json(['message' => 'Insurance claim created!', 'id' => $claim->id], 201);
    }

    public function insuranceClaimDetail(Request $request, InsuranceClaim $claim)
    {
        $this->authReceptionist($request);

        return response()->json($this->claimPayload($claim));
    }

    public function insuranceClaimUpdate(Request $request, InsuranceClaim $claim)
    {
        $this->authReceptionist($request);
        $validated = $request->validate($this->claimRules);
        $claim->update($validated);

        return response()->json(['message' => 'Insurance claim updated!', 'claim' => $this->claimPayload($claim->fresh())]);
    }

    public function insuranceClaimUpdateStatus(Request $request, InsuranceClaim $claim)
    {
        $this->authReceptionist($request);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,submitted,pre_auth_pending,pre_auth_approved,pre_auth_rejected,claim_submitted,approved,partially_approved,rejected,settled'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $claim->update([
            'status' => $validated['status'],
            'approved_amount' => $validated['approved_amount'] ?? $claim->approved_amount,
            'patient_liability' => isset($validated['approved_amount'])
                ? max(0, $claim->claim_amount - $validated['approved_amount'])
                : $claim->patient_liability,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return response()->json(['message' => 'Claim status updated.']);
    }

    public function insuranceClaimDestroy(Request $request, InsuranceClaim $claim)
    {
        $this->authReceptionist($request);
        $claim->delete();

        return response()->json(['message' => 'Insurance claim deleted.']);
    }

    // ============================================================
    // IPD Deposits
    // ============================================================

    public function ipdDeposits(Request $request)
    {
        $this->authReceptionist($request);

        $query = IpdDeposit::with(['patient:id,name,mrn', 'collectedBy:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->latest('deposited_at');

        $deposits = $query->take(150)->get()->map(fn (IpdDeposit $d) => [
            'id' => $d->id,
            'deposit_no' => $d->deposit_no,
            'patient_id' => $d->patient_id,
            'patient_name' => $d->patient?->name,
            'patient_mrn' => $d->patient?->mrn,
            'amount' => (float) $d->amount,
            'type' => $d->type,
            'payment_method' => $d->payment_method,
            'reference_no' => $d->reference_no,
            'notes' => $d->notes,
            'collected_by_name' => $d->collectedBy?->name,
            'deposited_at' => $d->deposited_at,
            'balance' => IpdDeposit::getBalanceForPatient($d->patient_id),
        ]);

        return response()->json([
            'stats' => [
                'total_collected' => (float) IpdDeposit::where('type', 'advance')->sum('amount'),
                'total_refunded' => (float) IpdDeposit::where('type', 'refund')->sum('amount'),
                'today_collection' => (float) IpdDeposit::where('type', 'advance')->whereDate('deposited_at', today())->sum('amount'),
            ],
            'deposits' => $deposits,
        ]);
    }

    public function ipdDepositStore(Request $request)
    {
        $receptionist = $this->authReceptionist($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,card,upi,bank_transfer,cheque'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $deposit = IpdDeposit::create([
            'patient_id' => $validated['patient_id'],
            'admission_id' => Patient::find($validated['patient_id'])?->currentAdmission?->id,
            'collected_by' => $receptionist->id,
            'amount' => $validated['amount'],
            'type' => 'advance',
            'payment_method' => $validated['payment_method'],
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'deposited_at' => now(),
        ]);

        return response()->json(['message' => 'Deposit recorded successfully!', 'id' => $deposit->id], 201);
    }

    public function ipdDepositShow(Request $request, IpdDeposit $deposit)
    {
        $this->authReceptionist($request);
        $deposit->load(['patient', 'collectedBy']);

        return response()->json([
            'id' => $deposit->id,
            'patient_name' => $deposit->patient?->name,
            'amount' => (float) $deposit->amount,
            'type' => $deposit->type,
            'balance' => IpdDeposit::getBalanceForPatient($deposit->patient_id),
        ]);
    }

    public function ipdDepositRefund(Request $request, IpdDeposit $deposit)
    {
        $receptionist = $this->authReceptionist($request);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,card,upi,bank_transfer,cheque'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $balance = IpdDeposit::getBalanceForPatient($deposit->patient_id);
        if ($validated['amount'] > $balance) {
            return response()->json(['message' => 'Refund amount exceeds available balance of ' . number_format($balance, 2)], 422);
        }

        IpdDeposit::create([
            'patient_id' => $deposit->patient_id,
            'admission_id' => $deposit->admission_id,
            'collected_by' => $receptionist->id,
            'amount' => $validated['amount'],
            'type' => 'refund',
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'deposited_at' => now(),
        ]);

        return response()->json(['message' => 'Refund recorded.']);
    }

    // ============================================================
    // Scheme Billing
    // ============================================================

    public function schemeBillings(Request $request)
    {
        $this->authReceptionist($request);

        $bills = SchemeBilling::with('patient:id,name,mrn')->latest()->take(150)->get()->map(fn (SchemeBilling $b) => $this->schemePayload($b));

        return response()->json([
            'stats' => [
                'total' => SchemeBilling::count(),
                'pending' => SchemeBilling::where('status', 'submitted')->count(),
                'approved_amount' => (float) SchemeBilling::whereIn('status', ['approved', 'settled'])->sum('scheme_payment'),
                'settled' => SchemeBilling::where('status', 'settled')->count(),
            ],
            'bills' => $bills,
        ]);
    }

    private function schemePayload(SchemeBilling $b): array
    {
        return [
            'id' => $b->id,
            'scheme_bill_no' => $b->scheme_bill_no,
            'patient_id' => $b->patient_id,
            'patient_name' => $b->patient?->name,
            'patient_mrn' => $b->patient?->mrn,
            'invoice_id' => $b->invoice_id,
            'scheme' => $b->scheme,
            'scheme_name' => $b->scheme_name,
            'beneficiary_id' => $b->beneficiary_id,
            'package_code' => $b->package_code,
            'package_name' => $b->package_name,
            'package_rate' => (float) $b->package_rate,
            'actual_bill' => (float) $b->actual_bill,
            'scheme_payment' => $b->scheme_payment !== null ? (float) $b->scheme_payment : null,
            'patient_liability' => $b->patient_liability !== null ? (float) $b->patient_liability : null,
            'status' => $b->status,
            'rejection_reason' => $b->rejection_reason,
            'submission_date' => $b->submission_date,
            'settlement_date' => $b->settlement_date,
            'icd10_code' => $b->icd10_code,
        ];
    }

    private array $schemeRules = [
        'scheme' => ['required', 'in:pmjay,ayushman,cghs,esi,state_scheme,other'],
        'scheme_name' => ['nullable', 'string', 'max:255'],
        'beneficiary_id' => ['nullable', 'string', 'max:100'],
        'package_code' => ['nullable', 'string', 'max:100'],
        'package_name' => ['nullable', 'string', 'max:255'],
        'package_rate' => ['required', 'numeric', 'min:0'],
        'actual_bill' => ['nullable', 'numeric', 'min:0'],
    ];

    public function schemeBillingStore(Request $request)
    {
        $this->authReceptionist($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
            ...$this->schemeRules,
        ]);

        $bill = SchemeBilling::create([...$validated, 'actual_bill' => $validated['actual_bill'] ?? 0, 'status' => 'draft']);

        return response()->json(['message' => 'Scheme bill created!', 'id' => $bill->id], 201);
    }

    public function schemeBillingDetail(Request $request, SchemeBilling $scheme)
    {
        $this->authReceptionist($request);

        return response()->json($this->schemePayload($scheme));
    }

    public function schemeBillingUpdate(Request $request, SchemeBilling $scheme)
    {
        $this->authReceptionist($request);
        $validated = $request->validate($this->schemeRules);
        $scheme->update($validated);

        return response()->json(['message' => 'Scheme bill updated!', 'scheme' => $this->schemePayload($scheme->fresh())]);
    }

    public function schemeBillingUpdateStatus(Request $request, SchemeBilling $scheme)
    {
        $this->authReceptionist($request);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,submitted,approved,rejected,settled'],
            'scheme_payment' => ['nullable', 'numeric', 'min:0'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $updates = ['status' => $validated['status'], 'rejection_reason' => $validated['rejection_reason'] ?? null];

        if (array_key_exists('scheme_payment', $validated) && $validated['scheme_payment'] !== null) {
            $updates['scheme_payment'] = $validated['scheme_payment'];
            $updates['patient_liability'] = max(0, $scheme->actual_bill - $validated['scheme_payment']);
        }
        if ($validated['status'] === 'submitted' && !$scheme->submission_date) {
            $updates['submission_date'] = now()->toDateString();
        }
        if ($validated['status'] === 'settled') {
            $updates['settlement_date'] = now()->toDateString();
        }

        $scheme->update($updates);

        return response()->json(['message' => 'Scheme bill status updated.']);
    }

    public function schemeBillingDestroy(Request $request, SchemeBilling $scheme)
    {
        $this->authReceptionist($request);
        $scheme->delete();

        return response()->json(['message' => 'Scheme bill deleted.']);
    }
}
