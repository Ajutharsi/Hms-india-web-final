<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Icd10Code;
use App\Models\LabOrder;
use App\Models\OpdToken;
use App\Models\OtSchedule;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\RadiologyOrder;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * JSON endpoints for the Flutter doctor app — mirrors the web's
 * DoctorDashboardController plus Doctor\ConsultationController and
 * Doctor\PrescriptionController.
 *
 * A doctor is a User with the `doctor` role linked to a Doctor record by
 * email (the same link the web's AuthorizesAccess::getAuthDoctor() uses).
 * An unlinked account can sign in but owns no appointments, so its "my"
 * numbers are all zero rather than hospital-wide counts.
 */
class DoctorController extends Controller
{
    private function authDoctorUser(Request $request): User
    {
        abort_unless($request->user()->hasRole('doctor'), 403, 'This account is not a doctor account.');

        return $request->user();
    }

    /** The Doctor record behind the signed-in user, or null when unlinked. */
    private function doctorRecord(Request $request): ?Doctor
    {
        return Doctor::where('email', $this->authDoctorUser($request)->email)->first();
    }

    /** Appointments/consultations may only be touched by their own doctor. */
    private function ownedDoctorId(Request $request): int
    {
        $doctor = $this->doctorRecord($request);
        abort_if($doctor === null, 403, 'This doctor account is not linked to a doctor record yet.');

        return $doctor->id;
    }

    /**
     * Who may write the clinical record for this appointment or
     * consultation: its own doctor, or an administrator on their behalf.
     *
     * The web allows both — its route is `role:super_admin|admin|doctor`
     * and neither ConsultationController nor PrescriptionController checks
     * ownership beyond that. The record is filed against the appointment's
     * doctor either way, not against whoever typed it, so an admin
     * entering a consultation for a doctor does not change whose it is.
     *
     * This API refused the admin until now. That was my own reading of
     * "a doctor's endpoints", not the web's rule, and it left two things
     * an administrator can do at a desk undoable on a phone.
     */
    private function assertMayWriteClinicalRecord(Request $request, int $doctorId, string $what): void
    {
        if ($request->user()->hasAnyRole(['admin', 'super_admin'])) {
            return;
        }

        abort_unless($doctorId === $this->ownedDoctorId($request), 403, "This {$what} belongs to another doctor.");
    }

    private function appointmentPayload(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'appointment_id' => $a->appointment_id,
            'patient_id' => $a->patient_id,
            'patient_name' => $a->patient?->name,
            'patient_mrn' => $a->patient?->mrn,
            'date' => optional($a->appointment_date)->format('Y-m-d') ?? (string) $a->appointment_date,
            'time' => $a->appointment_time,
            'type' => $a->type,
            'status' => $a->status,
            'token_number' => $a->token_number,
            'notes' => $a->notes,
            'has_consultation' => $a->consultation()->exists(),
        ];
    }

    private function consultationPayload(Consultation $c): array
    {
        $prescription = Prescription::with('items')->where('consultation_id', $c->id)->latest('id')->first();

        return [
            'id' => $c->id,
            'appointment_id' => $c->appointment_id,
            'patient_id' => $c->patient_id,
            'patient_name' => $c->patient?->name,
            'date' => optional($c->created_at)->format('Y-m-d'),
            'chief_complaint' => $c->chief_complaint,
            'diagnosis' => $c->diagnosis,
            'icd10_code' => $c->icd10_code,
            'treatment_plan' => $c->treatment_plan,
            'notes' => $c->notes,
            'vitals' => [
                'blood_pressure' => $c->blood_pressure,
                'temperature' => $c->temperature,
                'pulse_rate' => $c->pulse_rate,
                'weight' => $c->weight,
                'height' => $c->height,
                'spo2' => $c->spo2,
            ],
            'prescription' => $prescription === null ? null : [
                'id' => $prescription->id,
                'prescription_no' => $prescription->prescription_no,
                'notes' => $prescription->notes,
                'items' => $prescription->items->map(fn ($i) => [
                    'medicine_name' => $i->medicine_name,
                    'dosage' => $i->dosage,
                    'frequency' => $i->frequency,
                    'duration' => $i->duration,
                    'route' => $i->route,
                    'instructions' => $i->instructions,
                ])->all(),
            ],
        ];
    }

    private function patientPayload(Patient $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'mrn' => $p->mrn,
            'gender' => $p->gender,
            'age' => $p->age,
            'phone' => $p->phone,
            'blood_group' => $p->blood_group,
            'status' => $p->status,
            'photo_url' => $p->photo_url ?? null,
        ];
    }

    public function dashboard(Request $request)
    {
        $doctor = $this->doctorRecord($request);
        $doctorId = $doctor?->id;

        // Guarded exactly like the web dashboard: an unlinked account gets
        // zeros, never hospital-wide counts labelled as "mine".
        $scoped = fn ($query) => $doctorId ? $query : null;

        return response()->json([
            'stats' => [
                'my_appointments' => $doctorId ? Appointment::where('doctor_id', $doctorId)->count() : 0,
                'today_appointments' => $doctorId ? Appointment::where('doctor_id', $doctorId)->whereDate('appointment_date', today())->count() : 0,
                'prescriptions' => $doctorId ? Prescription::where('doctor_id', $doctorId)->count() : 0,
                'lab_orders' => $doctorId ? LabOrder::where('doctor_id', $doctorId)->count() : 0,
                'today_ot' => $doctorId ? OtSchedule::where('surgeon_id', $doctorId)->whereDate('scheduled_start', today())->where('status', '!=', 'cancelled')->count() : 0,
                'pending_radiology' => $doctorId ? RadiologyOrder::where('doctor_id', $doctorId)->whereIn('status', ['ordered', 'scheduled', 'in_progress'])->count() : 0,
                'opd_queue' => $doctorId ? OpdToken::where('doctor_id', $doctorId)->whereDate('token_date', today())->whereIn('status', ['waiting', 'in_consultation'])->count() : 0,
                'pending_consultations' => $doctorId
                    ? Appointment::where('doctor_id', $doctorId)->whereDate('appointment_date', today())
                        ->where('status', 'scheduled')->whereDoesntHave('consultation')->count()
                    : 0,
            ],
            'today_appointments' => $scoped(
                Appointment::with('patient')->where('doctor_id', $doctorId)
                    ->whereDate('appointment_date', today())
                    ->orderBy('appointment_time')
                    ->get()
                    ->map(fn ($a) => $this->appointmentPayload($a))
                    ->all()
            ) ?? [],
            'recent_patients' => $scoped(
                Patient::whereIn('id', Appointment::where('doctor_id', $doctorId)->latest('id')->limit(30)->pluck('patient_id'))
                    ->latest('id')->limit(5)->get()
                    ->map(fn ($p) => $this->patientPayload($p))
                    ->all()
            ) ?? [],
            'doctor' => $doctor === null ? null : [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'specialization' => $doctor->specialization,
                'photo_url' => $doctor->photo_url,
            ],
        ]);
    }

    public function appointments(Request $request)
    {
        $doctorId = $this->ownedDoctorId($request);

        $appointments = Appointment::with('patient')
            ->where('doctor_id', $doctorId)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->date, fn ($q, $date) => $q->whereDate('appointment_date', $date))
            ->when($request->boolean('today'), fn ($q) => $q->whereDate('appointment_date', today()))
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time')
            ->limit(200)
            ->get();

        return response()->json(['appointments' => $appointments->map(fn ($a) => $this->appointmentPayload($a))->all()]);
    }

    public function appointmentDetail(Request $request, Appointment $appointment)
    {
        $this->assertMayWriteClinicalRecord($request, $appointment->doctor_id, 'appointment');
        $appointment->load('patient');

        $consultation = Consultation::where('appointment_id', $appointment->id)->latest('id')->first();
        $patient = $appointment->patient;

        return response()->json([
            'appointment' => $this->appointmentPayload($appointment),
            'patient' => $patient ? $this->patientPayload($patient) : null,
            'consultation' => $consultation ? $this->consultationPayload($consultation) : null,
            'latest_vitals' => $patient ? $this->latestVitals($patient->id) : null,
        ]);
    }

    private function latestVitals(int $patientId): ?array
    {
        $vital = Vital::where('patient_id', $patientId)->latest('recorded_at')->first();

        return $vital === null ? null : [
            'blood_pressure' => $vital->blood_pressure,
            'temperature' => $vital->temperature,
            'pulse_rate' => $vital->pulse_rate,
            'spo2' => $vital->spo2,
            'recorded_at' => optional($vital->recorded_at)->format('Y-m-d H:i'),
        ];
    }

    /** Records the consultation for an appointment and completes it. */
    public function consultationStore(Request $request, Appointment $appointment)
    {
        $this->assertMayWriteClinicalRecord($request, $appointment->doctor_id, 'appointment');

        $validated = $request->validate([
            'chief_complaint' => ['required', 'string', 'min:2', 'max:1000'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
            'icd10_code' => ['nullable', 'string', 'max:20'],
            'treatment_plan' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'blood_pressure' => ['nullable', 'string', 'max:20'],
            'temperature' => ['nullable', 'numeric', 'min:0'],
            'pulse_rate' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $consultation = DB::transaction(function () use ($appointment, $validated) {
            $consultation = Consultation::create($validated + [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
            ]);

            $appointment->update(['status' => 'completed']);

            return $consultation;
        });

        return response()->json([
            'message' => 'Consultation saved successfully!',
            'consultation' => $this->consultationPayload($consultation),
        ], 201);
    }

    public function consultationDetail(Request $request, Consultation $consultation)
    {
        $this->assertMayWriteClinicalRecord($request, $consultation->doctor_id, 'consultation');

        return response()->json(['consultation' => $this->consultationPayload($consultation->load('patient'))]);
    }

    /** Writes the prescription attached to a consultation. */
    public function prescriptionStore(Request $request, Consultation $consultation)
    {
        $this->assertMayWriteClinicalRecord($request, $consultation->doctor_id, 'consultation');

        $validated = $request->validate([
            'medicines' => ['required', 'array', 'min:1'],
            'medicines.*.medicine_name' => ['required', 'string', 'max:255'],
            'medicines.*.dosage' => ['required', 'string', 'max:255'],
            'medicines.*.frequency' => ['required', 'string', 'max:255'],
            'medicines.*.duration' => ['required', 'string', 'max:255'],
            'medicines.*.route' => ['nullable', 'string', 'max:255'],
            'medicines.*.instructions' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $prescription = DB::transaction(function () use ($consultation, $validated) {
            $prescription = Prescription::create([
                'consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'doctor_id' => $consultation->doctor_id,
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
            'message' => 'Prescription saved successfully!',
            'prescription_id' => $prescription->id,
            'consultation' => $this->consultationPayload($consultation->fresh()),
        ], 201);
    }

    public function prescriptions(Request $request)
    {
        $doctorId = $this->ownedDoctorId($request);

        $prescriptions = Prescription::with(['items', 'patient'])
            ->where('doctor_id', $doctorId)
            ->when($request->patient_id, fn ($q, $id) => $q->where('patient_id', $id))
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'prescriptions' => $prescriptions->map(fn ($p) => [
                'id' => $p->id,
                'prescription_no' => $p->prescription_no,
                'date' => optional($p->prescription_date)->format('Y-m-d') ?? (string) $p->prescription_date,
                'patient_name' => $p->patient?->name,
                'status' => $p->status,
                'notes' => $p->notes,
                'items' => $p->items->map(fn ($i) => [
                    'medicine_name' => $i->medicine_name,
                    'dosage' => $i->dosage,
                    'frequency' => $i->frequency,
                    'duration' => $i->duration,
                    'route' => $i->route,
                    'instructions' => $i->instructions,
                ])->all(),
            ])->all(),
        ]);
    }

    public function patients(Request $request)
    {
        $this->authDoctorUser($request);

        $search = $request->get('search');
        $nameHash = $search ? Patient::hashFor($search) : null;

        $patients = Patient::query()
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('mrn', 'LIKE', "%{$search}%")
                ->when($nameHash, fn ($n) => $n->orWhere('name_hash', $nameHash))))
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json(['patients' => $patients->map(fn ($p) => $this->patientPayload($p))->all()]);
    }

    public function patientDetail(Request $request, Patient $patient)
    {
        $doctorId = $this->doctorRecord($request)?->id;

        $consultations = Consultation::where('patient_id', $patient->id)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->latest('id')->limit(20)->get();

        return response()->json([
            'patient' => $this->patientPayload($patient),
            'latest_vitals' => $this->latestVitals($patient->id),
            'consultations' => $consultations->map(fn ($c) => [
                'id' => $c->id,
                'date' => optional($c->created_at)->format('Y-m-d'),
                'chief_complaint' => $c->chief_complaint,
                'diagnosis' => $c->diagnosis,
            ])->all(),
            'appointments' => Appointment::where('patient_id', $patient->id)
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->with('patient')->latest('appointment_date')->limit(10)->get()
                ->map(fn ($a) => $this->appointmentPayload($a))->all(),
        ]);
    }

    /** Diagnosis code lookup for the consultation form. */
    public function icd10Search(Request $request)
    {
        $this->authDoctorUser($request);

        $term = trim((string) $request->get('q', ''));
        if (strlen($term) < 2) {
            return response()->json(['codes' => []]);
        }

        $codes = Icd10Code::where('is_active', true)
            ->where(fn ($q) => $q->where('code', 'LIKE', "%{$term}%")->orWhere('description', 'LIKE', "%{$term}%"))
            ->orderBy('code')
            ->limit(25)
            ->get();

        return response()->json([
            'codes' => $codes->map(fn ($c) => [
                'code' => $c->code,
                'description' => $c->description,
                'category' => $c->category,
            ])->all(),
        ]);
    }

    public function profile(Request $request)
    {
        $user = $this->authDoctorUser($request);
        $doctor = $this->doctorRecord($request);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_photo_url' => $user->profile_photo_url,
            ],
            'doctor' => $doctor === null ? null : [
                'id' => $doctor->id,
                'doctor_id' => $doctor->doctor_id,
                'specialization' => $doctor->specialization,
                'qualification' => $doctor->qualification,
                'consultation_fee' => $doctor->consultation_fee,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->authDoctorUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if (! empty($validated['password'])) {
            abort_unless(Hash::check($validated['current_password'], $user->password), 422, 'Your current password is incorrect.');
            $user->password = Hash::make($validated['password']);
        }

        $user->fill(['name' => $validated['name'], 'email' => $validated['email'], 'phone' => $validated['phone'] ?? $user->phone]);
        $user->save();

        return response()->json(['message' => 'Profile updated successfully!']);
    }

    // ------------------------------------------------------- my schedule

    /**
     * The doctor's own clinic hours.
     *
     * These rows are what the patient app's slot picker is built from,
     * and until now only an administrator could change them -- on the
     * web. A doctor who stops a Friday clinic had to ask someone.
     */
    public function schedule(Request $request)
    {
        $doctorId = $this->ownedDoctorId($request);

        $order = "FIELD(day,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')";

        $rows = DoctorSchedule::where('doctor_id', $doctorId)
            ->orderByRaw($order)
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'summary' => [
                'days' => $rows->where('is_available', true)->pluck('day')->unique()->count(),
                'sessions' => $rows->count(),
            ],
            'schedule' => $rows->map(fn ($s) => [
                'id' => $s->id,
                'day' => $s->day,
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'slot_duration' => (int) $s->slot_duration,
                'is_available' => (bool) $s->is_available,
            ])->all(),
        ]);
    }

    public function scheduleStore(Request $request)
    {
        $doctorId = $this->ownedDoctorId($request);
        $data = $this->scheduleRules($request);

        $this->assertNoOverlap($doctorId, $data);

        $row = DoctorSchedule::create($data + ['doctor_id' => $doctorId]);

        return response()->json(['message' => 'Clinic hours added.', 'id' => $row->id], 201);
    }

    public function scheduleUpdate(Request $request, DoctorSchedule $schedule)
    {
        $doctorId = $this->ownedDoctorId($request);
        abort_if($schedule->doctor_id !== $doctorId, 403, 'Those clinic hours belong to another doctor.');

        $data = $this->scheduleRules($request);
        $this->assertNoOverlap($doctorId, $data, $schedule->id);

        $schedule->update($data);

        return response()->json(['message' => 'Clinic hours updated.']);
    }

    public function scheduleDestroy(Request $request, DoctorSchedule $schedule)
    {
        $doctorId = $this->ownedDoctorId($request);
        abort_if($schedule->doctor_id !== $doctorId, 403, 'Those clinic hours belong to another doctor.');

        $schedule->delete();

        return response()->json(['message' => 'Clinic hours removed.']);
    }

    private function scheduleRules(Request $request): array
    {
        return $request->validate([
            'day' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration' => ['nullable', 'integer', 'min:5', 'max:120'],
            'is_available' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Two sessions on the same day that overlap would offer the same
     * minute as two bookable slots.
     */
    private function assertNoOverlap(int $doctorId, array $data, ?int $ignoreId = null): void
    {
        $clash = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day', $data['day'])
            ->when($ignoreId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->first();

        abort_if(
            $clash !== null,
            422,
            'That overlaps the ' . substr((string) $clash?->start_time, 0, 5) .
            '-' . substr((string) $clash?->end_time, 0, 5) . ' session on the same day.'
        );
    }
}
