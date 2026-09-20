<?php

namespace App\Http\Controllers\Ward;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WardController extends Controller
{
    public function index()
    {
        return view('ward.index');
    }

    public function data()
    {
        $query = Ward::query()->latest();

        return DataTables::of($query)
            ->addColumn('name_display', fn($w) => '<strong>' . e($w->name) . '</strong>')
            ->addColumn('type_badge', function ($w) {
                $colors = ['general' => 'primary', 'private' => 'success', 'semi_private' => 'info', 'icu' => 'danger', 'nicu' => 'warning', 'emergency' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$w->type] ?? 'secondary') . ' text-capitalize">' . str_replace('_', ' ', $w->type) . '</span>';
            })
            ->addColumn('available_beds_badge', fn($w) => '<span class="badge bg-label-success">' . $w->available_beds . '</span>')
            ->addColumn('occupied_beds_badge', fn($w) => '<span class="badge bg-label-danger">' . ($w->total_beds - $w->available_beds) . '</span>')
            ->addColumn('charge_fmt', fn($w) => 'LKR ' . number_format($w->charge_per_day, 2))
            ->addColumn('status_badge', fn($w) => '<span class="badge bg-label-' . ($w->status === 'active' ? 'success' : 'danger') . '">' . $w->status . '</span>')
            ->addColumn('actions', fn($w) => '
                <div class="d-flex gap-2">
                    <a href="/ward/' . $w->id . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                        <i class="icon-base ti tabler-eye"></i>
                    </a>
                    <button onclick="editWard(' . $w->id . ')" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                        <i class="icon-base ti tabler-edit"></i>
                    </button>
                    <button onclick="deleteWard(' . $w->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
                </div>
            ')
            ->rawColumns(['name_display', 'type_badge', 'available_beds_badge', 'occupied_beds_badge', 'status_badge', 'actions'])
            ->make(true);
    }

   public function store(Request $request)
{
    $validated = $request->validate([
        'name'           => 'required|string|min:2|max:255',
        'type'           => 'required|in:general,private,semi_private,icu,nicu,emergency',
        'floor'          => 'nullable|string|max:255',
        'building'       => 'nullable|string|max:255',
        'total_beds'     => 'required|integer|min:1',
        'charge_per_day' => 'required|numeric|min:0',
        'status'         => 'nullable|in:active,inactive',
        'description'    => 'nullable|string|max:1000',
    ], $this->validationMessages());

    Ward::create([
        ...$validated,
        'available_beds' => $validated['total_beds'],
    ]);

    return redirect()->route('ward.index')
        ->with('success', 'Ward created successfully!');
}

    public function show(Ward $ward)
    {
        $ward->load(['beds.currentAdmission.patient', 'admissions' => fn($q) => $q->with(['patient', 'doctor'])->latest()->take(10)]);
        return view('ward.show', compact('ward'));
    }

    public function getJson(Ward $ward)
    {
        return response()->json($ward);
    }

    public function update(Request $request, Ward $ward)
    {
        $validated = $request->validate([
            'name'           => 'required|string|min:2|max:255',
            'type'           => 'required|in:general,private,semi_private,icu,nicu,emergency',
            'floor'          => 'nullable|string|max:255',
            'building'       => 'nullable|string|max:255',
            'charge_per_day' => 'required|numeric|min:0',
            'status'         => 'nullable|in:active,inactive',
            'description'    => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $ward->update($validated);

        return redirect()->route('ward.index')
            ->with('success', 'Ward updated successfully!');
    }

    public function destroy(Ward $ward)
    {
        $ward->delete();
        return response()->json(['success' => true]);
    }

    // Beds
    public function beds(Ward $ward)
    {
        $beds = Bed::with('currentAdmission.patient')
            ->where('ward_id', $ward->id)
            ->get()
            ->map(fn($b) => [
                'id'      => $b->id,
                'bed_no'  => $b->bed_no,
                'type'    => $b->type,
                'status'  => $b->status,
                'patient' => $b->currentAdmission?->patient?->name ?? null,
            ]);

        return response()->json(['data' => $beds]);
    }

    // Admissions
    public function admissions(Request $request)
    {
        $admissionStats = [
            'total'         => Admission::count(),
            'admitted'      => Admission::where('status', 'admitted')->count(),
            'discharged'    => Admission::where('status', 'discharged')->count(),
            'availableBeds' => Bed::where('status', 'available')->count(),
        ];

        $activePatients = \App\Models\Patient::active()->get(['id', 'name', 'mrn']);
        $activeDoctors  = \App\Models\Doctor::active()->get(['id', 'name', 'specialization']);
        $openWards      = Ward::active()->where('available_beds', '>', 0)->get();
        $fromPatient    = $request->filled('patient_id') ? \App\Models\Patient::find($request->patient_id) : null;

        return view('ward.admissions', compact('admissionStats', 'activePatients', 'activeDoctors', 'openWards', 'fromPatient'));
    }

    // Server-side DataTables — was previously loading every admission row
    // (with 4 eager-loaded relations) into memory on every page view, an
    // unbounded query that got slower as admissions grew.
    public function admissionsData()
    {
        $query = Admission::with(['patient:id,name,mrn,photo', 'ward:id,name', 'bed:id,bed_no', 'doctor:id,name'])
            ->select(['id', 'admission_no', 'patient_id', 'ward_id', 'bed_id', 'doctor_id', 'admission_date', 'discharge_date', 'status', 'total_charges'])
            ->latest('admission_date');

        return DataTables::of($query)
            ->addColumn('patient_name', fn($a) => $a->patient->name ?? 'N/A')
            ->addColumn('patient_mrn', fn($a) => $a->patient->mrn ?? 'N/A')
            ->addColumn('patient_photo_url', fn($a) => $a->patient->photo_url ?? asset('images/defaults/patient.png'))
            ->addColumn('ward_name', fn($a) => $a->ward->name ?? 'N/A')
            ->addColumn('bed_no', fn($a) => $a->bed->bed_no ?? 'N/A')
            ->addColumn('doctor_name', fn($a) => 'Dr. ' . ($a->doctor->name ?? 'N/A'))
            ->addColumn('doctor_photo_url', fn($a) => $a->doctor->photo_url ?? asset('images/defaults/doctor.png'))
            ->editColumn('discharge_date', fn($a) => $a->discharge_date ?? '—')
            ->filterColumn('patient_name', fn($q, $kw) => $q->whereHas('patient', fn($p) => $p->where('mrn', 'like', "{$kw}%")->when(\App\Models\Patient::hashFor($kw), fn($p2, $hash) => $p2->orWhere('name_hash', $hash))))
            ->make(true);
    }

    public function admit(Request $request)
    {
        $validated = $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'ward_id'         => 'required|exists:wards,id',
            'bed_id'          => 'required|exists:beds,id',
            'doctor_id'       => 'required|exists:doctors,id',
            'admission_date'  => 'required|date',
            'admission_reason'=> 'nullable|string|max:1000',
        ], $this->validationMessages());

        // Transactional with a row lock: two simultaneous admissions can't
        // grab the same bed (the model's created event then marks it occupied).
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
                $bed = Bed::lockForUpdate()->find($validated['bed_id']);

                if ($bed->status !== 'available') {
                    throw new \RuntimeException('Bed is not available!');
                }

                // Data audit found patients with no unique-active-admission guard
                // at all — a patient could be admitted into two beds/wards at
                // once, corrupting bed occupancy and billing.
                $alreadyAdmitted = Admission::where('patient_id', $validated['patient_id'])
                    ->where('status', 'admitted')
                    ->exists();

                if ($alreadyAdmitted) {
                    throw new \RuntimeException('This patient already has an active admission. Discharge or transfer them first.');
                }

                Admission::create($validated);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ward.admissions')
            ->with('success', 'Patient admitted successfully!');
    }

