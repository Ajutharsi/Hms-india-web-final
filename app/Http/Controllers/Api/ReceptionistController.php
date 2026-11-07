<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\OpdToken;
use App\Models\Patient;
use App\Models\RadiologyOrder;
use App\Models\Referral;
use App\Models\User;
use App\Models\Ward;
use App\Services\SubscriptionLimitService;
use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * JSON endpoints for the Flutter receptionist app — mirrors the web's
 * ReceptionDashboardController + Patient\PatientController +
 * Appointment\AppointmentController + Referral\ReferralController +
 * OPD\OpdQueueController + Radiology\RadiologyController. Same pattern as
 * NurseController/LabAssistantController: authReceptionist() just checks
 * the role, there's no separate Receptionist domain model.
 */
class ReceptionistController extends Controller
{
    use AuthorizesRoles;

    private function authReceptionist(Request $request): User
    {
        return $this->authRoles($request, ['receptionist'], 'This account is not a receptionist account.');
    }

    /**
     * Radiology, Referrals and the OPD queue. The web puts the doctor on
     * all three (`role:...|doctor|receptionist`) — a doctor refers a
     * patient onward, orders imaging and reads the findings, and calls the
     * next patient in. The mobile API had them behind the receptionist-only
     * guard, so none of it was reachable from a doctor's account.
     */
    private function authReceptionistOrDoctor(Request $request): User
    {
        return $this->authRoles($request, ['receptionist', 'doctor']);
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
        $this->authReceptionist($request);

        $todayAppointments = Appointment::with(['patient:id,name,mrn', 'doctor:id,name'])
            ->whereDate('appointment_date', today())
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id,
                'patient_name' => $a->patient?->name,
                'doctor_name' => $a->doctor?->name,
                'time' => substr($a->appointment_time, 0, 5),
                'type' => $a->type,
            ]);

        $recentPatients = Patient::latest()->take(5)->get()
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'mrn' => $p->mrn,
                'status' => $p->status,
                'photo_url' => $p->photo_url,
            ]);

        return response()->json([
            'stats' => [
                'today_appointments' => Appointment::whereDate('appointment_date', today())->count(),
                'total_patients' => Patient::count(),
                'pending_invoices' => Invoice::where('status', 'unpaid')->count(),
                'available_beds' => (int) Ward::sum('available_beds'),
            ],
            'today_appointments' => $todayAppointments,
            'recent_patients' => $recentPatients,
        ]);
    }

    // ============================================================
    // Patients (full CRUD except delete — receptionist has no delete permission)
    // ============================================================

    public function patients(Request $request)
    {
        $this->authReceptionist($request);

        $query = Patient::select(['id', 'mrn', 'name', 'phone', 'gender', 'dob', 'blood_group', 'status', 'patient_type', 'photo'])
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
        ]);

        return response()->json(['patients' => $patients]);
    }

    public function patientDetail(Request $request, Patient $patient)
    {
        $this->authReceptionist($request);

        return response()->json($this->patientPayload($patient));
    }

    private function patientPayload(Patient $patient): array
    {
        return [
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
            'nationality' => $patient->nationality,
            'marital_status' => $patient->marital_status,
            'occupation' => $patient->occupation,
            'status' => $patient->status,
            'patient_type' => $patient->patient_type,
            'photo_url' => $patient->photo_url,
        ];
    }

    private array $patientRules = [
        'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
        'dob' => ['nullable', 'date', 'before:today'],
        'gender' => ['nullable', 'in:male,female,other'],
        'blood_group' => ['nullable', 'string', 'max:255'],
        'address' => ['nullable', 'string', 'max:500'],
        'emergency_contact' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
        'emergency_contact_name' => ['nullable', 'string', 'max:255'],
        'nationality' => ['nullable', 'string', 'max:255'],
        'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
        'occupation' => ['nullable', 'string', 'max:255'],
        'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ];

    public function patientStore(Request $request)
    {
        // Every sibling in this controller checks the role first; these
        // two did not, which left any signed-in account -- a patient's
        // own login included -- able to create and edit patient records
        // through the front desk's endpoints. The route groups carry no
        // role middleware, so the method is the only guard there is.
        $this->authReceptionist($request);

        try {
            app(SubscriptionLimitService::class)->assertCanCreate('patient');
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['name' => [$e->getMessage()]]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'patient_type' => ['required', 'in:op,ip'],
            'status' => ['nullable', 'in:active,discharged,deceased'],
            ...$this->patientRules,
        ]);

        $photo = $request->hasFile('photo') ? $request->file('photo')->store('patients', 'public') : null;

        $patient = Patient::create([
            ...collect($validated)->except(['status', 'photo', 'email'])->all(),
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'photo' => $photo,
        ]);

        if (!empty($validated['email']) && config('hospital.auto_patient_login')) {
            $this->createPortalAccount($patient, $validated['name'], $validated['email']);
        }

        return response()->json(['message' => 'Patient ' . $patient->mrn . ' added successfully!', 'patient' => $this->patientPayload($patient)], 201);
    }

    public function patientUpdate(Request $request, Patient $patient)
    {
        $this->authReceptionist($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($patient->user_id)],
            'patient_type' => ['required', 'in:op,ip'],
            'status' => ['required', 'in:active,discharged,deceased'],
            'gender' => ['required', 'in:male,female,other'],
            ...collect($this->patientRules)->except('photo')->all(),
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = collect($validated)->except('photo')->all();

        if ($request->hasFile('photo')) {
            if ($patient->photo && !str_starts_with($patient->photo, 'assets/')) {
                Storage::disk('public')->delete($patient->photo);
            }
            $data['photo'] = $request->file('photo')->store('patients', 'public');
        }

        $patient->update($data);

        return response()->json(['message' => 'Patient updated successfully!', 'patient' => $this->patientPayload($patient->fresh())]);
    }

    private function createPortalAccount(Patient $patient, string $name, string $email): void
    {
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $patient->update(['user_id' => $existingUser->id]);

            return;
        }

        $defaultPassword = 'Patient@' . substr($patient->mrn, -4);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($defaultPassword),
        ]);
        $user->assignRole('patient');
        $patient->update(['user_id' => $user->id]);
    }

    // ============================================================
    // Appointments (admin scheduling — books for any patient/doctor, unlike
    // the patient app's self-booking flow)
    // ============================================================

    public function appointments(Request $request)
    {
        $this->authReceptionist($request);

        $query = Appointment::with(['patient:id,name,mrn', 'doctor:id,name,specialization'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('appointment_date', $request->date))
            ->orderByDesc('appointment_date')->orderByDesc('appointment_time');

        $appointments = $query->take(150)->get()->map(fn (Appointment $a) => $this->appointmentPayload($a));

        return response()->json(['appointments' => $appointments]);
    }

    private function appointmentPayload(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'appointment_id' => $a->appointment_id,
            'patient_id' => $a->patient_id,
            'patient_name' => $a->patient?->name,
            'patient_mrn' => $a->patient?->mrn,
            'doctor_id' => $a->doctor_id,
            'doctor_name' => $a->doctor?->name,
            'doctor_specialization' => $a->doctor?->specialization,
            'date' => $a->appointment_date,
            'time' => substr($a->appointment_time, 0, 5),
            'token_number' => $a->token_number,
            'type' => $a->type,
            'status' => $a->status,
            'notes' => $a->notes,
        ];
    }

    private array $appointmentRules = [
        'patient_id' => ['required', 'exists:patients,id'],
        'doctor_id' => ['required', 'exists:doctors,id'],
        'appointment_time' => ['required', 'date_format:H:i'],
        'type' => ['required', 'in:op,emergency,consult,followup'],
        'status' => ['required', 'in:scheduled,completed,cancelled,no_show'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ];

    public function appointmentStore(Request $request)
    {
        $receptionist = $this->authReceptionist($request);

        $validated = $request->validate([
            ...$this->appointmentRules,
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $conflict = Appointment::where('doctor_id', $validated['doctor_id'])
            ->whereDate('appointment_date', $validated['appointment_date'])
            ->where('appointment_time', $validated['appointment_time'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['appointment_time' => ['That time slot was just taken — please pick another.']]);
        }

        $appointment = DB::transaction(function () use ($validated, $receptionist) {
            $tokenCount = Appointment::where('doctor_id', $validated['doctor_id'])
                ->where('appointment_date', $validated['appointment_date'])
                ->lockForUpdate()
                ->count();

            $appointment = Appointment::create([
                ...$validated,
                'token_number' => $tokenCount + 1,
                'booked_by' => 'receptionist:' . $receptionist->id,
            ]);

            if ($validated['type'] === 'op') {
                $position = OpdToken::whereDate('token_date', $validated['appointment_date'])
                    ->where('doctor_id', $validated['doctor_id'])
                    ->lockForUpdate()
                    ->max('queue_position') + 1;

                OpdToken::create([
                    'patient_id' => $validated['patient_id'],
                    'doctor_id' => $validated['doctor_id'],
                    'appointment_id' => $appointment->id,
                    'token_date' => $validated['appointment_date'],
                    'status' => 'waiting',
                    'queue_position' => $position,
                ]);
            }

            return $appointment;
        });

        return response()->json(['message' => 'Appointment booked successfully!', 'appointment' => $this->appointmentPayload($appointment)], 201);
    }

    public function appointmentUpdate(Request $request, Appointment $appointment)
    {
        $this->authReceptionist($request);

        $validated = $request->validate([
            ...$this->appointmentRules,
            'appointment_date' => ['required', 'date'],
        ]);

        $appointment->update($validated);

        return response()->json(['message' => 'Appointment updated successfully!', 'appointment' => $this->appointmentPayload($appointment->fresh())]);
    }

    public function appointmentSlots(Request $request)
    {
        $this->authReceptionist($request);

        $request->validate(['doctor_id' => ['required', 'exists:doctors,id'], 'date' => ['required', 'date']]);

        $doctor = Doctor::find($request->doctor_id);
        $day = strtolower(date('l', strtotime($request->date)));
        $schedule = $doctor->schedules()->where('day', $day)->where('is_available', true)->first();

        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        $slots = [];
        $start = strtotime($schedule->start_time);
        $end = strtotime($schedule->end_time);
        $duration = max(1, (int) $schedule->slot_duration) * 60;

        $booked = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('appointment_time')
            ->toArray();

        for ($time = $start; $time < $end; $time += $duration) {
            $timeStr = date('H:i', $time);
            $slots[] = ['time' => $timeStr, 'available' => !in_array($timeStr, $booked)];
        }

        return response()->json(['slots' => $slots]);
    }

    public function doctors(Request $request)
    {
        $this->authReceptionist($request);

        return response()->json(['doctors' => Doctor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'specialization'])]);
    }

    // ============================================================
    // Referrals
    // ============================================================

    public function referrals(Request $request)
    {
        $this->authReceptionistOrDoctor($request);

        $referrals = Referral::with(['patient:id,name,mrn', 'referredBy:id,name', 'referredTo:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('referral_date')
            ->take(150)
            ->get()
            ->map(fn (Referral $r) => [
                'id' => $r->id,
                'referral_no' => $r->referral_no,
                'patient_id' => $r->patient_id,
                'patient_name' => $r->patient?->name,
                'patient_mrn' => $r->patient?->mrn,
                'referred_by_name' => $r->referredBy?->name,
                'referred_to_name' => $r->referredTo?->name,
                'reason' => $r->reason,
                'notes' => $r->notes,
                'priority' => $r->priority,
                'status' => $r->status,
                'referral_date' => $r->referral_date,
            ]);

        return response()->json(['referrals' => $referrals]);
    }

    public function referralStore(Request $request)
    {
        $this->authReceptionistOrDoctor($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'referred_by' => ['required', 'exists:doctors,id'],
            'referred_to' => ['required', 'exists:doctors,id', 'different:referred_by'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'referral_date' => ['required', 'date'],
        ]);

        $last = Referral::withTrashed()->latest('id')->first();
        $number = $last ? (intval(substr($last->referral_no, 4)) + 1) : 1;
        $refNo = 'REF-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);

        $referral = Referral::create([...$validated, 'referral_no' => $refNo, 'status' => 'pending']);

        return response()->json(['message' => 'Referral created!', 'id' => $referral->id], 201);
    }

    public function referralUpdateStatus(Request $request, Referral $referral)
    {
        $this->authReceptionistOrDoctor($request);
        $request->validate(['status' => ['required', 'in:pending,accepted,completed,cancelled']]);

        $referral->update(['status' => $request->status]);

        return response()->json(['message' => 'Referral status updated.']);
    }

    public function referralDestroy(Request $request, Referral $referral)
    {
        $this->authReceptionistOrDoctor($request);
        $referral->delete();

        return response()->json(['message' => 'Referral deleted.']);
    }

    // ============================================================
    // OPD Queue
    // ============================================================

    public function opdQueue(Request $request)
    {
        $this->authReceptionistOrDoctor($request);

        $tokens = OpdToken::with(['patient:id,name,mrn', 'doctor:id,name'])
            ->whereDate('token_date', today())
            ->orderBy('queue_position')
            ->get()
            ->map(fn (OpdToken $t) => [
                'id' => $t->id,
                'token_no' => $t->token_no,
                'patient_id' => $t->patient_id,
                'patient_name' => $t->patient?->name,
                'patient_mrn' => $t->patient?->mrn,
                'doctor_id' => $t->doctor_id,
                'doctor_name' => $t->doctor?->name,
                'status' => $t->status,
                'queue_position' => $t->queue_position,
                'called_at' => $t->called_at,
                'notes' => $t->notes,
            ]);

        return response()->json([
            'stats' => [
                'total' => OpdToken::whereDate('token_date', today())->count(),
                'waiting' => OpdToken::whereDate('token_date', today())->where('status', 'waiting')->count(),
                'in_consult' => OpdToken::whereDate('token_date', today())->where('status', 'in_consultation')->count(),
                'completed' => OpdToken::whereDate('token_date', today())->where('status', 'completed')->count(),
            ],
            'tokens' => $tokens,
        ]);
    }

    public function opdQueueStore(Request $request)
    {
        $this->authReceptionistOrDoctor($request);
        $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $position = OpdToken::whereDate('token_date', today())->where('doctor_id', $request->doctor_id)->max('queue_position') + 1;

        $token = OpdToken::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'token_date' => today(),
            'status' => 'waiting',
            'queue_position' => $position,
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'Token issued successfully!', 'id' => $token->id], 201);
    }

    public function opdQueueCallNext(Request $request, Doctor $doctor)
    {
        $this->authReceptionistOrDoctor($request);

        $current = OpdToken::whereDate('token_date', today())->where('doctor_id', $doctor->id)->where('status', 'in_consultation')->first();
        if ($current) {
            $current->update(['status' => 'completed', 'completed_at' => now()]);
        }

        $next = OpdToken::whereDate('token_date', today())->where('doctor_id', $doctor->id)->where('status', 'waiting')->orderBy('queue_position')->first();
        if (!$next) {
            return response()->json(['message' => 'No patients waiting in queue.'], 404);
        }

        $next->update(['status' => 'in_consultation', 'called_at' => now()]);

        return response()->json(['message' => 'Next patient called.', 'token_id' => $next->id]);
    }

    public function opdQueueUpdateStatus(Request $request, OpdToken $token)
    {
        $this->authReceptionistOrDoctor($request);
        $request->validate(['status' => ['required', 'in:waiting,in_consultation,completed,skipped,cancelled']]);

        $updates = ['status' => $request->status];
        if ($request->status === 'in_consultation') $updates['called_at'] = now();
        if (in_array($request->status, ['completed', 'skipped', 'cancelled'], true)) $updates['completed_at'] = now();

        $token->update($updates);

        return response()->json(['message' => 'Queue status updated.']);
    }

    public function opdQueueDestroy(Request $request, OpdToken $token)
    {
        $this->authReceptionistOrDoctor($request);
        $token->delete();

        return response()->json(['message' => 'Token removed.']);
    }

    // ============================================================
    // Radiology
    // ============================================================

    public function radiologyOrders(Request $request)
    {
        $this->authReceptionistOrDoctor($request);

        $orders = RadiologyOrder::with(['patient:id,name,mrn', 'doctor:id,name'])
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->patient_id))
            ->latest('order_date')
            ->take(150)
            ->get()
            ->map(fn (RadiologyOrder $o) => $this->radiologyPayload($o));

        return response()->json([
            'stats' => [
                'total' => RadiologyOrder::count(),
                'pending' => RadiologyOrder::whereIn('status', ['ordered', 'scheduled', 'in_progress'])->count(),
                'completed' => RadiologyOrder::where('status', 'completed')->count(),
                'emergency' => RadiologyOrder::where('priority', 'emergency')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
            'orders' => $orders,
        ]);
    }

    private function radiologyPayload(RadiologyOrder $o): array
    {
        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'patient_id' => $o->patient_id,
            'patient_name' => $o->patient?->name,
            'patient_mrn' => $o->patient?->mrn,
            'doctor_name' => $o->doctor?->name,
            'modality' => $o->modality,
            'body_part' => $o->body_part,
            'study_description' => $o->study_description,
            'clinical_indication' => $o->clinical_indication,
            'priority' => $o->priority,
            'status' => $o->status,
            'order_date' => $o->order_date,
            'scheduled_at' => $o->scheduled_at,
            'completed_at' => $o->completed_at,
            'findings' => $o->findings,
            'impression' => $o->impression,
            'radiologist_name' => $o->radiologist_name,
        ];
    }

    public function radiologyStore(Request $request)
    {
        $this->authReceptionistOrDoctor($request);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'modality' => ['required', 'string', 'max:255'],
            'body_part' => ['required', 'string', 'max:255'],
            'study_description' => ['required', 'string', 'max:255'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $order = RadiologyOrder::create([...$validated, 'status' => 'ordered', 'order_date' => now()->toDateString()]);

        return response()->json(['message' => 'Radiology order created!', 'id' => $order->id], 201);
    }

    public function radiologyDetail(Request $request, RadiologyOrder $order)
    {
        $this->authReceptionistOrDoctor($request);

        return response()->json($this->radiologyPayload($order));
    }

    public function radiologyUpdate(Request $request, RadiologyOrder $order)
    {
        $this->authReceptionistOrDoctor($request);

        $validated = $request->validate([
            'modality' => ['required', 'string', 'max:255'],
            'body_part' => ['required', 'string', 'max:255'],
            'study_description' => ['required', 'string', 'max:255'],
            'clinical_indication' => ['nullable', 'string', 'max:1000'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'status' => ['required', 'in:ordered,scheduled,in_progress,completed,cancelled'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $order->update($validated);

        return response()->json(['message' => 'Radiology order updated!', 'order' => $this->radiologyPayload($order->fresh())]);
    }

    public function radiologyEnterFindings(Request $request, RadiologyOrder $order)
    {
        $receptionist = $this->authReceptionistOrDoctor($request);

        $validated = $request->validate([
            'findings' => ['nullable', 'string', 'max:5000'],
            'impression' => ['nullable', 'string', 'max:2000'],
            'radiologist_name' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update([
            ...$validated,
            'performed_by' => $receptionist->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json(['message' => 'Findings saved!']);
    }

    public function radiologyDestroy(Request $request, RadiologyOrder $order)
    {
        $this->authReceptionistOrDoctor($request);
        $order->delete();

        return response()->json(['message' => 'Radiology order deleted.']);
    }

    // ============================================================
    // Profile
    // ============================================================

    public function profile(Request $request)
    {
        $receptionist = $this->authReceptionist($request);

        return response()->json($this->profilePayload($receptionist));
    }

    public function updateProfile(Request $request)
    {
        $receptionist = $this->authReceptionist($request);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($receptionist->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($receptionist->profile_photo) {
                Storage::disk('public')->delete($receptionist->profile_photo);
            }
            $receptionist->update(['profile_photo' => $request->file('profile_photo')->store('profile-photos', 'public')]);
        }

        $receptionist->update(['name' => $request->name, 'email' => $request->email, 'phone' => $request->phone]);

        if ($request->filled('password')) {
            $receptionist->update(['password' => Hash::make($request->password), 'must_change_password' => false]);
        }

        return response()->json($this->profilePayload($receptionist->fresh()));
    }

    private function profilePayload(User $receptionist): array
    {
        return [
            'user' => [
                'id' => $receptionist->id,
                'name' => $receptionist->name,
                'email' => $receptionist->email,
                'phone' => $receptionist->phone,
                'profile_photo_url' => $receptionist->profile_photo_url,
                'department' => $receptionist->department?->name,
            ],
        ];
    }
}
