<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\DietPlan;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\RadiologyOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Everything a doctor orders for a patient, in one place.
 *
 * Each of these could already be created somewhere: a lab order from
 * the lab's own screen, radiology from the front desk, a prescription
 * from inside a consultation, a diet from the ward. What a doctor could
 * not do was stand at the bedside, decide on four things at once, and
 * enter them without walking through four different parts of the app.
 *
 * Nothing new is stored -- these write the same rows those screens do.
 */
class DoctorOrderController extends Controller
{
    use AuthorizesRoles;

    private function authDoctor(Request $request)
    {
        return $this->authRoles($request, ['doctor'], 'This account cannot place orders.');
    }

    /** The doctor record behind the signed-in user. */
    private function doctorId(Request $request): int
    {
        // Doctors are matched to users by email, the way DoctorController
        // has always done it; there is no user_id on the table.
        $doctor = Doctor::where('email', $request->user()->email)->first();

        abort_if($doctor === null, 403, 'This account is not linked to a doctor record yet.');

        return $doctor->id;
    }

    /**
     * What has already been ordered for this patient, newest first,
     * across all four kinds.
     */
    public function index(Request $request, Patient $patient)
    {
        $this->authDoctor($request);

        $labs = LabOrder::with('items.labTest')
            ->where('patient_id', $patient->id)
            ->latest('id')->limit(20)->get()
            ->map(fn ($o) => [
                'kind' => 'lab',
                'id' => $o->id,
                'title' => $o->order_no,
                'detail' => $o->items->map(fn ($i) => $i->labTest?->name)->filter()->join(', '),
                'status' => $o->status,
                'date' => (string) $o->order_date,
            ]);

        $radiology = RadiologyOrder::where('patient_id', $patient->id)
            ->latest('id')->limit(20)->get()
            ->map(fn ($o) => [
                'kind' => 'radiology',
                'id' => $o->id,
                'title' => $o->order_no,
                'detail' => trim(($o->modality ?? '') . ' ' . ($o->body_part ?? '')),
                'status' => $o->status,
                'date' => optional($o->order_date)->format('Y-m-d') ?? (string) $o->order_date,
            ]);

        $prescriptions = Prescription::with('items')
            ->where('patient_id', $patient->id)
            ->latest('id')->limit(20)->get()
            ->map(fn ($p) => [
                'kind' => 'medicine',
                'id' => $p->id,
                'title' => $p->prescription_no,
                'detail' => $p->items->map(fn ($i) => $i->medicine_name)->filter()->join(', '),
                'status' => $p->status,
                'date' => (string) $p->prescription_date,
            ]);

        $diets = DietPlan::where('patient_id', $patient->id)
            ->latest('id')->limit(20)->get()
            ->map(fn ($d) => [
                'kind' => 'diet',
                'id' => $d->id,
                'title' => $d->diet_type,
                'detail' => $d->restrictions,
                'status' => $d->status,
                'date' => optional($d->created_at)->format('Y-m-d'),
            ]);

        // A prescription hangs off a consultation, so the app needs to
        // know whether there is one to hang it on.
        $consultation = Consultation::where('patient_id', $patient->id)
            ->where('doctor_id', $this->doctorId($request))
            ->latest('id')
            ->first();

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'mrn' => $patient->mrn,
            ],
            'can_prescribe' => $consultation !== null,
            'orders' => $labs->concat($radiology)->concat($prescriptions)->concat($diets)
                ->sortByDesc('date')->values()->all(),
        ]);
    }

    public function storeLab(Request $request, Patient $patient)
    {
        $this->authDoctor($request);

        $validated = $request->validate([
            'tests' => ['required', 'array', 'min:1'],
            'tests.*' => ['exists:lab_tests,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($validated, $patient, $request) {
            $order = LabOrder::create([
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctorId($request),
                'order_date' => now()->toDateString(),
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['tests'] as $testId) {
                LabOrderItem::create([
                    'lab_order_id' => $order->id,
                    'lab_test_id' => $testId,
                    'status' => 'pending',
                ]);
            }

            return $order;
        });

        return response()->json(['message' => "Lab order {$order->order_no} placed.", 'id' => $order->id], 201);
    }

    public function storeRadiology(Request $request, Patient $patient)
    {
        $this->authDoctor($request);

        $validated = $request->validate([
            'modality' => ['required', 'string', 'max:60'],
            'body_part' => ['required', 'string', 'max:120'],
            // NOT NULL on the table, so the form has to ask for it.
            'study_description' => ['required', 'string', 'max:255'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'in:routine,urgent,emergency'],
        ]);

        $order = RadiologyOrder::create($validated + [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctorId($request),
            'order_date' => now()->toDateString(),
            'status' => 'ordered',
            'priority' => $validated['priority'] ?? 'routine',
        ]);

        return response()->json(['message' => 'Radiology order placed.', 'id' => $order->id], 201);
    }

    public function storeMedicines(Request $request, Patient $patient)
    {
        $doctorId = $this->doctorId($request);
        $this->authDoctor($request);

        $validated = $request->validate([
            'medicines' => ['required', 'array', 'min:1'],
            'medicines.*.medicine_name' => ['required', 'string', 'max:255'],
            'medicines.*.dosage' => ['required', 'string', 'max:100'],
            'medicines.*.frequency' => ['required', 'string', 'max:100'],
            'medicines.*.duration' => ['required', 'string', 'max:100'],
            'medicines.*.route' => ['nullable', 'string', 'max:50'],
            'medicines.*.instructions' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // A prescription belongs to a visit, and the table says so: there
        // is no prescription without a consultation to hang it on.
        $consultation = Consultation::where('patient_id', $patient->id)
            ->where('doctor_id', $doctorId)
            ->latest('id')
            ->first();

        abort_if(
            $consultation === null,
            422,
            'Record a consultation for this patient first -- a prescription belongs to a visit.'
        );

        $prescription = DB::transaction(function () use ($validated, $patient, $doctorId, $consultation) {
            $prescription = Prescription::create([
                'consultation_id' => $consultation->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'prescription_date' => now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ]);

            foreach ($validated['medicines'] as $medicine) {
                PrescriptionItem::create($medicine + ['prescription_id' => $prescription->id]);
            }

            return $prescription;
        });

        return response()->json([
            'message' => "Prescription {$prescription->prescription_no} written.",
            'id' => $prescription->id,
        ], 201);
    }

    public function storeDiet(Request $request, Patient $patient)
    {
        $this->authDoctor($request);

        $validated = $request->validate([
            'diet_type' => ['required', 'in:normal,diabetic,liquid,soft,low_salt,npo,other'],
            'restrictions' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $diet = DietPlan::create($validated + [
            'patient_id' => $patient->id,
            'status' => 'active',
        ]);

        return response()->json(['message' => 'Diet order placed.', 'id' => $diet->id], 201);
    }
}
