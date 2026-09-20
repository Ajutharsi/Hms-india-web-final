<?php

namespace App\Http\Controllers\ConsentForm;

use App\Http\Controllers\Controller;
use App\Models\ConsentForm;
use App\Models\Patient;
use App\Traits\AuthorizesAccess;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ConsentFormController extends Controller
{
    use AuthorizesAccess;

    
    private function authorizeConsentFormAccess(ConsentForm $consentForm): void
    {
        if ($this->isPatientRole()) {
            $patient = $this->getAuthPatient();
            $this->abortIfNotOwner(
                (bool) ($patient && $consentForm->patient_id === $patient->id),
                'You are not authorized to access this consent form.'
            );
        }

        if ($this->isDoctorRole()) {
            $this->abortIfNotOwner(
                $consentForm->created_by === auth()->id(),
                'You are not authorized to access this consent form.'
            );
        }
    }

    public function index()
    {
        $patients = Patient::where('status', 'active')->get(['id', 'name', 'mrn']);

        $statsQuery = ConsentForm::query();

        // Doctor role — stats scoped to consent forms they created
        if (auth()->user()->hasRole('doctor')) {
            $statsQuery->where('created_by', auth()->id());
        }

        $totalConsentForms     = (clone $statsQuery)->count();
        $pendingConsentForms   = (clone $statsQuery)->where('status', 'pending')->count();
        $signedConsentForms    = (clone $statsQuery)->where('status', 'signed')->count();
        $declinedConsentForms  = (clone $statsQuery)->where('status', 'declined')->count();

        return view('consent-forms.index', compact(
            'patients',
            'totalConsentForms', 'pendingConsentForms', 'signedConsentForms', 'declinedConsentForms'
        ));
    }

    public function data()
    {
        $query = ConsentForm::with(['patient:id,name,mrn', 'createdBy:id,name'])
            ->orderBy('created_at', 'desc');

        // Doctor role — only consent forms they created
        if (auth()->user()->hasRole('doctor')) {
            $query->where('created_by', auth()->id());
        }

        return DataTables::of($query)
            ->addColumn('patient_info', fn($f) => '<div><strong>' . e($f->patient->name ?? 'N/A') . '</strong><br><small class="text-muted">' . e($f->patient->mrn ?? 'N/A') . '</small></div>')
            ->editColumn('procedure_name', fn($f) => $f->procedure_name ?? 'N/A')
            ->addColumn('actions', fn($f) => '
                <div class="d-flex gap-1">
                    ' . ($f->status === 'pending' ? '<a href="/consent-forms/' . $f->id . '/sign" class="btn btn-sm btn-icon btn-label-warning" title="Sign"><i class="icon-base ti tabler-writing"></i></a>' : '') . '
                    ' . ($f->status === 'signed' ? '<a href="/consent-forms/' . $f->id . '/print" target="_blank" class="btn btn-sm btn-icon btn-label-info" title="Print"><i class="icon-base ti tabler-printer"></i></a>' : '') . '
                    <button onclick="deleteConsent(' . $f->id . ')" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
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
            'patient_id'     => 'required|exists:patients,id',
            'consent_type'   => 'required|in:general,surgery,anesthesia,blood_transfusion,procedure,other',
            'procedure_name' => 'nullable|string|max:255',
            'description'    => 'required|string|max:1000',
            'witness_name'   => 'nullable|string|max:255',
            'doctor_name'    => 'nullable|string|max:255',
            'consent_date'   => 'required|date',
        ], $this->validationMessages());

        $patient = Patient::find($request->patient_id);

        $last      = ConsentForm::withTrashed()->latest('id')->first();
        $number    = $last ? (intval(substr($last->consent_no, 4)) + 1) : 1;
        $consentNo = 'CNS-' . str_pad($number, 4, '0', STR_PAD_LEFT);

        $form = ConsentForm::create([
            'consent_no'     => $consentNo,
            'patient_id'     => $request->patient_id,
            'consent_type'   => $request->consent_type,
            'procedure_name' => $request->procedure_name,
            'description'    => $request->description,
            'patient_name'   => $patient->name,
            'witness_name'   => $request->witness_name,
            'doctor_name'    => $request->doctor_name,
            'consent_date'   => $request->consent_date,
            'status'         => 'pending',
            'created_by'     => auth()->id(),
        ]);

        return redirect()->route('consent-forms.sign', $form->id)
            ->with('success', 'Consent form ' . $consentNo . ' created!');
    }

    public function sign(ConsentForm $consentForm)
    {
        $this->authorizeConsentFormAccess($consentForm);

        $consentForm->load('patient');
        return view('consent-forms.sign', compact('consentForm'));
    }

    public function saveSignature(Request $request, ConsentForm $consentForm)
    {
        $this->authorizeConsentFormAccess($consentForm);

        $request->validate([
            // These are base64 data-URI canvas exports, not short text — capped
            // to match the size ceiling every file-upload field elsewhere in
            // this app already enforces (was previously fully unbounded).
            'patient_signature' => 'required|string|max:2000000',
            'witness_signature' => 'nullable|string|max:2000000',
        ], $this->validationMessages());

        $consentForm->update([
            'patient_signature' => $request->patient_signature,
            'witness_signature' => $request->witness_signature,
            'status'            => 'signed',
        ]);

        return redirect()->route('consent-forms.print', $consentForm->id)
            ->with('success', 'Consent form signed successfully!');
    }

    public function print(ConsentForm $consentForm)
    {
        $this->authorizeConsentFormAccess($consentForm);

        $consentForm->load('patient');
        return view('consent-forms.print', compact('consentForm'));
    }

    public function destroy(ConsentForm $consentForm)
    {
        $this->authorizeConsentFormAccess($consentForm);

        $consentForm->delete();
        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, ConsentForm $consentForm)
{
    $this->authorizeConsentFormAccess($consentForm);

    $request->validate([
        'status' => 'required|in:pending,signed,declined',
    ], $this->validationMessages());
    $consentForm->update(['status' => $request->status]);
    return response()->json(['success' => true]);
}
}