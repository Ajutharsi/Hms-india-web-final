<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientAttachment;
use App\Models\PatientMedicalRecord;
use App\Models\PatientProblem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The patient's medical record, for the clinical roles.
 *
 * Mirrors App\Http\Controllers\Patient\EMRController, which the web has
 * had from the start: history, allergies, problem list and attachments.
 * The app could show consultations and vitals but nothing of this, so a
 * doctor on the ward could not see that a patient is allergic to
 * penicillin.
 *
 * Allergies come first in the payload for that reason.
 */
class EmrController extends Controller
{
    use AuthorizesRoles;

    /** Everyone who treats the patient may read and write the record. */
    private function authClinical(Request $request)
    {
        return $this->authRoles($request, ['doctor', 'nurse'], 'This account cannot open patient records.');
    }

    public function show(Request $request, Patient $patient)
    {
        $this->authClinical($request);

        $patient->load(['allergies', 'problems', 'medicalRecords', 'attachments.uploadedBy']);

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'mrn' => $patient->mrn,
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
            ],
            // Severe allergies are the reason this screen exists, so they
            // are sorted to the top rather than left in insert order.
            'allergies' => $patient->allergies
                ->sortByDesc(fn ($a) => ['mild' => 0, 'moderate' => 1, 'severe' => 2][$a->severity] ?? 0)
                ->values()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'allergen' => $a->allergen,
                    'type' => $a->type,
                    'severity' => $a->severity,
                    'reaction' => $a->reaction,
                ])->all(),
            'problems' => $patient->problems->map(fn ($p) => [
                'id' => $p->id,
                'problem_name' => $p->problem_name,
                'onset_date' => optional($p->onset_date)->format('Y-m-d') ?? (string) $p->onset_date,
                'status' => $p->status,
                'notes' => $p->notes,
            ])->all(),
            'records' => $patient->medicalRecords
                ->sortByDesc('record_date')
                ->values()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'record_type' => $r->record_type,
                    'record_date' => optional($r->record_date)->format('Y-m-d') ?? (string) $r->record_date,
                    'description' => $r->description,
                    'doctor_name' => $r->doctor_name,
                    'notes' => $r->notes,
                ])->all(),
            'attachments' => $patient->attachments->map(fn ($a) => [
                'id' => $a->id,
                'file_name' => $a->file_name,
                'file_type' => $a->file_type,
                'file_size' => $a->file_size,
                'description' => $a->description,
                'uploaded_by' => $a->uploadedBy?->name,
                'url' => Storage::disk('public')->url($a->file_path),
            ])->all(),
        ]);
    }

    public function storeAllergy(Request $request, Patient $patient)
    {
        $this->authClinical($request);

        $validated = $request->validate([
            'allergen' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'in:food,drug,environmental,other'],
            'severity' => ['required', 'in:mild,moderate,severe'],
            'reaction' => ['nullable', 'string', 'max:1000'],
        ]);

        $allergy = $patient->allergies()->create($validated);

        return response()->json(['message' => 'Allergy added.', 'id' => $allergy->id], 201);
    }

    public function destroyAllergy(Request $request, PatientAllergy $allergy)
    {
        $this->authClinical($request);
        $allergy->delete();

        return response()->json(['message' => 'Allergy removed.']);
    }

    public function storeProblem(Request $request, Patient $patient)
    {
        $this->authClinical($request);

        $validated = $request->validate([
            'problem_name' => ['required', 'string', 'min:2', 'max:255'],
            'onset_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,resolved,chronic'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $problem = $patient->problems()->create($validated);

        return response()->json(['message' => 'Problem added.', 'id' => $problem->id], 201);
    }

    public function updateProblem(Request $request, PatientProblem $problem)
    {
        $this->authClinical($request);

        $validated = $request->validate([
            'status' => ['required', 'in:active,resolved,chronic'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $problem->update($validated);

        return response()->json(['message' => 'Problem updated.']);
    }

    public function destroyProblem(Request $request, PatientProblem $problem)
    {
        $this->authClinical($request);
        $problem->delete();

        return response()->json(['message' => 'Problem removed.']);
    }

    public function storeRecord(Request $request, Patient $patient)
    {
        $user = $this->authClinical($request);

        $validated = $request->validate([
            'record_type' => ['required', 'string', 'max:255'],
            'record_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:1000'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // The web form asks who wrote it; from the app we already know.
        $record = $patient->medicalRecords()->create(
            $validated + ['doctor_name' => $validated['doctor_name'] ?? $user->name]
        );

        return response()->json(['message' => 'Record added.', 'id' => $record->id], 201);
    }

    public function destroyRecord(Request $request, PatientMedicalRecord $record)
    {
        $this->authClinical($request);
        $record->delete();

        return response()->json(['message' => 'Record removed.']);
    }

    public function storeAttachment(Request $request, Patient $patient)
    {
        $user = $this->authClinical($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $path = $file->storeAs(
            "patients/{$patient->id}/attachments",
            time() . '_' . $file->getClientOriginalName(),
            'public'
        );

        $attachment = $patient->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => round($file->getSize() / 1024, 2) . ' KB',
            'description' => $request->description,
            'uploaded_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'File uploaded.',
            'id' => $attachment->id,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }

    public function destroyAttachment(Request $request, PatientAttachment $attachment)
    {
        $this->authClinical($request);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment removed.']);
    }
}
