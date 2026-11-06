<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\NursingAssessment;
use App\Models\Patient;
use Illuminate\Http\Request;

/**
 * Scored bedside assessments.
 *
 * Fall risk, pain, pressure-sore risk and the early warning score had
 * nowhere to live, so they were going into free-text nursing notes,
 * where nothing can trend them or flag the dangerous ones. Here they
 * are numbers, and the list leads with the patients who need attention.
 */
class NursingAssessmentController extends Controller
{
    use AuthorizesRoles;

    private function authClinical(Request $request)
    {
        return $this->authRoles($request, ['nurse', 'doctor'], 'This account cannot record assessments.');
    }

    /**
     * Recent assessments, newest first, or one patient's history.
     */
    public function index(Request $request)
    {
        $this->authClinical($request);

        $rows = NursingAssessment::with(['patient:id,name,mrn', 'assessedBy:id,name'])
            ->when($request->patient_id, fn ($q, $id) => $q->where('patient_id', $id))
            ->latest('assessed_at')
            ->limit(100)
            ->get();

        return response()->json([
            'stats' => [
                'assessments' => $rows->count(),
                'needs_attention' => $rows->filter(fn ($a) => $a->needs_attention)->count(),
            ],
            'assessments' => $rows->map(fn ($a) => $this->payload($a))->all(),
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $user = $this->authClinical($request);

        $validated = $request->validate([
            'assessed_at' => ['nullable', 'date'],
            'fall_risk_score' => ['nullable', 'integer', 'min:0', 'max:125'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'braden_score' => ['nullable', 'integer', 'min:6', 'max:23'],
            'ews_score' => ['nullable', 'integer', 'min:0', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // An assessment with no scores records nothing.
        $scores = ['fall_risk_score', 'pain_score', 'braden_score', 'ews_score'];
        $given = collect($scores)->filter(fn ($k) => ($validated[$k] ?? null) !== null);
        abort_if($given->isEmpty(), 422, 'Record at least one score.');

        // Tie it to the stay it belongs to, if the patient is admitted.
        $admission = Admission::where('patient_id', $patient->id)
            ->where('status', 'admitted')
            ->latest('admission_date')
            ->first();

        $assessment = NursingAssessment::create($validated + [
            'patient_id' => $patient->id,
            'admission_id' => $admission?->id,
            'assessed_by' => $user->id,
            'assessed_at' => $validated['assessed_at'] ?? now(),
        ]);

        return response()->json([
            'message' => $assessment->needs_attention ? 'Saved. This patient needs attention.' : 'Assessment saved.',
            'id' => $assessment->id,
        ], 201);
    }

    public function destroy(Request $request, NursingAssessment $assessment)
    {
        $user = $this->authClinical($request);

        // A wrong score should be corrected by whoever wrote it, or by
        // an administrator; it is part of the clinical record.
        abort_unless(
            $assessment->assessed_by === $user->id || $user->hasAnyRole(['admin', 'super_admin']),
            403,
            'That assessment was recorded by somebody else.'
        );

        $assessment->delete();

        return response()->json(['message' => 'Assessment removed.']);
    }

    private function payload(NursingAssessment $a): array
    {
        return [
            'id' => $a->id,
            'patient_id' => $a->patient_id,
            'patient_name' => $a->patient?->name,
            'patient_mrn' => $a->patient?->mrn,
            'assessed_by' => $a->assessedBy?->name,
            'assessed_at' => optional($a->assessed_at)->format('Y-m-d H:i'),
            'fall_risk_score' => $a->fall_risk_score,
            'fall_risk_band' => $a->fall_risk_band,
            'pain_score' => $a->pain_score,
            'pain_band' => $a->pain_band,
            'braden_score' => $a->braden_score,
            'braden_band' => $a->braden_band,
            'ews_score' => $a->ews_score,
            'ews_band' => $a->ews_band,
            'needs_attention' => $a->needs_attention,
            'notes' => $a->notes,
        ];
    }
}
