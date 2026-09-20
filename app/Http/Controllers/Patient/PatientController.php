<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\ActivityLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\SubscriptionLimitService;
use App\Support\DataTableFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class PatientController extends Controller
{
    public function __construct(private readonly SubscriptionLimitService $limits)
    {
    }

    public function index()
    {
        $statsQuery = Patient::query();

        if (auth()->user()->hasRole('doctor')) {
            $doctorId = Doctor::where('email', auth()->user()->email)->value('id');
            $statsQuery->whereHas('appointments', fn($q) => $q->where('doctor_id', $doctorId));
        }

        $totalPatients  = (clone $statsQuery)->count();
        $activePatients = (clone $statsQuery)->active()->count();
        $ipPatients     = (clone $statsQuery)->where('patient_type', 'ip')->count();
        $opPatients     = (clone $statsQuery)->where('patient_type', 'op')->count();
        $patientUsage   = $this->limits->resourceSummary('patient');

        return view('patients.index', compact('totalPatients', 'activePatients', 'ipPatients', 'opPatients', 'patientUsage'));
    }

    // DataTables — server-side processing
    public function data(Request $request)
    {
        $query = Patient::with(['currentAdmission.ward', 'currentAdmission.bed'])
            ->select([
                'id', 'mrn', 'name', 'phone',
                'gender', 'dob', 'blood_group',
                'status', 'patient_type', 'photo',
            ]);

        // Doctor role — only patients who have an appointment with this doctor
        if (auth()->user()->hasRole('doctor')) {
            $doctorId = Doctor::where('email', auth()->user()->email)->value('id');
            $doctorId
                ? $query->whereHas('appointments', fn($q) => $q->where('doctor_id', $doctorId))
                : $query->whereRaw('1 = 0');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('patient_type')) {
            $query->where('patient_type', $request->patient_type);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        return DataTables::of($query)
            // name/phone are AES-encrypted at rest, so DataTables' default LIKE-based
            // global search can't match them. Search MRN (unencrypted) plus exact
            // blind-index hash matches on name/phone/email instead.
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }

                $hash = Patient::hashFor($search);
                $query->where(function ($q) use ($search, $hash) {
                    $q->where('mrn', 'like', "{$search}%");
                    if ($hash) {
                        $q->orWhere('name_hash', $hash)
                          ->orWhere('phone_hash', $hash)
                          ->orWhere('email_hash', $hash);
                    }
                });
            }, true)
            ->addColumn('photo_url', fn($p) => $p->photo_url)
            ->addColumn('department', fn($p) => $p->currentAdmission?->ward?->type ?? '—')
            ->addColumn('ward_name', fn($p) => $p->currentAdmission?->ward?->name ?? '—')
            ->addColumn('bed_no', fn($p) => $p->currentAdmission?->bed?->bed_no ?? '—')
            ->addColumn('status_badge', fn($p) => DataTableFormat::badge($p->status, [
                'active' => 'success', 'deceased' => 'danger', 'discharged' => 'secondary',
            ]))
            ->addColumn('type_badge', fn($p) => $p->patient_type === 'ip'
                ? '<span class="badge bg-label-warning">IP</span>'
                : '<span class="badge bg-label-info">OP</span>')
            ->addColumn('actions', fn($p) => view('_partials.dt-actions', ['buttons' => [
                ['type' => 'link', 'url' => route('patients.show', $p), 'icon' => 'tabler-eye', 'class' => 'btn-label-primary', 'title' => 'View'],
                ['type' => 'button', 'onclick' => "editPatient({$p->id})", 'icon' => 'tabler-edit', 'class' => 'btn-label-warning', 'title' => 'Edit', 'can' => 'edit patients'],
                ['type' => 'button', 'onclick' => "deletePatient({$p->id})", 'icon' => 'tabler-trash', 'class' => 'btn-label-danger', 'title' => 'Delete', 'can' => 'delete patients'],
            ]])->render())
            ->rawColumns(['status_badge', 'type_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('patients.create');
    }

    // Get JSON — Edit Offcanvas
    public function getJson(Patient $patient)
    {
        $this->authorize('view', $patient);

        return response()->json($patient);
    }

    public function edit(Patient $patient)
    {
        $this->authorize('view', $patient);

        return response()->json($patient);
    }

    public function store(StorePatientRequest $request)
    {
        try {
            $this->limits->assertCanCreate('patient');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $photo = $request->hasFile('photo') ? $request->file('photo')->store('patients', 'public') : null;

        $patient = Patient::create([
            ...$request->safe()->except(['status', 'photo']),
            'status' => $request->status ?? 'active',
            'photo'  => $photo,
        ]);

        ActivityLog::log('created', 'Patient', 'Patient ' . $patient->mrn . ' registered', $patient);

        // Auto create user account if email provided and enabled in settings
        if ($request->email && config('hospital.auto_patient_login')) {
            $this->createPortalAccount($patient, $request->name, $request->email);
        }

        return redirect()->route('patients.index')
            ->with('success', 'Patient ' . $patient->mrn . ' added successfully!');
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        $patient->load([
            'medicalRecords', 'allergies', 'problems',
            'attachments.uploadedBy', 'appointments.doctor',
        ]);

        $referrals = \App\Models\Referral::with(['referredBy', 'referredTo'])
            ->where('patient_id', $patient->id)
            ->orderBy('referral_date', 'desc')
            ->get();

        $followups = $patient->appointments()
            ->where('type', 'followup')
            ->with('doctor')
            ->orderBy('appointment_date', 'desc')
            ->get();

        $currentAdmission = $patient->currentAdmission()->with('ward')->first();
        $showIcuTab = $patient->icuCharts()->exists()
            || ($currentAdmission && in_array($currentAdmission->ward?->type, ['icu', 'nicu']));

        return view('patients.show', compact('patient', 'referrals', 'followups', 'showIcuTab'));
    }

    public function print(Patient $patient)
    {
        $this->authorize('view', $patient);

        $patient->load(['medicalRecords', 'allergies', 'problems']);

        return view('patients.print', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            // Only delete the old file if it's actually on the storage disk — the
            // legacy seeded stock photos under assets/patients/ are plain public
            // files, not managed by Storage, and must never be deleted this way.
            if ($patient->photo && !str_starts_with($patient->photo, 'assets/')) {
                Storage::disk('public')->delete($patient->photo);
            }
            $data['photo'] = $request->file('photo')->store('patients', 'public');
        }

        $patient->update($data);
        ActivityLog::log('updated', 'Patient', 'Patient ' . $patient->name . ' updated', $patient);

        // back() so an edit submitted from the patient's own detail page returns
        // there instead of always bouncing to the index list (index-page edits
        // still land back on index, since that's where they were submitted from).
        return back()->with('success', 'Patient updated successfully!');
    }

    public function destroy(Patient $patient)
    {
        abort_unless(auth()->user()->can('delete patients'), 403);

        ActivityLog::log('deleted', 'Patient', 'Patient ' . $patient->name . ' deleted', $patient);
        $patient->delete(); // soft delete — medical records are retained

        return response()->json(['success' => true]);
    }

    private function createPortalAccount(Patient $patient, string $name, string $email): void
    {
        $existingUser = \App\Models\User::where('email', $email)->first();

        if ($existingUser) {
            $patient->update(['user_id' => $existingUser->id]);

            return;
        }

        $defaultPassword = 'Patient@' . substr($patient->mrn, -4);
        $user = \App\Models\User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($defaultPassword),
        ]);
        $user->assignRole('patient');
        $patient->update(['user_id' => $user->id]);

        session()->flash('user_created', 'Patient login created! Email: ' . $email . ' | Password: ' . $defaultPassword);
    }
}
