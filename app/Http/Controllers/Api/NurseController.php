<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\BloodTransfusion;
use App\Models\BloodUnit;
use App\Models\ConsentForm;
use App\Models\DietPlan;
use App\Models\Doctor;
use App\Models\ErRegistration;
use App\Models\FluidLog;
use App\Models\FoodLog;
use App\Models\IcuChart;
use App\Models\MedicationAdministration;
use App\Models\NursingNote;
use App\Models\OtSchedule;
use App\Models\Patient;
use App\Models\RoundAssignment;
use App\Models\ShiftHandover;
use App\Models\ShiftHandoverPatient;
use App\Models\ShiftHandoverTask;
use App\Models\StaffRound;
use App\Models\User;
use App\Models\Vital;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * JSON endpoints for the Flutter nurse app — mirrors the web nurse portal's
 * controllers (Ward, EMar, Vitals, Nursing, ShiftHandover, OT, ICU,
 * BloodBank, ConsentForm, ER, FoodIntake, FluidIntake, StaffRounds) as one
 * controller, same pattern as PatientController for the patient role.
 *
 * Nurses have no separate domain profile (unlike Patient::where('user_id',
 * ...)) — every nurse-authored record just stores auth()->id() directly, so
 * authNurse() only verifies the role and returns the plain User.
 */
class NurseController extends Controller
{
    private function authNurse(Request $request): User
    {
        abort_unless($request->user()->hasRole('nurse'), 403, 'This account is not a nurse account.');

        return $request->user();
    }

    /**
     * Patients, Food/Fluid Intake, Rounds, and Profile are shared between
     * the nurse and staff roles on the web (same `role:...|nurse|staff`
     * route middleware) — used by both the nurse and staff mobile apps.
     */
    private function authNurseOrStaff(Request $request): User
    {
        abort_unless($request->user()->hasAnyRole(['nurse', 'staff']), 403, 'This account cannot access this feature.');

        return $request->user();
    }

    /**
     * The clinical records a doctor reads and writes alongside the nurse:
     * admissions, vitals, eMAR, nursing notes, OT, ICU and the blood bank.
     * The web puts the doctor on every one of those route groups
     * (`role:...|doctor|nurse`); the mobile API had left the doctor out, so
     * a doctor opening any of these screens got a 403.
     *
     * Shift handover is deliberately not on this list. The web keeps it to
     * `role:super_admin|admin|nurse`, and accepting a handover is something
     * only the incoming nurse can do.
     */
    private function authNurseOrDoctor(Request $request): User
    {
        abort_unless($request->user()->hasAnyRole(['nurse', 'doctor']), 403, 'This account cannot access this feature.');

        return $request->user();
    }

    /**
     * Ward (list/show/available-beds/admit), Consent Forms, and ER, which
     * the web shares between all three of these roles. Discharge, transfer
     * and admission balance are not here: the web excludes the receptionist
     * from those specifically, so they sit on [authNurseOrDoctor] instead.
     */
    private function authWardTeam(Request $request): User
    {
        abort_unless($request->user()->hasAnyRole(['nurse', 'receptionist', 'doctor']), 403, 'This account cannot access this feature.');

        return $request->user();
    }

    private function patientSearch($query, ?string $keyword)
    {
        if (!$keyword) {
            return $query;
        }

        $hash = Patient::hashFor($keyword);

        return $query->where(function ($q) use ($keyword, $hash) {
            $q->where('mrn', 'like', "{$keyword}%");
            if ($hash) {
                $q->orWhere('name_hash', $hash);
            }
        });
    }

    // ============================================================
    // Dashboard
    // ============================================================

