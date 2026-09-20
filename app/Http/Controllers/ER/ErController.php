<?php

namespace App\Http\Controllers\ER;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\ErRegistration;
use App\Models\Patient;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ErController extends Controller
{
    public function index()
    {
        $waitingCount   = ErRegistration::where('status', 'waiting')->count();
        $underTreatment = ErRegistration::where('status', 'under_treatment')->count();
        $redAlertCount  = ErRegistration::where('triage_level', 'red')
            ->whereNotIn('status', ['discharged', 'transferred', 'expired'])->count();
        $todayTotal     = ErRegistration::whereDate('arrival_time', today())->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn', 'phone'])->sortBy(fn($p) => $p->name)->values();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('er.index', compact(
            'waitingCount', 'underTreatment', 'redAlertCount', 'todayTotal', 'patients', 'doctors'
        ));
    }

    public function data()
    {
        $query = ErRegistration::with(['patient:id,mrn', 'attendingDoctor:id,name'])->latest('arrival_time');

        return DataTables::of($query)
            ->addColumn('patient_mrn', fn($e) => $e->patient->mrn ?? '—')
            ->addColumn('attending_doctor', fn($e) => $e->attendingDoctor->name ?? '—')
            ->editColumn('arrival_time', fn($e) => $e->arrival_time?->format('d M Y h:i A'))
            ->addColumn('triage_badge', function ($e) {
                $colors = ['red' => 'danger', 'yellow' => 'warning', 'green' => 'success', 'black' => 'dark'];
                return '<span class="badge bg-label-' . ($colors[$e->triage_level] ?? 'secondary') . '">' . $e->triage_level . '</span>';
            })
            ->addColumn('status_badge', function ($e) {
                $map = ['waiting' => 'warning', 'under_treatment' => 'info', 'admitted' => 'primary', 'discharged' => 'success', 'transferred' => 'secondary', 'expired' => 'dark'];
                return '<span class="badge bg-label-' . ($map[$e->status] ?? 'secondary') . '">' . str_replace('_', ' ', $e->status) . '</span>';
            })
            ->addColumn('actions', function ($e) {
                $btns = '<div class="d-flex gap-2">';
                $btns .= '<button onclick="openManage(' . $e->id . ')" class="btn btn-sm btn-icon btn-label-info" title="Manage"><i class="icon-base ti tabler-edit"></i></button>';
                if ($e->patient_id) {
                    $btns .= '<a href="' . route('ward.admissions', ['patient_id' => $e->patient_id]) . '" class="btn btn-sm btn-icon btn-label-primary" title="Admit to Ward"><i class="icon-base ti tabler-bed"></i></a>';
                    $btns .= '<a href="' . route('appointments.index', ['patient_id' => $e->patient_id, 'type' => 'emergency']) . '" class="btn btn-sm btn-icon btn-label-warning" title="Create Appointment"><i class="icon-base ti tabler-calendar-plus"></i></a>';
                }
                $btns .= '<button onclick="deleteEr(' . $e->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete"><i class="icon-base ti tabler-trash"></i></button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['triage_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'      => 'nullable|exists:patients,id',
            'patient_name'    => 'required|string|max:255',
            'patient_phone'   => 'nullable|string|max:20',
            'age'             => 'nullable|integer|min:0|max:150',
            'gender'          => 'nullable|in:male,female,other',
            'triage_level'    => 'required|in:red,yellow,green,black',
            'chief_complaint' => 'required|string|max:500',
            'presenting_symptoms' => 'nullable|string|max:2000',
            'mode_of_arrival' => 'nullable|string|max:100',
        ], $this->validationMessages());

        ErRegistration::create([
            'patient_id'           => $request->patient_id,
            'patient_name'         => $request->patient_name,
            'patient_phone'        => $request->patient_phone,
            'age'                  => $request->age,
            'gender'               => $request->gender,
            'triaged_by'           => auth()->id(),
            'triage_level'         => $request->triage_level,
            'chief_complaint'      => $request->chief_complaint,
            'presenting_symptoms'  => $request->presenting_symptoms,
            'mode_of_arrival'      => $request->mode_of_arrival,
            'status'               => 'waiting',
            'arrival_time'         => now(),
            'triage_time'          => now(),
        ]);

        return back()->with('success', 'Patient registered in ER!');
    }

    public function show(ErRegistration $er)
    {
        $er->load(['patient', 'triagedBy', 'attendingDoctor']);
        return response()->json($er);
    }

    public function update(Request $request, ErRegistration $er)
    {
        $request->validate([
            'attending_doctor_id' => 'nullable|exists:doctors,id',
            'status'              => 'required|in:waiting,under_treatment,admitted,discharged,transferred,expired',
            'notes'               => 'nullable|string|max:2000',
            'icd10_code'          => 'nullable|string|max:20',
        ], $this->validationMessages());

        $er->update($request->only(['attending_doctor_id', 'status', 'notes', 'icd10_code']));

        return back()->with('success', 'ER record updated!');
    }

    public function triage(Request $request, ErRegistration $er)
    {
        $request->validate([
            'triage_level' => 'required|in:red,yellow,green,black',
        ], $this->validationMessages());

        $er->update([
            'triage_level' => $request->triage_level,
            'triaged_by'   => auth()->id(),
            'triage_time'  => now(),
            'status'       => 'under_treatment',
        ]);

        return response()->json(['success' => true]);
    }

    public function discharge(Request $request, ErRegistration $er)
    {
        $request->validate([
            'status' => 'required|in:discharged,admitted,transferred,expired',
            'notes'  => 'nullable|string|max:2000',
        ], $this->validationMessages());

        $er->update([
            'status'         => $request->status,
            'discharge_time' => now(),
            'notes'          => $request->notes ?? $er->notes,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(ErRegistration $er)
    {
        $er->delete();
        return response()->json(['success' => true]);
    }
}