    // Outstanding balance vs deposits, shown on the discharge form before confirming.
    public function balanceInfo(Admission $admission)
    {
        $invoiceTotal   = (float) $admission->invoices()->sum('total');
        $paymentsMade   = (float) $admission->invoices()->join('payments', 'payments.invoice_id', '=', 'invoices.id')->sum('payments.amount');
        $depositBalance = \App\Models\IpdDeposit::getBalanceForAdmission($admission->id);
        $outstanding    = max(0, $invoiceTotal - $paymentsMade - $depositBalance);

        return response()->json([
            'invoice_total'    => $invoiceTotal,
            'payments_made'    => $paymentsMade,
            'deposit_balance'  => $depositBalance,
            'outstanding'      => $outstanding,
        ]);
    }

    public function discharge(Request $request, Admission $admission)
    {
        $request->validate([
            'discharge_date'  => 'required|date',
            'discharge_notes' => 'nullable|string|max:1000',
            'total_charges'   => 'required|numeric|min:0',
        ], $this->validationMessages());

        $admission->update([
            'discharge_date'  => $request->discharge_date,
            'discharge_notes' => $request->discharge_notes,
            'total_charges'   => $request->total_charges,
            'status'          => 'discharged',
        ]);

        return redirect()->route('ward.admissions')
            ->with('success', 'Patient discharged successfully!');
    }

    public function availableBeds(Request $request)
    {
        $beds = Bed::where('ward_id', $request->ward_id)
            ->where('status', 'available')
            ->get()
            ->map(fn($b) => [
                'id'     => $b->id,
                'bed_no' => $b->bed_no,
                'type'   => $b->type,
            ]);

        return response()->json(['beds' => $beds]);
    }

    public function transfer(Request $request, Admission $admission)
{
    $request->validate([
        'new_ward_id' => 'required|exists:wards,id',
        'new_bed_id'  => 'required|exists:beds,id',
        'transfer_reason' => 'nullable|string|max:1000',
    ], $this->validationMessages());

    $newBed = Bed::find($request->new_bed_id);
    if ($newBed->status !== 'available') {
        return back()->with('error', 'Selected bed is not available!');
    }

    // Free old bed
    $oldWard = $admission->ward;
    Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
    Ward::where('id', $admission->ward_id)->increment('available_beds');

    // Occupy new bed
    Bed::where('id', $request->new_bed_id)->update(['status' => 'occupied']);
    Ward::where('id', $request->new_ward_id)->decrement('available_beds');

    // Update admission
    $admission->update([
        'ward_id' => $request->new_ward_id,
        'bed_id'  => $request->new_bed_id,
        'discharge_notes' => ($admission->discharge_notes ? $admission->discharge_notes . ' | ' : '') .
            'Transferred from ' . $oldWard->name . ' to new ward on ' . now()->format('d M Y') .
            ($request->transfer_reason ? ' — Reason: ' . $request->transfer_reason : ''),
    ]);

    return redirect()->route('ward.admissions')
        ->with('success', 'Patient transferred successfully!');
}
}
