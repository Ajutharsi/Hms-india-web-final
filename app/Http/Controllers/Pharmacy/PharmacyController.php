<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\StoreDispensingRequest;
use App\Models\Drug;
use App\Models\DrugDispensing;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\DispensingService;
use App\Support\DataTableFormat;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PharmacyController extends Controller
{
    public function __construct(private readonly DispensingService $dispensing)
    {
    }

    public function index()
    {
        return view('pharmacy.dispensing.index');
    }

    public function data()
    {
        $query = DrugDispensing::with(['patient:id,name,mrn', 'prescription:id,prescription_no'])
            ->withCount('items')
            ->latest();

        return DataTables::of($query)
            ->addColumn('patient_info', fn($d) => DataTableFormat::patientInfo($d->patient))
            ->addColumn('prescription_badge', fn($d) => $d->prescription
                ? '<span class="badge bg-label-primary">' . e($d->prescription->prescription_no) . '</span>'
                : '<span class="badge bg-label-secondary">Manual</span>')
            ->addColumn('items_badge', fn($d) => '<span class="badge bg-label-info">' . $d->items_count . ' items</span>')
            ->addColumn('total_amount_fmt', fn($d) => DataTableFormat::money($d->total_amount))
            ->addColumn('status_badge', fn($d) => DataTableFormat::badge($d->status, [
                'dispensed' => 'success', 'pending' => 'warning', 'partial' => 'info',
            ]))
            ->addColumn('actions', fn($d) => view('_partials.dt-actions', ['buttons' => [
                ['type' => 'link', 'url' => route('pharmacy.dispensing.show', $d), 'icon' => 'tabler-eye', 'class' => 'btn-label-info', 'title' => 'View'],
            ]])->render())
            // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
            ->filterColumn('patient_info', fn($q, $kw) => DataTableFormat::patientFilter($q, $kw))
            ->rawColumns(['patient_info', 'prescription_badge', 'items_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $patients     = Patient::active()->get(['id', 'name', 'mrn']);
        $drugs        = Drug::active()->where('current_stock', '>', 0)->get();
        $prescription = $request->prescription_id
            ? Prescription::with(['patient', 'doctor', 'items'])->find($request->prescription_id)
            : null;

        return view('pharmacy.dispensing.create', compact('patients', 'drugs', 'prescription'));
    }

    public function store(StoreDispensingRequest $request)
    {
        // Transactional: stock rows are locked, insufficient stock aborts everything.
        $dispensing = $this->dispensing->dispense($request->validated(), auth()->id());

        return redirect()->route('pharmacy.dispensing.show', $dispensing->id)
            ->with('success', 'Dispensing completed!');
    }

    public function show(DrugDispensing $dispensing)
    {
        $dispensing->load(['patient', 'prescription', 'items.drug', 'dispensedBy']);

        return view('pharmacy.dispensing.show', compact('dispensing'));
    }

    public function fromPrescription(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'items']);
        $drugs = Drug::active()->where('current_stock', '>', 0)->get();

        return view('pharmacy.dispensing.create', compact('prescription', 'drugs'));
    }

    public function alerts()
    {
        $lowStock = Drug::active()
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->get();

        $nearExpiry = Drug::active()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->whereDate('expiry_date', '>=', now())
            ->get();

        $expired = Drug::active()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now())
            ->get();

        return response()->json(compact('lowStock', 'nearExpiry', 'expired'));
    }
}
