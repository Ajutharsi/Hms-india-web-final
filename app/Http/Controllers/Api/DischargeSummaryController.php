<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Consultation;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\Vital;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The document a patient leaves with.
 *
 * Nothing here is new information: the diagnosis is in the
 * consultations, the drugs are in the prescriptions, the tests are in
 * the lab orders. What was missing was a way to see a stay as one
 * thing, and somewhere for the doctor's own closing note to live --
 * admissions already had a discharge_notes column that nothing wrote to.
 *
 * So this gathers the stay and saves that note; it does not re-key the
 * clinical record.
 */
class DischargeSummaryController extends Controller
{
    use AuthorizesRoles;

    private function authClinical(Request $request)
    {
        return $this->authRoles($request, ['doctor', 'nurse'], 'This account cannot write discharge summaries.');
    }

    /**
     * Admitted and recently discharged stays, newest first.
     */
    public function index(Request $request)
    {
        $this->authClinical($request);

        $admissions = Admission::with(['patient:id,name,mrn', 'ward:id,name', 'bed:id,bed_no', 'doctor:id,name'])
            ->when($request->get('state') === 'discharged', fn ($q) => $q->where('status', 'discharged'))
            ->when($request->get('state', 'admitted') === 'admitted', fn ($q) => $q->where('status', 'admitted'))
            ->latest('admission_date')
            ->limit(100)
            ->get();

        return response()->json([
            'stats' => [
                'admitted' => Admission::where('status', 'admitted')->count(),
                // A discharged stay with nothing written is the one that
                // needs chasing.
                'unwritten' => Admission::where('status', 'discharged')
                    ->where(fn ($q) => $q->whereNull('discharge_notes')->orWhere('discharge_notes', ''))
                    ->count(),
            ],
            'admissions' => $admissions->map(fn (Admission $a) => [
                'id' => $a->id,
                'admission_no' => $a->admission_no,
                'patient_name' => $a->patient?->name,
                'patient_mrn' => $a->patient?->mrn,
                'ward_name' => $a->ward?->name,
                'bed_no' => $a->bed?->bed_no,
                'doctor_name' => $a->doctor?->name,
                'admission_date' => $a->admission_date ? Carbon::parse($a->admission_date)->format('Y-m-d') : null,
                'discharge_date' => $a->discharge_date ? Carbon::parse($a->discharge_date)->format('Y-m-d') : null,
                'status' => $a->status,
                'has_summary' => filled($a->discharge_notes),
            ])->all(),
        ]);
    }

    /**
     * One stay, gathered: who, where, how long, what was found, what was
     * given and what was tested.
     */
    public function show(Request $request, Admission $admission)
    {
        $this->authClinical($request);

        $admission->load(['patient', 'ward:id,name', 'bed:id,bed_no', 'doctor:id,name']);

        // Admission dates are not cast on the model, so they arrive as
        // strings; everything below does date maths on them.
        $from = $admission->admission_date ? Carbon::parse($admission->admission_date)->startOfDay() : now()->subYear();
        $to = $admission->discharge_date ? Carbon::parse($admission->discharge_date)->endOfDay() : now();

        // A consultation carries no date of its own -- it is dated by
        // when it was written.
        $consultations = Consultation::with('doctor:id,name')
            ->where('patient_id', $admission->patient_id)
            ->whereBetween('created_at', [$from, $to])
            ->latest('created_at')
            ->get();

        $prescriptions = Prescription::with('items')
            ->where('patient_id', $admission->patient_id)
            ->whereBetween('prescription_date', [$from, $to])
            ->latest('id')
            ->get();

        $labOrders = LabOrder::with('items.labTest')
            ->where('patient_id', $admission->patient_id)
            ->whereBetween('order_date', [$from, $to])
            ->latest('id')
            ->get();

        $vitals = Vital::where('patient_id', $admission->patient_id)
            ->whereBetween('recorded_at', [$from, $to])
            ->latest('recorded_at')
            ->first();

        $days = $admission->admission_date ? (int) $from->diffInDays($to) + 1 : null;

        return response()->json([
            'admission' => [
                'id' => $admission->id,
                'admission_no' => $admission->admission_no,
                'patient_name' => $admission->patient?->name,
                'patient_mrn' => $admission->patient?->mrn,
                'ward_name' => $admission->ward?->name,
                'bed_no' => $admission->bed?->bed_no,
                'doctor_name' => $admission->doctor?->name,
                'admission_date' => $admission->admission_date ? Carbon::parse($admission->admission_date)->format('Y-m-d') : null,
                'discharge_date' => $admission->discharge_date ? Carbon::parse($admission->discharge_date)->format('Y-m-d') : null,
                'days' => $days,
                'status' => $admission->status,
                'admission_reason' => $admission->admission_reason,
                'discharge_notes' => $admission->discharge_notes,
                'total_charges' => (float) $admission->total_charges,
            ],
            'diagnoses' => $consultations->map(fn ($c) => [
                'date' => optional($c->created_at)->format('Y-m-d'),
                'doctor_name' => $c->doctor?->name,
                'chief_complaint' => $c->chief_complaint,
                'diagnosis' => $c->diagnosis,
                'icd10_code' => $c->icd10_code,
                'treatment_plan' => $c->treatment_plan,
            ])->all(),
            'medicines' => $prescriptions->flatMap(fn ($p) => $p->items->map(fn ($i) => [
                'prescription_no' => $p->prescription_no,
                'medicine_name' => $i->medicine_name,
                'dosage' => $i->dosage,
                'frequency' => $i->frequency,
                'duration' => $i->duration,
            ]))->all(),
            'tests' => $labOrders->flatMap(fn ($o) => $o->items->map(fn ($i) => [
                'order_no' => $o->order_no,
                'test_name' => $i->labTest?->name,
                'status' => $i->status,
            ]))->all(),
            'last_vitals' => $vitals === null ? null : [
                'blood_pressure' => $vitals->blood_pressure,
                'temperature' => $vitals->temperature,
                'pulse_rate' => $vitals->pulse_rate,
                'spo2' => $vitals->spo2,
                'recorded_at' => optional($vitals->recorded_at)->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Save the closing note. Discharging the patient stays a ward
     * action -- this is the document, not the bed movement.
     */
    public function store(Request $request, Admission $admission)
    {
        $this->authClinical($request);

        $validated = $request->validate([
            'discharge_notes' => ['required', 'string', 'max:5000'],
        ]);

        $admission->update($validated);

        return response()->json(['message' => 'Discharge summary saved.']);
    }
}
