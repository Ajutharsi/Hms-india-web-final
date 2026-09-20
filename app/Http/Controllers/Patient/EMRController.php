<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientMedicalRecord;
use App\Models\PatientAllergy;
use App\Models\PatientProblem;
use App\Models\PatientAttachment;
use Illuminate\Support\Facades\Storage;

class EMRController extends Controller
{
    public function storeMedicalRecord(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'record_type'  => 'required|string|max:255',
            'record_date'  => 'required|date',
            'description'  => 'required|string|max:1000',
            'doctor_name'  => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $patient->medicalRecords()->create($validated);

        return back()->with('success', 'Medical record added!');
    }

     public function destroyMedicalRecord(PatientMedicalRecord $record)
    {
        $record->delete();
        return response()->json(['success' => true]);
    }

    public function storeAllergy(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'allergen' => 'required|string|min:2|max:255',
            'type'     => 'required|in:food,drug,environmental,other',
            'severity' => 'required|in:mild,moderate,severe',
            'reaction' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $patient->allergies()->create($validated);

        return back()->with('success', 'Allergy added!');
    }

    public function destroyAllergy(PatientAllergy $allergy)
    {
        $allergy->delete();
        return response()->json(['success' => true]);
    }

     public function storeProblem(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'problem_name' => 'required|string|min:2|max:255',
            'onset_date'   => 'nullable|date',
            'status'       => 'required|in:active,resolved,chronic',
            'notes'        => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $patient->problems()->create($validated);

        return back()->with('success', 'Problem added!');
    }

     public function destroyProblem(PatientProblem $problem)
    {
        $problem->delete();
        return response()->json(['success' => true]);
    }

     public function storeAttachment(Request $request, Patient $patient)
    {
        $request->validate([
            'file'        => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'description' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $file     = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs("patients/{$patient->id}/attachments", $fileName, 'public');

        $patient->attachments()->create([
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $filePath,
            'file_type'   => $file->getMimeType(),
            'file_size'   => round($file->getSize() / 1024, 2) . ' KB',
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'File uploaded successfully!');
    }

      public function destroyAttachment(PatientAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return response()->json(['success' => true]);
    }

    public function downloadAttachment(PatientAttachment $attachment)
    {
        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }
}
