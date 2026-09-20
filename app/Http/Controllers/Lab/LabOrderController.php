<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\PatientMedicalRecord;
use App\Support\DataTableFormat;
use App\Traits\AuthorizesAccess;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LabOrderController extends Controller
{
    use AuthorizesAccess;

    private function authorizeLabOrderAccess(LabOrder $labOrder): void
    {
        if ($this->isPatientRole()) {
            $patient = $this->getAuthPatient();
            $this->abortIfNotOwner(
                (bool) ($patient && $labOrder->patient_id === $patient->id),
                'You are not authorized to view this lab order.'
            );
        }

        if ($this->isDoctorRole()) {
            $doctor = $this->getAuthDoctor();
            $this->abortIfNotOwner(
                (bool) ($doctor && $labOrder->doctor_id === $doctor->id),
                'You are not authorized to view this lab order.'
            );
        }
    }

    public function index()
    {
        $statsQuery = LabOrder::query();

        // Doctor role — stats scoped to this doctor's own lab orders
        if (auth()->user()->hasRole('doctor')) {
            $doctorRecord = Doctor::where('email', auth()->user()->email)->first();
            if ($doctorRecord) {
                $statsQuery->where('doctor_id', $doctorRecord->id);
            } else {
                $statsQuery->whereRaw('1 = 0');
            }
        }

        $totalLabOrders      = (clone $statsQuery)->count();
        $pendingLabOrders    = (clone $statsQuery)->where('status', 'pending')->count();
        $processingLabOrders = (clone $statsQuery)->where('status', 'processing')->count();
        $completedLabOrders  = (clone $statsQuery)->where('status', 'completed')->count();

        return view('lab.orders.index', compact(
            'totalLabOrders', 'pendingLabOrders', 'processingLabOrders', 'completedLabOrders'
        ));
    }

   public function data()
{
    $query = LabOrder::with(['patient:id,name,mrn', 'doctor:id,name,photo'])
        ->withCount('items')
        ->latest();

    // Patient role — only their orders
    if (auth()->user()->hasRole('patient')) {
        $patientRecord = \App\Models\Patient::where('user_id', auth()->id())->first();
        if ($patientRecord) {
            $query->where('patient_id', $patientRecord->id);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    // Doctor role — only their orders
    elseif (auth()->user()->hasRole('doctor')) {
        $doctorRecord = \App\Models\Doctor::where('email', auth()->user()->email)->first();
        if ($doctorRecord) {
            $query->where('doctor_id', $doctorRecord->id);
        }
    }

    return DataTables::of($query)
        ->addColumn('patient_info', fn($o) => '<div><strong>' . e($o->patient->name ?? 'N/A') . '</strong><br><small class="text-muted">' . e($o->patient->mrn ?? 'N/A') . '</small></div>')
        ->addColumn('doctor_name', fn($o) => DataTableFormat::doctorInfo($o->doctor, prefixDr: true))
        ->addColumn('tests_badge', fn($o) => '<span class="badge bg-label-info">' . $o->items_count . ' Tests</span>')
        ->addColumn('status_badge', function ($o) {
            $colors = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
            return '<span class="badge bg-label-' . ($colors[$o->status] ?? 'secondary') . '">' . $o->status . '</span>';
        })
        ->addColumn('actions', fn($o) => '
            <div class="d-flex gap-2">
                <a href="/lab/orders/' . $o->id . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                    <i class="icon-base ti tabler-eye"></i>
                </a>
                <a href="/lab/orders/' . $o->id . '/print" target="_blank" class="btn btn-sm btn-icon btn-label-primary" title="Print">
                    <i class="icon-base ti tabler-printer"></i>
                </a>
            </div>
        ')
        // name is encrypted at rest; blind-index only supports exact match, so search MRN (partial) + name_hash (exact).
        ->filterColumn('patient_info', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
        ->filterColumn('doctor_name', fn($q, $kw) => $q->whereHas('doctor', fn($d) => $d->where('name', 'like', "%{$kw}%")))
        ->rawColumns(['patient_info', 'doctor_name', 'tests_badge', 'status_badge', 'actions'])
        ->make(true);
}

    public function create()
    {
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn']);
        $doctors  = Doctor::where('status', 'active')->get();
        $tests    = LabTest::where('status', 'active')->get();
        return view('lab.orders.create', compact('patients', 'doctors', 'tests'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'doctor_id'      => 'required|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'tests'          => 'required|array|min:1',
            'tests.*'        => 'exists:lab_tests,id',
            'notes'          => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $order = LabOrder::create([
            'patient_id'     => $request->patient_id,
            'doctor_id'      => $request->doctor_id,
            'appointment_id' => $request->appointment_id ?? null,
            'order_date'     => now()->toDateString(),
            'status'         => 'pending',
            'notes'          => $request->notes,
        ]);

        foreach ($request->tests as $testId) {
            LabOrderItem::create([
                'lab_order_id' => $order->id,
                'lab_test_id'  => $testId,
                'status'       => 'pending',
            ]);
        }

       
        PatientMedicalRecord::create([
            'patient_id'  => $request->patient_id,
            'record_type' => 'Lab Result',
            'record_date' => now()->toDateString(),
            'description' => 'Lab Order ' . $order->order_no . ' created',
            'doctor_name' => Doctor::find($request->doctor_id)->name ?? null,
            'notes'       => $request->notes ?? null,
            'reference_id'   => $order->id,               
            'reference_type' => 'lab_order',
        ]);

        return redirect()->route('lab.orders.show', $order->id)
            ->with('success', 'Lab order created!');
    }

    public function show(LabOrder $labOrder)
    {
        $this->authorizeLabOrderAccess($labOrder);

        $labOrder->load(['patient', 'doctor', 'items.labTest', 'items.result']);
        return view('lab.orders.show', compact('labOrder'));
    }

    public function enterResults(Request $request, LabOrder $labOrder)
    {
        $request->validate([
            'results'                    => 'required|array',
            'results.*.result_value'     => 'required|string|max:255',
            'results.*.unit'             => 'nullable|string|max:255',
            'results.*.normal_range'     => 'nullable|string|max:255',
            'results.*.remarks'          => 'nullable|string|max:255',
        ], $this->validationMessages());

        // Batch-load every submitted item + its labTest once instead of one
        // find() + one lazy labTest query per row (was N+1 across the loop).
        $items = LabOrderItem::with('labTest')
            ->whereIn('id', array_keys($request->results))
            ->get()
            ->keyBy('id');

        foreach ($request->results as $itemId => $data) {
            $item = $items->get($itemId);
            if (!$item) continue;

            // Flag calculate
            $flag = 'normal';
            $test = $item->labTest;
            if ($test && $test->normal_range_male) {
                $flag = $this->calculateFlag(
                    $data['result_value'],
                    $test->normal_range_male
                );
            }

            LabResult::updateOrCreate(
                ['lab_order_item_id' => $itemId],
                [
                    'lab_order_id'     => $labOrder->id,
                    'patient_id'       => $labOrder->patient_id,
                    'lab_test_id'      => $item->lab_test_id,
                    'result_value'     => $data['result_value'],
                    'unit'             => $data['unit'] ?? $test->unit ?? null,
                    'normal_range'     => $data['normal_range'] ?? null,
                    'flag'             => $flag,
                    'remarks'          => $data['remarks'] ?? null,
                    'performed_by'     => auth()->id(),
                    'result_date'      => now(),
                ]
            );

            $item->update(['status' => 'completed']);
        }

        
        $allCompleted = $labOrder->items()->where('status', 'pending')->count() === 0;
        if ($allCompleted) {
            $labOrder->update(['status' => 'completed']);
        }

        return redirect()->route('lab.orders.show', $labOrder->id)
            ->with('success', 'Results saved!');
    }

    public function print(LabOrder $labOrder)
    {
        $this->authorizeLabOrderAccess($labOrder);

        $labOrder->load(['patient', 'doctor', 'items.labTest', 'items.result']);
        return view('lab.orders.print', compact('labOrder'));
    }

    private function calculateFlag($value, $range)
    {
        
        if (str_contains($range, '-')) {
            [$min, $max] = explode('-', $range);
            if ((float)$value < (float)$min) return 'low';
            if ((float)$value > (float)$max) return 'high';
            return 'normal';
        }
        return 'normal';
    }
}