    public function dashboard(Request $request)
    {
        $nurse = $this->authNurse($request);
        $userId = $nurse->id;

        $ipPatients = Patient::where('patient_type', 'ip')->active()->count();
        $overdueRounds = RoundAssignment::where('staff_id', $userId)->where('status', 'active')->where('next_round_at', '<=', now())->count();
        $todayFoodLogs = FoodLog::whereDate('log_date', today())->count();
        $todayFluidLogs = FluidLog::whereDate('log_date', today())->count();
        $pendingMeds = MedicationAdministration::where('status', 'scheduled')->count();
        $needsVitals = Patient::where('patient_type', 'ip')->active()
            ->whereDoesntHave('vitals', fn ($q) => $q->where('recorded_at', '>=', now()->subHours(4)))
            ->count();
        $todayNotes = NursingNote::whereDate('noted_at', today())->count();
        $pendingHandovers = ShiftHandover::where('incoming_nurse_id', $userId)->where('status', 'pending_acceptance')->count();
        $todayHandovers = ShiftHandover::whereDate('handover_date', today())
            ->where(fn ($q) => $q->where('outgoing_nurse_id', $userId)->orWhere('incoming_nurse_id', $userId))
            ->count();

        $todayAppointments = \App\Models\Appointment::with(['patient:id,name,mrn', 'doctor:id,name'])
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'patient_name' => $a->patient?->name,
                'doctor_name' => $a->doctor?->name,
                'time' => substr($a->appointment_time, 0, 5),
                'type' => $a->type,
            ]);

        $recentPatients = Patient::where('patient_type', 'ip')->active()->latest()->take(5)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'mrn' => $p->mrn,
                'status' => $p->status,
                'photo_url' => $p->photo_url,
            ]);

        return response()->json([
            'stats' => [
                'ip_patients' => $ipPatients,
                'overdue_rounds' => $overdueRounds,
                'food_logs_today' => $todayFoodLogs,
                'fluid_logs_today' => $todayFluidLogs,
                'pending_medications' => $pendingMeds,
                'needs_vitals_check' => $needsVitals,
                'nursing_notes_today' => $todayNotes,
                'pending_handovers' => $pendingHandovers,
                'todays_handovers' => $todayHandovers,
            ],
            'today_appointments' => $todayAppointments,
            'recent_patients' => $recentPatients,
        ]);
    }

    /**
     * Mirrors StaffDashboardController — the "staff" role's own dashboard
     * (narrower than nurse's: assigned wards + own food/fluid logs only,
     * no eMAR/vitals/handover stats since staff can't access those).
     */
    public function staffDashboard(Request $request)
    {
        $staff = $this->authNurseOrStaff($request);
        $userId = $staff->id;

        $myRounds = RoundAssignment::where('staff_id', $userId)->where('status', 'active')->count();
        $overdueRounds = RoundAssignment::where('staff_id', $userId)->where('status', 'active')->where('next_round_at', '<=', now())->count();
        $myFoodLogs = FoodLog::whereDate('log_date', today())->where('logged_by', $userId)->count();
        $myFluidLogs = FluidLog::whereDate('log_date', today())->where('logged_by', $userId)->count();

        $recentPatients = Patient::where('patient_type', 'ip')->active()->latest()->take(5)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'mrn' => $p->mrn,
                'status' => $p->status,
                'photo_url' => $p->photo_url,
            ]);

        return response()->json([
            'stats' => [
                'my_assigned_wards' => $myRounds,
                'overdue_rounds' => $overdueRounds,
                'my_food_logs_today' => $myFoodLogs,
                'my_fluid_logs_today' => $myFluidLogs,
            ],
            'today_appointments' => [],
            'recent_patients' => $recentPatients,
        ]);
    }

    // ============================================================
    // Patients
    // ============================================================

    public function patients(Request $request)
    {
        $this->authNurseOrStaff($request);

        $query = Patient::with(['currentAdmission.ward', 'currentAdmission.bed'])
            ->select(['id', 'mrn', 'name', 'phone', 'gender', 'dob', 'blood_group', 'status', 'patient_type', 'photo'])
            ->when($request->filled('type'), fn ($q) => $q->where('patient_type', $request->type));

        $query = $this->patientSearch($query, $request->q);

        $patients = $query->latest()->take(100)->get()->map(fn (Patient $p) => [
            'id' => $p->id,
            'mrn' => $p->mrn,
            'name' => $p->name,
            'phone' => $p->phone,
            'gender' => $p->gender,
            'dob' => $p->dob,
            'blood_group' => $p->blood_group,
            'status' => $p->status,
            'patient_type' => $p->patient_type,
            'photo_url' => $p->photo_url,
            'ward_name' => $p->currentAdmission?->ward?->name,
            'bed_no' => $p->currentAdmission?->bed?->bed_no,
        ]);

        return response()->json(['patients' => $patients]);
    }

    public function patientDetail(Request $request, Patient $patient)
    {
        $this->authNurseOrStaff($request);
        $patient->load(['currentAdmission.ward', 'currentAdmission.bed', 'currentAdmission.doctor']);

        return response()->json([
            'id' => $patient->id,
            'mrn' => $patient->mrn,
            'name' => $patient->name,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'dob' => $patient->dob,
            'gender' => $patient->gender,
            'blood_group' => $patient->blood_group,
            'address' => $patient->address,
            'emergency_contact' => $patient->emergency_contact,
            'emergency_contact_name' => $patient->emergency_contact_name,
            'status' => $patient->status,
            'patient_type' => $patient->patient_type,
            'photo_url' => $patient->photo_url,
            'admission' => $patient->currentAdmission ? [
                'id' => $patient->currentAdmission->id,
                'admission_no' => $patient->currentAdmission->admission_no,
                'ward_name' => $patient->currentAdmission->ward?->name,
                'bed_no' => $patient->currentAdmission->bed?->bed_no,
                'doctor_name' => $patient->currentAdmission->doctor?->name,
                'admission_date' => $patient->currentAdmission->admission_date,
            ] : null,
        ]);
    }

    // ============================================================
    // Ward / Admissions
    // ============================================================

    public function wards(Request $request)
    {
        $this->authWardTeam($request);

        $wards = Ward::where('status', 'active')->orderBy('name')->get()->map(fn (Ward $w) => [
            'id' => $w->id,
            'ward_no' => $w->ward_no,
            'name' => $w->name,
            'type' => $w->type,
            'floor' => $w->floor,
            'building' => $w->building,
            'total_beds' => $w->total_beds,
            'available_beds' => $w->available_beds,
            'charge_per_day' => (float) $w->charge_per_day,
        ]);

        return response()->json(['wards' => $wards]);
    }

    public function wardDetail(Request $request, Ward $ward)
    {
        $this->authWardTeam($request);
        $ward->load(['beds.currentAdmission.patient']);

        return response()->json([
            'id' => $ward->id,
            'name' => $ward->name,
            'type' => $ward->type,
            'total_beds' => $ward->total_beds,
            'available_beds' => $ward->available_beds,
            'beds' => $ward->beds->map(fn (Bed $b) => [
                'id' => $b->id,
                'bed_no' => $b->bed_no,
                'type' => $b->type,
                'status' => $b->status,
                'patient_name' => $b->currentAdmission?->patient?->name,
                'patient_id' => $b->currentAdmission?->patient?->id,
            ]),
        ]);
    }

    public function availableBeds(Request $request)
    {
        $this->authWardTeam($request);
        $request->validate(['ward_id' => ['required', 'exists:wards,id']]);

        $beds = Bed::where('ward_id', $request->ward_id)->where('status', 'available')->get()
            ->map(fn (Bed $b) => ['id' => $b->id, 'bed_no' => $b->bed_no, 'type' => $b->type]);

        return response()->json(['beds' => $beds]);
    }

    public function admissions(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $query = Admission::with(['patient:id,name,mrn,photo', 'ward:id,name', 'bed:id,bed_no', 'doctor:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('admission_date');

        $admissions = $query->take(100)->get()->map(fn (Admission $a) => [
            'id' => $a->id,
            'admission_no' => $a->admission_no,
            'patient_id' => $a->patient_id,
            'patient_name' => $a->patient?->name,
            'patient_mrn' => $a->patient?->mrn,
            'patient_photo_url' => $a->patient?->photo_url,
            'ward_name' => $a->ward?->name,
            'bed_no' => $a->bed?->bed_no,
            'doctor_name' => $a->doctor?->name,
            'admission_date' => $a->admission_date,
            'discharge_date' => $a->discharge_date,
            'status' => $a->status,
            'total_charges' => (float) $a->total_charges,
        ]);

        return response()->json(['admissions' => $admissions]);
    }

    public function admit(Request $request)
    {
        $this->authWardTeam($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'ward_id' => ['required', 'exists:wards,id'],
            'bed_id' => ['required', 'exists:beds,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'admission_date' => ['required', 'date'],
            'admission_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $admission = DB::transaction(function () use ($validated) {
                $bed = Bed::lockForUpdate()->find($validated['bed_id']);

                if ($bed->status !== 'available') {
                    throw new \RuntimeException('Bed is not available!');
                }

                $alreadyAdmitted = Admission::where('patient_id', $validated['patient_id'])->where('status', 'admitted')->exists();
                if ($alreadyAdmitted) {
                    throw new \RuntimeException('This patient already has an active admission. Discharge or transfer them first.');
                }

                return Admission::create($validated);
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['bed_id' => [$e->getMessage()]]);
        }

        return response()->json(['message' => 'Patient admitted successfully!', 'admission_id' => $admission->id], 201);
    }

    public function dischargeAdmission(Request $request, Admission $admission)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'discharge_date' => ['required', 'date'],
            'discharge_notes' => ['nullable', 'string', 'max:1000'],
            'total_charges' => ['required', 'numeric', 'min:0'],
        ]);

        $admission->update([
            'discharge_date' => $request->discharge_date,
            'discharge_notes' => $request->discharge_notes,
            'total_charges' => $request->total_charges,
            'status' => 'discharged',
        ]);

        return response()->json(['message' => 'Patient discharged successfully!']);
    }

    public function transferAdmission(Request $request, Admission $admission)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'new_ward_id' => ['required', 'exists:wards,id'],
            'new_bed_id' => ['required', 'exists:beds,id'],
            'transfer_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $newBed = Bed::find($request->new_bed_id);
        if ($newBed->status !== 'available') {
            throw ValidationException::withMessages(['new_bed_id' => ['Selected bed is not available!']]);
        }

        $oldWard = $admission->ward;
        Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
        Ward::where('id', $admission->ward_id)->increment('available_beds');
        Bed::where('id', $request->new_bed_id)->update(['status' => 'occupied']);
        Ward::where('id', $request->new_ward_id)->decrement('available_beds');

        $admission->update([
            'ward_id' => $request->new_ward_id,
            'bed_id' => $request->new_bed_id,
            'discharge_notes' => ($admission->discharge_notes ? $admission->discharge_notes . ' | ' : '') .
                'Transferred from ' . $oldWard->name . ' to new ward on ' . now()->format('d M Y') .
                ($request->transfer_reason ? ' — Reason: ' . $request->transfer_reason : ''),
        ]);

        return response()->json(['message' => 'Patient transferred successfully!']);
    }

    public function admissionBalance(Request $request, Admission $admission)
    {
        $this->authNurseOrDoctor($request);

        $invoiceTotal = (float) $admission->invoices()->sum('total');
        $paymentsMade = (float) $admission->invoices()->join('payments', 'payments.invoice_id', '=', 'invoices.id')->sum('payments.amount');
        $depositBalance = \App\Models\IpdDeposit::getBalanceForAdmission($admission->id);
        $outstanding = max(0, $invoiceTotal - $paymentsMade - $depositBalance);

        return response()->json([
            'invoice_total' => $invoiceTotal,
            'payments_made' => $paymentsMade,
            'deposit_balance' => $depositBalance,
            'outstanding' => $outstanding,
        ]);
    }

    // ============================================================
    // eMAR
    // ============================================================

    public function emarList(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $query = MedicationAdministration::with(['patient:id,name,mrn', 'nurse:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('admitted_only'), fn ($q) => $q->whereHas('patient.currentAdmission'))
            ->latest();

        $items = $query->take(150)->get()->map(fn (MedicationAdministration $m) => [
            'id' => $m->id,
            'patient_id' => $m->patient_id,
            'patient_name' => $m->patient?->name,
            'patient_mrn' => $m->patient?->mrn,
            'drug_name' => $m->drug_name,
            'dosage' => $m->dosage,
            'route' => $m->route,
            'frequency' => $m->frequency,
            'scheduled_time' => $m->scheduled_time ? substr($m->scheduled_time, 0, 5) : null,
            'administered_at' => $m->administered_at,
            'status' => $m->status,
            'notes' => $m->notes,
            'is_prn' => (bool) $m->is_prn,
            'is_controlled' => (bool) $m->is_controlled,
            'nurse_name' => $m->nurse?->name,
        ]);

        return response()->json(['medications' => $items]);
    }

    public function emarStore(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'drug_name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:50'],
            'frequency' => ['nullable', 'string', 'max:50'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_prn' => ['nullable', 'boolean'],
            'is_controlled' => ['nullable', 'boolean'],
        ]);

        $mar = MedicationAdministration::create([
            'patient_id' => $request->patient_id,
            'admission_id' => Patient::find($request->patient_id)?->currentAdmission?->id,
            'drug_name' => $request->drug_name,
            'dosage' => $request->dosage,
            'route' => $request->route,
            'frequency' => $request->frequency,
            'scheduled_time' => $request->scheduled_time,
            'status' => 'scheduled',
            'notes' => $request->notes,
            'is_prn' => $request->boolean('is_prn'),
            'is_controlled' => $request->boolean('is_controlled'),
        ]);

        return response()->json(['message' => 'Medication order added!', 'id' => $mar->id], 201);
    }

    public function emarAdminister(Request $request, MedicationAdministration $mar)
    {
        $nurse = $this->authNurseOrDoctor($request);

        $request->validate([
            'status' => ['required', 'in:administered,missed,refused,held'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $mar->update([
            'status' => $request->status,
            'nurse_id' => $nurse->id,
            'administered_at' => $request->status === 'administered' ? now() : null,
            'notes' => $request->notes ?? $mar->notes,
        ]);

        return response()->json(['message' => 'Medication status updated.', 'status' => $mar->status]);
    }

    public function emarDestroy(Request $request, MedicationAdministration $mar)
    {
        $this->authNurseOrDoctor($request);
        $mar->delete();

        return response()->json(['message' => 'Medication order deleted.']);
    }

    // ============================================================
    // Vitals
    // ============================================================

    public function vitalsList(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $query = Vital::with(['patient:id,name,mrn', 'recordedBy:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('admitted_only'), fn ($q) => $q->whereHas('patient.currentAdmission'))
            ->latest('recorded_at');

        $items = $query->take(150)->get()->map(fn (Vital $v) => $this->vitalPayload($v));

        return response()->json(['vitals' => $items]);
    }

    private function vitalPayload(Vital $v): array
    {
        return [
            'id' => $v->id,
            'patient_id' => $v->patient_id,
            'patient_name' => $v->patient?->name,
            'patient_mrn' => $v->patient?->mrn,
            'blood_pressure' => $v->blood_pressure,
            'temperature' => $v->temperature,
            'pulse_rate' => $v->pulse_rate,
            'respiratory_rate' => $v->respiratory_rate,
            'spo2' => $v->spo2,
            'weight' => $v->weight,
            'height' => $v->height,
            'news_score' => $v->news_score,
            'notes' => $v->notes,
            'recorded_at' => $v->recorded_at,
            'recorded_by_name' => $v->recordedBy?->name,
        ];
    }

    public function vitalsStore(Request $request)
    {
        $nurse = $this->authNurseOrDoctor($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'temperature' => ['nullable', 'numeric', 'min:20', 'max:45'],
            'pulse_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vital = new Vital($request->only([
            'patient_id', 'blood_pressure', 'temperature', 'pulse_rate',
            'respiratory_rate', 'spo2', 'weight', 'height', 'notes',
        ]));
        $vital->admission_id = Patient::find($request->patient_id)?->currentAdmission?->id;
        $vital->recorded_by = $nurse->id;
        $vital->recorded_at = now();
        $vital->news_score = $vital->newsScore();
        $vital->save();

        return response()->json(['message' => 'Vitals recorded successfully!', 'vital' => $this->vitalPayload($vital)], 201);
    }

    public function vitalsHistory(Request $request, Patient $patient)
    {
        $this->authNurseOrDoctor($request);

        $vitals = Vital::where('patient_id', $patient->id)->orderByDesc('recorded_at')->take(50)->get()
            ->map(fn (Vital $v) => $this->vitalPayload($v));

        return response()->json(['vitals' => $vitals]);
    }

    public function vitalsDestroy(Request $request, Vital $vital)
    {
        $this->authNurseOrDoctor($request);
        $vital->delete();

        return response()->json(['message' => 'Vital record deleted.']);
    }

    // ============================================================
    // Nursing Notes
    // ============================================================

    public function nursingNotesList(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $query = NursingNote::with(['patient:id,name,mrn', 'writtenBy:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->boolean('admitted_only'), fn ($q) => $q->whereHas('patient.currentAdmission'))
            ->latest('noted_at');

        $items = $query->take(150)->get()->map(fn (NursingNote $n) => [
            'id' => $n->id,
            'patient_id' => $n->patient_id,
            'patient_name' => $n->patient?->name,
            'patient_mrn' => $n->patient?->mrn,
            'note_type' => $n->note_type,
            'shift' => $n->shift,
            'note' => $n->note,
            'priority' => $n->priority,
            'noted_at' => $n->noted_at,
            'written_by_name' => $n->writtenBy?->name,
        ]);

        return response()->json(['notes' => $items]);
    }

    public function nursingNoteStore(Request $request)
    {
        $nurse = $this->authNurseOrDoctor($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'note_type' => ['required', 'in:nursing,progress,handover,assessment,discharge_summary'],
            'shift' => ['nullable', 'in:morning,afternoon,night'],
            'note' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:routine,urgent,critical'],
        ]);

        $note = NursingNote::create([
            'patient_id' => $request->patient_id,
            'written_by' => $nurse->id,
            'admission_id' => Patient::find($request->patient_id)?->currentAdmission?->id,
            'note_type' => $request->note_type,
            'shift' => $request->shift,
            'note' => $request->note,
            'priority' => $request->priority,
            'noted_at' => now(),
        ]);

        return response()->json(['message' => 'Note added successfully!', 'id' => $note->id], 201);
    }

    public function nursingNoteUpdate(Request $request, NursingNote $note)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'note_type' => ['required', 'in:nursing,progress,handover,assessment,discharge_summary'],
            'shift' => ['nullable', 'in:morning,afternoon,night'],
            'note' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:routine,urgent,critical'],
        ]);

        $note->update($request->only(['note_type', 'shift', 'note', 'priority']));

        return response()->json(['message' => 'Note updated successfully!']);
    }

    public function nursingNoteDestroy(Request $request, NursingNote $note)
    {
        $this->authNurseOrDoctor($request);
        $note->delete();

        return response()->json(['message' => 'Note deleted.']);
    }

    // ============================================================
    // Shift Handover
    // ============================================================

    private function handoverScoped(Request $request)
    {
        return ShiftHandover::where(fn ($q) => $q->where('outgoing_nurse_id', $request->user()->id)->orWhere('incoming_nurse_id', $request->user()->id));
    }

    public function handoversList(Request $request)
    {
        $this->authNurse($request);

        $handovers = $this->handoverScoped($request)
            ->with(['ward:id,name', 'outgoingNurse:id,name', 'incomingNurse:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('ward_id'), fn ($q) => $q->where('ward_id', $request->ward_id))
            ->latest('handover_date')
            ->take(100)
            ->get()
            ->map(fn (ShiftHandover $h) => $this->handoverSummary($h));

        return response()->json(['handovers' => $handovers]);
    }

    private function handoverSummary(ShiftHandover $h): array
    {
        return [
            'id' => $h->id,
            'handover_no' => $h->handover_no,
            'ward_name' => $h->ward?->name,
            'current_shift' => $h->current_shift,
            'next_shift' => $h->next_shift,
            'handover_date' => $h->handover_date,
            'status' => $h->status,
            'outgoing_nurse_name' => $h->outgoingNurse?->name,
            'incoming_nurse_name' => $h->incomingNurse?->name,
        ];
    }

    public function handoverWardPatients(Request $request)
    {
        $this->authNurse($request);
        $request->validate(['ward_id' => ['required', 'exists:wards,id']]);

        $admissions = Admission::where('ward_id', $request->ward_id)->where('status', 'admitted')->get();

        return response()->json(['patients' => $admissions->map(fn ($a) => $this->handoverCardPayload($a->patient_id, $a->id))->values()]);
    }

    private function suggestedMedicationStatus(int $patientId): string
    {
        $today = MedicationAdministration::where('patient_id', $patientId)->whereDate('created_at', today());
        if ((clone $today)->where('status', 'missed')->exists()) return 'missed';
        if ((clone $today)->where('status', 'scheduled')->exists()) return 'pending';

        return 'completed';
    }

    private function handoverCardPayload(int $patientId, ?int $admissionId, array $saved = []): array
    {
        $patient = Patient::find($patientId);
        $admission = $admissionId ? Admission::with('bed')->find($admissionId) : null;
        $latestVitals = $patient?->vitals()->latest('recorded_at')->first();

        return [
            'patient_id' => $patientId,
            'admission_id' => $admissionId,
            'name' => $patient->name ?? 'Unknown',
            'mrn' => $patient->mrn ?? 'N/A',
            'photo_url' => $patient->photo_url ?? null,
            'bed_no' => $admission->bed->bed_no ?? 'N/A',
            'admission_date' => $admission?->admission_date,
            'vitals' => $latestVitals ? [
                'blood_pressure' => $latestVitals->blood_pressure,
                'pulse_rate' => $latestVitals->pulse_rate,
                'temperature' => $latestVitals->temperature,
                'respiratory_rate' => $latestVitals->respiratory_rate,
                'spo2' => $latestVitals->spo2,
                'recorded_at' => $latestVitals->recorded_at,
            ] : null,
            'suggested_med_status' => $saved['medication_status'] ?? $this->suggestedMedicationStatus($patientId),
            'saved' => [
                'condition' => $saved['condition'] ?? 'stable',
                'medication_status' => $saved['medication_status'] ?? null,
                'iv_fluid_status' => $saved['iv_fluid_status'] ?? null,
                'iv_next_change_time' => $saved['iv_next_change_time'] ?? null,
                'doctor_instructions' => $saved['doctor_instructions'] ?? null,
                'pending_procedures' => $saved['pending_procedures'] ?? null,
                'pending_lab_reports' => $saved['pending_lab_reports'] ?? null,
                'pending_imaging' => $saved['pending_imaging'] ?? null,
                'special_notes' => $saved['special_notes'] ?? null,
                'is_high_priority' => (bool) ($saved['is_high_priority'] ?? false),
            ],
        ];
    }

    public function handoverCreateMeta(Request $request)
    {
        $nurse = $this->authNurse($request);

        return response()->json([
            'wards' => Ward::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'nurses' => User::role('nurse')->where('id', '!=', $nurse->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function handoverStore(Request $request)
    {
        $nurse = $this->authNurse($request);
        abort_unless($nurse->can('create handovers'), 403);

        $data = $request->validate([
            'ward_id' => ['required', 'exists:wards,id'],
            'current_shift' => ['required', 'in:morning,evening,night'],
            'next_shift' => ['required', 'in:morning,evening,night'],
            'incoming_nurse_id' => ['required', 'exists:users,id', 'different:outgoing_nurse_id'],
            'handover_date' => ['required', 'date'],
            'shift_summary' => ['nullable', 'string', 'max:5000'],
            'patients' => ['required', 'array', 'min:1'],
            'patients.*.patient_id' => ['required', 'exists:patients,id'],
            'patients.*.admission_id' => ['nullable', 'exists:admissions,id'],
            'patients.*.condition' => ['required', 'in:stable,improving,critical,observation'],
            'patients.*.medication_status' => ['required', 'in:completed,pending,missed'],
            'patients.*.iv_fluid_status' => ['nullable', 'in:running,completed'],
            'patients.*.iv_next_change_time' => ['nullable', 'date'],
            'patients.*.doctor_instructions' => ['nullable', 'string', 'max:2000'],
            'patients.*.pending_procedures' => ['nullable', 'string', 'max:2000'],
            'patients.*.pending_lab_reports' => ['nullable', 'string', 'max:2000'],
            'patients.*.pending_imaging' => ['nullable', 'string', 'max:2000'],
            'patients.*.special_notes' => ['nullable', 'string', 'max:2000'],
            'patients.*.is_high_priority' => ['nullable', 'boolean'],
            'tasks' => ['nullable', 'array'],
            'tasks.*.patient_id' => ['nullable', 'exists:patients,id'],
            'tasks.*.task_description' => ['required_with:tasks', 'string', 'max:500'],
        ]);

        $handover = DB::transaction(function () use ($data, $nurse) {
            $handover = ShiftHandover::create([
                'ward_id' => $data['ward_id'],
                'current_shift' => $data['current_shift'],
                'next_shift' => $data['next_shift'],
                'incoming_nurse_id' => $data['incoming_nurse_id'],
                'handover_date' => $data['handover_date'],
                'shift_summary' => $data['shift_summary'] ?? null,
                'outgoing_nurse_id' => $nurse->id,
                'status' => 'draft',
            ]);

            foreach ($data['patients'] as $card) {
                ShiftHandoverPatient::create([
                    'shift_handover_id' => $handover->id,
                    'patient_id' => $card['patient_id'],
                    'admission_id' => $card['admission_id'] ?? null,
                    'condition' => $card['condition'],
                    'medication_status' => $card['medication_status'],
                    'iv_fluid_status' => $card['iv_fluid_status'] ?? null,
                    'iv_next_change_time' => $card['iv_next_change_time'] ?? null,
                    'doctor_instructions' => $card['doctor_instructions'] ?? null,
                    'pending_procedures' => $card['pending_procedures'] ?? null,
                    'pending_lab_reports' => $card['pending_lab_reports'] ?? null,
                    'pending_imaging' => $card['pending_imaging'] ?? null,
                    'special_notes' => $card['special_notes'] ?? null,
                    'is_high_priority' => (bool) ($card['is_high_priority'] ?? false),
                ]);
            }

            foreach ($data['tasks'] ?? [] as $task) {
                if (empty($task['task_description'])) continue;
                ShiftHandoverTask::create([
                    'shift_handover_id' => $handover->id,
                    'patient_id' => $task['patient_id'] ?: null,
                    'task_description' => $task['task_description'],
                ]);
            }

            return $handover;
        });

        return response()->json(['message' => 'Handover saved as draft.', 'id' => $handover->id], 201);
    }

    public function handoverDetail(Request $request, ShiftHandover $shiftHandover)
    {
        $nurse = $this->authNurse($request);
        abort_unless(in_array($nurse->id, [$shiftHandover->outgoing_nurse_id, $shiftHandover->incoming_nurse_id]), 403);

        $shiftHandover->load(['ward', 'outgoingNurse', 'incomingNurse', 'patients.patient', 'patients.admission.bed', 'tasks.patient']);

        return response()->json([
            ...$this->handoverSummary($shiftHandover),
            'shift_summary' => $shiftHandover->shift_summary,
            'rejection_reason' => $shiftHandover->rejection_reason,
            'is_editable' => $shiftHandover->isEditable(),
            'patients' => $shiftHandover->patients->map(fn (ShiftHandoverPatient $p) => [
                'patient_id' => $p->patient_id,
                'admission_id' => $p->admission_id,
                'name' => $p->patient?->name,
                'mrn' => $p->patient?->mrn,
                'bed_no' => $p->admission?->bed?->bed_no,
                'condition' => $p->condition,
                'medication_status' => $p->medication_status,
                'iv_fluid_status' => $p->iv_fluid_status,
                'iv_next_change_time' => $p->iv_next_change_time,
                'doctor_instructions' => $p->doctor_instructions,
                'pending_procedures' => $p->pending_procedures,
                'pending_lab_reports' => $p->pending_lab_reports,
                'pending_imaging' => $p->pending_imaging,
                'special_notes' => $p->special_notes,
                'is_high_priority' => (bool) $p->is_high_priority,
            ]),
            'tasks' => $shiftHandover->tasks->map(fn (ShiftHandoverTask $t) => [
                'patient_name' => $t->patient?->name,
                'task_description' => $t->task_description,
                'is_completed' => (bool) $t->is_completed,
            ]),
        ]);
    }

    public function handoverSubmit(Request $request, ShiftHandover $shiftHandover)
    {
        $nurse = $this->authNurse($request);
        abort_unless($shiftHandover->isEditable() && $nurse->id === $shiftHandover->outgoing_nurse_id, 403);
        abort_unless($nurse->can('create handovers'), 403);

        $shiftHandover->update(['status' => 'pending_acceptance', 'submitted_at' => now()]);

        return response()->json(['message' => 'Handover submitted — awaiting acceptance by the incoming nurse.']);
    }

    public function handoverAccept(Request $request, ShiftHandover $shiftHandover)
    {
        $nurse = $this->authNurse($request);
        abort_unless($nurse->can('accept handovers'), 403);
        abort_unless($shiftHandover->isPending() && $nurse->id === $shiftHandover->incoming_nurse_id, 403);

        $shiftHandover->update(['status' => 'accepted', 'accepted_at' => now()]);

        return response()->json(['message' => 'Handover accepted.']);
    }

    public function handoverReject(Request $request, ShiftHandover $shiftHandover)
    {
        $nurse = $this->authNurse($request);
        abort_unless($nurse->can('accept handovers'), 403);
        abort_unless($shiftHandover->isPending() && $nurse->id === $shiftHandover->incoming_nurse_id, 403);

        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $shiftHandover->update(['status' => 'rejected', 'rejected_at' => now(), 'rejection_reason' => $request->reason]);

        return response()->json(['message' => 'Handover rejected.']);
    }

    public function handoverAlertCount(Request $request)
    {
        $nurse = $this->authNurse($request);
        $count = ShiftHandover::where('incoming_nurse_id', $nurse->id)->where('status', 'pending_acceptance')->count();

        return response()->json(['count' => $count]);
    }

    public function handoverDestroy(Request $request, ShiftHandover $shiftHandover)
    {
        $nurse = $this->authNurse($request);
        abort_unless($shiftHandover->isEditable() && $nurse->id === $shiftHandover->outgoing_nurse_id, 403);

        $shiftHandover->delete();

        return response()->json(['message' => 'Handover deleted.']);
    }

    // ============================================================
    // Operation Theatre
    // ============================================================

    public function otList(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $items = OtSchedule::with(['patient:id,name,mrn', 'surgeon:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->latest('scheduled_start')
            ->take(100)
            ->get()
            ->map(fn (OtSchedule $o) => $this->otPayload($o));

        return response()->json(['ot_schedules' => $items]);
    }

    private function otPayload(OtSchedule $o): array
    {
        return [
            'id' => $o->id,
            'ot_no' => $o->ot_no,
            'patient_id' => $o->patient_id,
            'patient_name' => $o->patient?->name,
            'patient_mrn' => $o->patient?->mrn,
            'surgeon_name' => $o->surgeon?->name,
            'theatre_no' => $o->theatre_no,
            'procedure_name' => $o->procedure_name,
            'anaesthesia_type' => $o->anaesthesia_type,
            'priority' => $o->priority,
            'status' => $o->status,
            'scheduled_start' => $o->scheduled_start,
            'scheduled_end' => $o->scheduled_end,
            'actual_start' => $o->actual_start,
            'actual_end' => $o->actual_end,
        ];
    }

    public function otStore(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'surgeon_id' => ['nullable', 'exists:doctors,id'],
            'anaesthetist_id' => ['nullable', 'exists:doctors,id'],
            'scrub_nurse_id' => ['nullable', 'exists:users,id'],
            'theatre_no' => ['nullable', 'string', 'max:50'],
            'procedure_name' => ['required', 'string', 'max:255'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
            'anaesthesia_type' => ['nullable', 'in:general,spinal,epidural,local,sedation'],
            'priority' => ['required', 'in:elective,urgent,emergency'],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after:scheduled_start'],
            'pre_op_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ot = OtSchedule::create([
            ...$request->only([
                'patient_id', 'surgeon_id', 'anaesthetist_id', 'scrub_nurse_id', 'theatre_no',
                'procedure_name', 'icd10_code', 'anaesthesia_type', 'priority', 'scheduled_start',
                'scheduled_end', 'pre_op_notes',
            ]),
            'status' => 'scheduled',
        ]);

        return response()->json(['message' => 'OT scheduled successfully!', 'id' => $ot->id], 201);
    }

    public function otDetail(Request $request, OtSchedule $ot)
    {
        $this->authNurseOrDoctor($request);
        $ot->load(['patient', 'surgeon', 'anaesthetist', 'scrubNurse']);

        return response()->json([...$this->otPayload($ot), 'pre_op_notes' => $ot->pre_op_notes, 'post_op_notes' => $ot->post_op_notes, 'complications' => $ot->complications]);
    }

    public function otStart(Request $request, OtSchedule $ot)
    {
        $this->authNurseOrDoctor($request);
        $ot->update(['status' => 'in_progress', 'actual_start' => now()]);

        return response()->json(['message' => 'Surgery started.']);
    }

    public function otComplete(Request $request, OtSchedule $ot)
    {
        $this->authNurseOrDoctor($request);
        $request->validate(['post_op_notes' => ['nullable', 'string', 'max:2000'], 'complications' => ['nullable', 'string', 'max:2000']]);

        $ot->update([
            'status' => 'completed',
            'actual_end' => now(),
            'post_op_notes' => $request->post_op_notes,
            'complications' => $request->complications,
        ]);

        return response()->json(['message' => 'Surgery marked complete.']);
    }

    public function otDestroy(Request $request, OtSchedule $ot)
    {
        $this->authNurseOrDoctor($request);
        $ot->update(['status' => 'cancelled']);

        return response()->json(['message' => 'OT schedule cancelled.']);
    }

    // ============================================================
    // ICU Charting
    // ============================================================

    public function icuList(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $items = IcuChart::with(['patient:id,name,mrn', 'recordedBy:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->latest('charted_at')
            ->take(100)
            ->get()
            ->map(fn (IcuChart $c) => $this->icuPayload($c));

        return response()->json(['icu_charts' => $items]);
    }

    private function icuPayload(IcuChart $c): array
    {
        return [
            'id' => $c->id,
            'patient_id' => $c->patient_id,
            'patient_name' => $c->patient?->name,
            'patient_mrn' => $c->patient?->mrn,
            'ventilator_mode' => $c->ventilator_mode,
            'fio2' => $c->fio2,
            'peep' => $c->peep,
            'tidal_volume' => $c->tidal_volume,
            'respiratory_rate_set' => $c->respiratory_rate_set,
            'arterial_bp' => $c->arterial_bp,
            'cvp' => $c->cvp,
            'heart_rate' => $c->heart_rate,
            'rhythm' => $c->rhythm,
            'lines_drains' => $c->lines_drains,
            'gcs_eye' => $c->gcs_eye,
            'gcs_verbal' => $c->gcs_verbal,
            'gcs_motor' => $c->gcs_motor,
            'gcs_total' => $c->gcs_total,
            'intake_oral' => $c->intake_oral,
            'intake_iv' => $c->intake_iv,
            'output_urine' => $c->output_urine,
            'output_drain' => $c->output_drain,
            'fluid_balance' => $c->fluid_balance,
            'notes' => $c->notes,
            'charted_at' => $c->charted_at,
            'recorded_by_name' => $c->recordedBy?->name,
        ];
    }

    public function icuStore(Request $request)
    {
        $nurse = $this->authNurseOrDoctor($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'ventilator_mode' => ['nullable', 'string', 'max:50'],
            'fio2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'peep' => ['nullable', 'integer', 'min:0', 'max:50'],
            'tidal_volume' => ['nullable', 'integer', 'min:0'],
            'respiratory_rate_set' => ['nullable', 'integer', 'min:0'],
            'arterial_bp' => ['nullable', 'string', 'max:20'],
            'cvp' => ['nullable', 'integer'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'rhythm' => ['nullable', 'string', 'max:100'],
            'lines_drains' => ['nullable', 'string', 'max:1000'],
            'gcs_eye' => ['nullable', 'integer', 'min:1', 'max:4'],
            'gcs_verbal' => ['nullable', 'integer', 'min:1', 'max:5'],
            'gcs_motor' => ['nullable', 'integer', 'min:1', 'max:6'],
            'intake_oral' => ['nullable', 'numeric', 'min:0'],
            'intake_iv' => ['nullable', 'numeric', 'min:0'],
            'output_urine' => ['nullable', 'numeric', 'min:0'],
            'output_drain' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $chart = new IcuChart($request->only([
            'patient_id', 'ventilator_mode', 'fio2', 'peep', 'tidal_volume', 'respiratory_rate_set',
            'arterial_bp', 'cvp', 'heart_rate', 'rhythm', 'lines_drains',
            'gcs_eye', 'gcs_verbal', 'gcs_motor',
            'intake_oral', 'intake_iv', 'output_urine', 'output_drain', 'notes',
        ]));
        $chart->admission_id = Patient::find($request->patient_id)?->currentAdmission?->id;
        $chart->recorded_by = $nurse->id;
        $chart->charted_at = now();
        $chart->save();

        return response()->json(['message' => 'ICU chart recorded successfully!', 'chart' => $this->icuPayload($chart)], 201);
    }

    public function icuDetail(Request $request, IcuChart $chart)
    {
        $this->authNurseOrDoctor($request);
        $chart->load(['patient', 'recordedBy']);

        return response()->json($this->icuPayload($chart));
    }

    public function icuDestroy(Request $request, IcuChart $chart)
    {
        $this->authNurseOrDoctor($request);
        $chart->delete();

        return response()->json(['message' => 'ICU chart deleted.']);
    }

    // ============================================================
    // Blood Bank
    // ============================================================

    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function bloodBankIndex(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $totalUnits = BloodUnit::count();
        $byGroup = collect(self::BLOOD_GROUPS)->mapWithKeys(fn ($g) => [$g => BloodUnit::where('blood_group', $g)->where('status', 'available')->count()]);
        $nearExpiryCount = BloodUnit::where('status', 'available')->whereDate('expiry_date', '<=', now()->addDays(7))->whereDate('expiry_date', '>=', now())->count();

        $units = BloodUnit::with('reservedForPatient:id,name')->latest()->take(100)->get()->map(fn (BloodUnit $u) => [
            'id' => $u->id,
            'unit_no' => $u->unit_no,
            'blood_group' => $u->blood_group,
            'component' => $u->component,
            'donor_name' => $u->donor_name,
            'collection_date' => $u->collection_date,
            'expiry_date' => $u->expiry_date,
            'status' => $u->status,
            'is_near_expiry' => $u->isNearExpiry(),
            'reserved_for_name' => $u->reservedForPatient?->name,
            'notes' => $u->notes,
        ]);

        return response()->json([
            'total_units' => $totalUnits,
            'by_group' => $byGroup,
            'near_expiry_count' => $nearExpiryCount,
            'units' => $units,
        ]);
    }

    public function bloodBankStore(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'blood_group' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'component' => ['required', 'in:whole_blood,packed_rbc,ffp,platelets,cryoprecipitate'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'collection_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after:collection_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $unit = BloodUnit::create([
            ...$request->only(['blood_group', 'component', 'donor_name', 'collection_date', 'expiry_date', 'notes']),
            'status' => 'available',
        ]);

        return response()->json(['message' => 'Blood unit added to inventory!', 'id' => $unit->id], 201);
    }

    public function bloodBankTransfusions(Request $request)
    {
        $this->authNurseOrDoctor($request);

        $items = BloodTransfusion::with(['bloodUnit:id,unit_no,blood_group', 'administeredBy:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->latest('started_at')
            ->take(100)
            ->get()
            ->map(fn (BloodTransfusion $t) => [
                'id' => $t->id,
                'transfusion_no' => $t->transfusion_no,
                'patient_id' => $t->patient_id,
                'blood_group' => $t->bloodUnit?->blood_group,
                'unit_no' => $t->bloodUnit?->unit_no,
                'indication' => $t->indication,
                'status' => $t->status,
                'administered_by_name' => $t->administeredBy?->name,
                'started_at' => $t->started_at,
                'completed_at' => $t->completed_at,
            ]);

        return response()->json(['transfusions' => $items]);
    }

    public function bloodBankTransfuse(Request $request, BloodUnit $unit)
    {
        $nurse = $this->authNurseOrDoctor($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'indication' => ['nullable', 'string', 'max:255'],
        ]);

        if (!in_array($unit->status, ['available', 'reserved'], true)) {
            throw ValidationException::withMessages(['status' => ['This unit is not available for transfusion.']]);
        }

        BloodTransfusion::create([
            'patient_id' => $request->patient_id,
            'blood_unit_id' => $unit->id,
            'administered_by' => $nurse->id,
            'indication' => $request->indication,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $unit->update(['status' => 'transfused', 'reserved_for' => $request->patient_id]);

        return response()->json(['message' => 'Transfusion started.']);
    }

    public function bloodBankUpdateStatus(Request $request, BloodUnit $unit)
    {
        $this->authNurseOrDoctor($request);

        $request->validate([
            'status' => ['required', 'in:available,reserved,transfused,expired,discarded'],
            'reserved_for' => ['nullable', 'exists:patients,id'],
        ]);

        $unit->update([
            'status' => $request->status,
            'reserved_for' => $request->status === 'reserved' ? $request->reserved_for : null,
        ]);

        return response()->json(['message' => 'Blood unit status updated.']);
    }

    public function bloodBankDestroy(Request $request, BloodUnit $unit)
    {
        $this->authNurseOrDoctor($request);
        $unit->delete();

        return response()->json(['message' => 'Blood unit deleted.']);
    }

    // ============================================================
    // Consent Forms
    // ============================================================

    public function consentFormsList(Request $request)
    {
        $this->authWardTeam($request);

        $items = ConsentForm::with(['patient:id,name,mrn', 'createdBy:id,name'])
            ->latest()
            ->take(100)
            ->get()
            ->map(fn (ConsentForm $f) => $this->consentFormPayload($f));

        return response()->json(['consent_forms' => $items]);
    }

    private function consentFormPayload(ConsentForm $f): array
    {
        return [
            'id' => $f->id,
            'consent_no' => $f->consent_no,
            'patient_id' => $f->patient_id,
            'patient_name' => $f->patient?->name,
            'patient_mrn' => $f->patient?->mrn,
            'consent_type' => $f->consent_type,
            'procedure_name' => $f->procedure_name,
            'description' => $f->description,
            'witness_name' => $f->witness_name,
            'doctor_name' => $f->doctor_name,
            'consent_date' => $f->consent_date,
            'status' => $f->status,
            'created_by_name' => $f->createdBy?->name,
            'has_patient_signature' => !empty($f->patient_signature),
            'has_witness_signature' => !empty($f->witness_signature),
        ];
    }

    public function consentFormStore(Request $request)
    {
        $nurse = $this->authWardTeam($request);

        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'consent_type' => ['required', 'in:general,surgery,anesthesia,blood_transfusion,procedure,other'],
            'procedure_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'witness_name' => ['nullable', 'string', 'max:255'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'consent_date' => ['required', 'date'],
        ]);

        $patient = Patient::find($request->patient_id);
        $last = ConsentForm::withTrashed()->latest('id')->first();
        $number = $last ? (intval(substr($last->consent_no, 4)) + 1) : 1;
        $consentNo = 'CNS-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);

        $form = ConsentForm::create([
            'consent_no' => $consentNo,
            'patient_id' => $request->patient_id,
            'consent_type' => $request->consent_type,
            'procedure_name' => $request->procedure_name,
            'description' => $request->description,
            'patient_name' => $patient->name,
            'witness_name' => $request->witness_name,
            'doctor_name' => $request->doctor_name,
            'consent_date' => $request->consent_date,
            'status' => 'pending',
            'created_by' => $nurse->id,
        ]);

        return response()->json(['message' => 'Consent form ' . $consentNo . ' created!', 'id' => $form->id], 201);
    }

    public function consentFormSign(Request $request, ConsentForm $consentForm)
    {
        $this->authWardTeam($request);

        $request->validate([
            'patient_signature' => ['required', 'string', 'max:2000000'],
            'witness_signature' => ['nullable', 'string', 'max:2000000'],
        ]);

        $consentForm->update([
            'patient_signature' => $request->patient_signature,
            'witness_signature' => $request->witness_signature,
            'status' => 'signed',
        ]);

        return response()->json(['message' => 'Consent form signed successfully!']);
    }

    public function consentFormUpdateStatus(Request $request, ConsentForm $consentForm)
    {
        $this->authWardTeam($request);
        $request->validate(['status' => ['required', 'in:pending,signed,declined']]);

        $consentForm->update(['status' => $request->status]);

        return response()->json(['message' => 'Consent form status updated.']);
    }

    public function consentFormDestroy(Request $request, ConsentForm $consentForm)
    {
        $this->authWardTeam($request);
        $consentForm->delete();

        return response()->json(['message' => 'Consent form deleted.']);
    }

    // ============================================================
    // Emergency / ER
    // ============================================================

    public function erList(Request $request)
    {
        $this->authWardTeam($request);

        $waitingCount = ErRegistration::where('status', 'waiting')->count();
        $underTreatment = ErRegistration::where('status', 'under_treatment')->count();
        $redAlertCount = ErRegistration::where('triage_level', 'red')->whereNotIn('status', ['discharged', 'transferred', 'expired'])->count();
        $todayTotal = ErRegistration::whereDate('arrival_time', today())->count();

        $items = ErRegistration::with(['patient:id,mrn', 'attendingDoctor:id,name'])
            ->latest('arrival_time')
            ->take(100)
            ->get()
            ->map(fn (ErRegistration $e) => $this->erPayload($e));

        return response()->json([
            'stats' => ['waiting' => $waitingCount, 'under_treatment' => $underTreatment, 'red_alert' => $redAlertCount, 'today_total' => $todayTotal],
            'registrations' => $items,
        ]);
    }

    private function erPayload(ErRegistration $e): array
    {
        return [
            'id' => $e->id,
            'er_no' => $e->er_no,
            'patient_id' => $e->patient_id,
            'patient_name' => $e->patient_name,
            'patient_mrn' => $e->patient?->mrn,
            'patient_phone' => $e->patient_phone,
            'age' => $e->age,
            'gender' => $e->gender,
            'triage_level' => $e->triage_level,
            'chief_complaint' => $e->chief_complaint,
            'presenting_symptoms' => $e->presenting_symptoms,
            'mode_of_arrival' => $e->mode_of_arrival,
            'status' => $e->status,
            'attending_doctor_name' => $e->attendingDoctor?->name,
            'arrival_time' => $e->arrival_time,
            'triage_time' => $e->triage_time,
            'discharge_time' => $e->discharge_time,
            'notes' => $e->notes,
        ];
    }

    public function erStore(Request $request)
    {
        $nurse = $this->authWardTeam($request);

        $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'gender' => ['nullable', 'in:male,female,other'],
            'triage_level' => ['required', 'in:red,yellow,green,black'],
            'chief_complaint' => ['required', 'string', 'max:500'],
            'presenting_symptoms' => ['nullable', 'string', 'max:2000'],
            'mode_of_arrival' => ['nullable', 'string', 'max:100'],
        ]);

        $er = ErRegistration::create([
            ...$request->only(['patient_id', 'patient_name', 'patient_phone', 'age', 'gender', 'triage_level', 'chief_complaint', 'presenting_symptoms', 'mode_of_arrival']),
            'triaged_by' => $nurse->id,
            'status' => 'waiting',
            'arrival_time' => now(),
            'triage_time' => now(),
        ]);

        return response()->json(['message' => 'Patient registered in ER!', 'id' => $er->id], 201);
    }

    public function erDetail(Request $request, ErRegistration $er)
    {
        $this->authWardTeam($request);
        $er->load(['patient', 'triagedBy', 'attendingDoctor']);

        return response()->json($this->erPayload($er));
    }

    public function erUpdate(Request $request, ErRegistration $er)
    {
        $this->authWardTeam($request);
        $request->validate([
            'attending_doctor_id' => ['nullable', 'exists:doctors,id'],
            'status' => ['required', 'in:waiting,under_treatment,admitted,discharged,transferred,expired'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
        ]);

        $er->update($request->only(['attending_doctor_id', 'status', 'notes', 'icd10_code']));

        return response()->json(['message' => 'ER record updated!']);
    }

    public function erTriage(Request $request, ErRegistration $er)
    {
        $nurse = $this->authWardTeam($request);
        $request->validate(['triage_level' => ['required', 'in:red,yellow,green,black']]);

        $er->update(['triage_level' => $request->triage_level, 'triaged_by' => $nurse->id, 'triage_time' => now(), 'status' => 'under_treatment']);

        return response()->json(['message' => 'Patient triaged.']);
    }

    public function erDischarge(Request $request, ErRegistration $er)
    {
        $this->authWardTeam($request);
        $request->validate(['status' => ['required', 'in:discharged,admitted,transferred,expired'], 'notes' => ['nullable', 'string', 'max:2000']]);

        $er->update(['status' => $request->status, 'discharge_time' => now(), 'notes' => $request->notes ?? $er->notes]);

        return response()->json(['message' => 'ER visit closed.']);
    }

    public function erDestroy(Request $request, ErRegistration $er)
    {
        $this->authWardTeam($request);
        $er->delete();

        return response()->json(['message' => 'ER record deleted.']);
    }

    // ============================================================
    // Food Intake
    // ============================================================

    public function foodIntakeList(Request $request)
    {
        $this->authNurseOrStaff($request);

        $patients = Patient::with(['dietPlan', 'currentAdmission.ward', 'currentAdmission.bed', 'todayFoodLogs'])
            ->where('patient_type', 'ip')->where('status', 'active')->get()
            ->map(function (Patient $p) {
                $meals = ['breakfast', 'lunch', 'snacks', 'dinner'];
                $logs = $p->todayFoodLogs->keyBy('meal_type');
                $lastMeal = null;
                foreach (array_reverse($meals) as $meal) {
                    if (isset($logs[$meal])) {
                        $lastMeal = ['meal_type' => $meal, 'status' => $logs[$meal]->status];
                        break;
                    }
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'mrn' => $p->mrn,
                    'ward_name' => $p->currentAdmission?->ward?->name,
                    'bed_no' => $p->currentAdmission?->bed?->bed_no,
                    'diet_type' => $p->dietPlan?->diet_type ?? 'not_set',
                    'last_meal' => $lastMeal,
                    'meals_today' => $logs->count(),
                ];
            });

        return response()->json(['patients' => $patients]);
    }

    public function foodIntakeDetail(Request $request, Patient $patient)
    {
        $this->authNurseOrStaff($request);
        $patient->load(['dietPlan', 'currentAdmission.ward', 'currentAdmission.bed']);

        $foodLogs = FoodLog::with('loggedBy')
            ->where('patient_id', $patient->id)
            ->orderByDesc('log_date')
            ->orderByRaw("FIELD(meal_type, 'dinner', 'snacks', 'lunch', 'breakfast')")
            ->take(30)
            ->get()
            ->map(fn (FoodLog $l) => [
                'id' => $l->id,
                'log_date' => $l->log_date,
                'meal_type' => $l->meal_type,
                'status' => $l->status,
                'quantity' => $l->quantity,
                'notes' => $l->notes,
                'logged_by_name' => $l->loggedBy?->name,
            ]);

        return response()->json([
            'diet_plan' => $patient->dietPlan ? [
                'diet_type' => $patient->dietPlan->diet_type,
                'restrictions' => $patient->dietPlan->restrictions,
                'notes' => $patient->dietPlan->notes,
            ] : null,
            'food_logs' => $foodLogs,
        ]);
    }

    public function foodIntakeStoreDietPlan(Request $request, Patient $patient)
    {
        $this->authNurseOrStaff($request);

        $request->validate([
            'diet_type' => ['required', 'in:normal,diabetic,liquid,soft,low_salt,npo,other'],
            'restrictions' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DietPlan::where('patient_id', $patient->id)->update(['status' => 'inactive']);
        DietPlan::create([
            'patient_id' => $patient->id,
            'diet_type' => $request->diet_type,
            'restrictions' => $request->restrictions,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        return response()->json(['message' => 'Diet plan updated!']);
    }

    public function foodIntakeLogMeal(Request $request, Patient $patient)
    {
        $nurse = $this->authNurseOrStaff($request);

        $request->validate([
            'meal_type' => ['required', 'in:breakfast,lunch,snacks,dinner'],
            'status' => ['required', 'in:pending,given,refused,partial'],
            'quantity' => ['nullable', 'in:full,half,none'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        FoodLog::updateOrCreate(
            ['patient_id' => $patient->id, 'log_date' => today(), 'meal_type' => $request->meal_type],
            ['logged_by' => $nurse->id, 'status' => $request->status, 'quantity' => $request->quantity, 'notes' => $request->notes]
        );

        return response()->json(['message' => ucfirst($request->meal_type) . ' logged successfully!']);
    }

    // ============================================================
    // Fluid Intake
    // ============================================================

    public function fluidIntakeList(Request $request)
    {
        $this->authNurseOrStaff($request);

        $patients = Patient::with(['currentAdmission.ward', 'currentAdmission.bed', 'todayFluidLogs'])
            ->where('patient_type', 'ip')->where('status', 'active')->get()
            ->map(function (Patient $p) {
                $intake = $p->todayFluidLogs->where('flow_type', 'intake')->sum('amount_ml');
                $output = $p->todayFluidLogs->where('flow_type', 'output')->sum('amount_ml');

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'mrn' => $p->mrn,
                    'ward_name' => $p->currentAdmission?->ward?->name,
                    'bed_no' => $p->currentAdmission?->bed?->bed_no,
                    'intake' => $intake,
                    'output' => $output,
                    'balance' => $intake - $output,
                ];
            });

        return response()->json(['patients' => $patients]);
    }

    public function fluidIntakeDetail(Request $request, Patient $patient)
    {
        $this->authNurseOrStaff($request);
        $patient->load(['currentAdmission.ward', 'currentAdmission.bed']);

        $fluidLogs = FluidLog::with('loggedBy')
            ->where('patient_id', $patient->id)
            ->orderByDesc('log_date')->orderByDesc('log_time')
            ->take(50)
            ->get()
            ->map(fn (FluidLog $l) => [
                'id' => $l->id,
                'log_date' => $l->log_date,
                'log_time' => $l->log_time,
                'flow_type' => $l->flow_type,
                'category' => $l->category,
                'amount_ml' => $l->amount_ml,
                'notes' => $l->notes,
                'logged_by_name' => $l->loggedBy?->name,
            ]);

        $todayIntake = FluidLog::where('patient_id', $patient->id)->whereDate('log_date', today())->where('flow_type', 'intake')->sum('amount_ml');
        $todayOutput = FluidLog::where('patient_id', $patient->id)->whereDate('log_date', today())->where('flow_type', 'output')->sum('amount_ml');

        return response()->json(['fluid_logs' => $fluidLogs, 'today_intake' => $todayIntake, 'today_output' => $todayOutput]);
    }

    public function fluidIntakeLog(Request $request, Patient $patient)
    {
        $nurse = $this->authNurseOrStaff($request);

        $request->validate([
            'flow_type' => ['required', 'in:intake,output'],
            'category' => ['required', 'in:oral,iv,tube_feed,urine,vomit,drain,stool,other'],
            'amount_ml' => ['required', 'integer', 'min:1'],
            'log_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        FluidLog::create([
            'patient_id' => $patient->id,
            'logged_by' => $nurse->id,
            'log_date' => today(),
            'log_time' => $request->log_time,
            'flow_type' => $request->flow_type,
            'category' => $request->category,
            'amount_ml' => $request->amount_ml,
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => ucfirst($request->flow_type) . ' logged successfully!'], 201);
    }

    public function fluidIntakeDestroy(Request $request, FluidLog $fluidLog)
    {
        $this->authNurseOrStaff($request);
        $fluidLog->delete();

        return response()->json(['message' => 'Entry deleted!']);
    }

    // ============================================================
    // Rounds (My Rounds / Staff Rounds)
    // ============================================================

    public function myRounds(Request $request)
    {
        $nurse = $this->authNurseOrStaff($request);

        $assignments = RoundAssignment::with(['ward'])
            ->where('staff_id', $nurse->id)->where('status', 'active')->get()
            ->map(function (RoundAssignment $a) {
                $patients = Patient::with(['currentAdmission.bed'])
                    ->whereHas('currentAdmission', fn ($q) => $q->where('ward_id', $a->ward_id)->where('status', 'admitted'))
                    ->where('status', 'active')->get();

                $minutesLeft = $a->next_round_at ? now()->diffInMinutes($a->next_round_at, false) : null;
                $alertStatus = 'ok';
                if ($a->isOverdue()) $alertStatus = 'danger';
                elseif ($minutesLeft !== null && $minutesLeft <= 15) $alertStatus = 'warning';

                return [
                    'id' => $a->id,
                    'ward_name' => $a->ward->name ?? 'N/A',
                    'ward_id' => $a->ward_id,
                    'frequency_hours' => $a->frequency_hours,
                    'next_round_at' => $a->next_round_at,
                    'last_round_at' => $a->last_round_at,
                    'minutes_left' => $minutesLeft,
                    'is_overdue' => $a->isOverdue(),
                    'alert_status' => $alertStatus,
                    'patients' => $patients->map(fn (Patient $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'mrn' => $p->mrn,
                        'bed_no' => $p->currentAdmission?->bed?->bed_no ?? 'N/A',
                    ]),
                ];
            })
            ->sortBy('minutes_left')->values();

        return response()->json(['assignments' => $assignments]);
    }

    public function markRound(Request $request, Patient $patient)
    {
        $nurse = $this->authNurseOrStaff($request);

        $request->validate([
            'assignment_id' => ['required', 'exists:round_assignments,id'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'temperature' => ['nullable', 'string', 'max:20'],
            'pulse_rate' => ['nullable', 'string', 'max:20'],
            'spo2' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        StaffRound::create([
            'patient_id' => $patient->id,
            'staff_id' => $nurse->id,
            'round_time' => now(),
            'status' => 'completed',
            'blood_pressure' => $request->blood_pressure,
            'temperature' => $request->temperature,
            'pulse_rate' => $request->pulse_rate,
            'spo2' => $request->spo2,
            'notes' => $request->notes,
        ]);

        $assignment = RoundAssignment::find($request->assignment_id);
        $assignment->update(['last_round_at' => now(), 'next_round_at' => now()->addHours((int) $assignment->frequency_hours)]);

        return response()->json(['message' => 'Round marked complete for ' . $patient->name . '!']);
    }

    public function roundHistory(Request $request, Patient $patient)
    {
        $this->authNurseOrStaff($request);

        $rounds = StaffRound::with('staff')->where('patient_id', $patient->id)->orderByDesc('round_time')->take(20)->get()
            ->map(fn (StaffRound $r) => [
                'id' => $r->id,
                'round_time' => $r->round_time,
                'blood_pressure' => $r->blood_pressure,
                'temperature' => $r->temperature,
                'pulse_rate' => $r->pulse_rate,
                'spo2' => $r->spo2,
                'notes' => $r->notes,
                'staff_name' => $r->staff?->name,
            ]);

        return response()->json(['rounds' => $rounds]);
    }

    public function roundsAlertCount(Request $request)
    {
        $nurse = $this->authNurseOrStaff($request);
        $count = RoundAssignment::where('staff_id', $nurse->id)->where('status', 'active')->where('next_round_at', '<=', now())->count();

        return response()->json(['count' => $count]);
    }

    public function staffRoundsAssignments(Request $request)
    {
        $this->authNurseOrStaff($request);

        $assignments = RoundAssignment::with(['ward', 'staff', 'assignedBy'])->where('status', 'active')->get()
            ->map(fn (RoundAssignment $a) => [
                'id' => $a->id,
                'ward_name' => $a->ward->name ?? 'N/A',
                'staff_name' => $a->staff->name ?? 'N/A',
                'assigned_by' => $a->assignedBy->name ?? 'N/A',
                'frequency_hours' => $a->frequency_hours,
                'next_round_at' => $a->next_round_at,
                'last_round_at' => $a->last_round_at,
                'is_overdue' => $a->isOverdue(),
            ]);

        return response()->json(['assignments' => $assignments]);
    }

    // ============================================================
    // Profile (name/email/phone/photo/password — same fields as the web's
    // generic UserProfileController, no patient-style mrn/stats block)
    // ============================================================

    public function profile(Request $request)
    {
        $nurse = $this->authNurseOrStaff($request);

        return response()->json($this->nurseProfilePayload($nurse));
    }

    public function updateProfile(Request $request)
    {
        $nurse = $this->authNurseOrStaff($request);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($nurse->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($nurse->profile_photo) {
                Storage::disk('public')->delete($nurse->profile_photo);
            }
            $nurse->update(['profile_photo' => $request->file('profile_photo')->store('profile-photos', 'public')]);
        }

        $nurse->update(['name' => $request->name, 'email' => $request->email, 'phone' => $request->phone]);

        if ($request->filled('password')) {
            $nurse->update(['password' => Hash::make($request->password), 'must_change_password' => false]);
        }

        return response()->json($this->nurseProfilePayload($nurse->fresh()));
    }

    private function nurseProfilePayload(User $nurse): array
    {
        return [
            'user' => [
                'id' => $nurse->id,
                'name' => $nurse->name,
                'email' => $nurse->email,
                'phone' => $nurse->phone,
                'profile_photo_url' => $nurse->profile_photo_url,
                'department' => $nurse->department?->name,
            ],
        ];
    }
}
