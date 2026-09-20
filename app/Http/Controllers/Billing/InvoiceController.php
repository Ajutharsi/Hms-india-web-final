<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\AddPaymentRequest;
use App\Http\Requests\Billing\StoreInvoiceRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\BillingService;
use App\Models\Invoice;
use App\Models\Patient;
use App\Services\InvoiceService;
use App\Support\DataTableFormat;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function index()
    {
        return view('billing.invoices.index');
    }

    public function data()
    {
        $query = Invoice::with('patient:id,name,mrn')->latest();

        return DataTables::of($query)
            ->addColumn('patient_info', fn($i) => DataTableFormat::patientInfo($i->patient))
            ->addColumn('total_fmt', fn($i) => DataTableFormat::money($i->total))
            ->addColumn('paid_amount_fmt', fn($i) => DataTableFormat::money($i->paid_amount))
            ->addColumn('balance_fmt', fn($i) => '<span class="' . ($i->balance > 0 ? 'text-danger fw-bold' : 'text-success') . '">'
                . DataTableFormat::money($i->balance) . '</span>')
            ->addColumn('status_badge', fn($i) => DataTableFormat::badge($i->status, [
                'draft' => 'secondary', 'sent' => 'info', 'paid' => 'success',
                'partial' => 'warning', 'overdue' => 'danger', 'cancelled' => 'secondary',
            ]))
            ->addColumn('actions', fn($i) => view('_partials.dt-actions', ['buttons' => [
                ['type' => 'link', 'url' => route('billing.invoices.show', $i), 'icon' => 'tabler-eye', 'class' => 'btn-label-info', 'title' => 'View'],
                ['type' => 'link', 'url' => route('billing.invoices.print', $i), 'target' => '_blank', 'icon' => 'tabler-printer', 'class' => 'btn-label-primary', 'title' => 'Print'],
            ]])->render())
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => DataTableFormat::patientFilter($q, $kw))
            ->rawColumns(['patient_info', 'balance_fmt', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $patients    = Patient::active()->get(['id', 'name', 'mrn']);
        $services    = BillingService::active()->get();
        $appointment = $request->appointment_id
            ? Appointment::with(['patient', 'doctor'])->find($request->appointment_id)
            : null;

        return view('billing.invoices.create', compact('patients', 'services', 'appointment'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $invoice = $this->invoices->create($request->validated(), auth()->id());

        return redirect()->route('billing.invoices.show', $invoice->id)
            ->with('success', 'Invoice created!');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'appointment.doctor', 'items', 'payments.receivedBy', 'createdBy']);

        return view('billing.invoices.show', compact('invoice'));
    }

    public function addPayment(AddPaymentRequest $request, Invoice $invoice)
    {
        $this->authorize('addPayment', $invoice);

        $this->invoices->addPayment($invoice, $request->validated(), auth()->id());

        return redirect()->route('billing.invoices.show', $invoice->id)
            ->with('success', 'Payment recorded!');
    }

    public function print(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['patient', 'appointment.doctor', 'items', 'payments', 'createdBy']);

        return view('billing.invoices.print', compact('invoice'));
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        ActivityLog::log('deleted', 'Invoice', 'Invoice ' . $invoice->invoice_no . ' deleted', $invoice);
        $invoice->delete(); // soft delete — financial records are retained

        return redirect()->route('billing.invoices.index')
            ->with('success', 'Invoice deleted!');
    }
}
