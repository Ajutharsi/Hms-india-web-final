<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\NotificationLog;
use App\Models\OpdToken;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * JSON endpoints for the Flutter patient app — a parallel, token-authenticated
 * path alongside the existing Blade-based Portal\PatientPortalController
 * (same underlying logic where it applies, since that one only returns HTML
 * views the app can't consume).
 */
class PatientController extends Controller
{
    private function authPatient(Request $request): Patient
    {
        $patient = Patient::where('user_id', $request->user()->id)->first();
        abort_if(!$patient, 403, 'No patient profile is linked to this account.');

        return $patient;
    }

    public function appointments(Request $request)
    {
        $patient = $this->authPatient($request);

        $appointments = Appointment::with('doctor:id,name,specialization')
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->id,
                'appointment_id' => $a->appointment_id,
                'doctor_name' => $a->doctor?->name,
                'doctor_specialization' => $a->doctor?->specialization,
                'date' => $a->appointment_date,
                'time' => substr($a->appointment_time, 0, 5),
                'type' => $a->type,
                'status' => $a->status,
                'token_number' => $a->token_number,
                'notes' => $a->notes,
            ]);

        return response()->json(['appointments' => $appointments]);
    }

    public function prescriptions(Request $request)
    {
        $patient = $this->authPatient($request);

        $prescriptions = Prescription::with(['doctor:id,name,specialization', 'items'])
            ->where('patient_id', $patient->id)
            ->latest('prescription_date')
            ->get()
            ->map(fn (Prescription $p) => [
                'id' => $p->id,
                'prescription_no' => $p->prescription_no,
                'date' => $p->prescription_date,
                'doctor_name' => $p->doctor?->name,
                'doctor_specialization' => $p->doctor?->specialization,
                'notes' => $p->notes,
                'status' => $p->status,
                'items' => $p->items->map(fn ($item) => [
                    'medicine_name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                    'route' => $item->route,
                    'instructions' => $item->instructions,
                ]),
            ]);

        return response()->json(['prescriptions' => $prescriptions]);
    }

    /**
     * Only completed orders — mirrors PatientPortalController::labResults()
     * (a patient shouldn't see in-progress/pending results before the lab
     * has actually finished and a clinician has reviewed them).
     */
    public function labResults(Request $request)
    {
        $patient = $this->authPatient($request);

        $labOrders = LabOrder::with(['doctor:id,name', 'items.labTest', 'items.result'])
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->latest('order_date')
            ->get()
            ->map(fn (LabOrder $order) => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'date' => $order->order_date,
                'doctor_name' => $order->doctor?->name,
                'notes' => $order->notes,
                'items' => $order->items->map(fn ($item) => [
                    'test_name' => $item->labTest?->test_name,
                    'category' => $item->labTest?->category,
                    'result_value' => $item->result?->result_value,
                    'unit' => $item->result?->unit,
                    'normal_range' => $item->result?->normal_range,
                    'flag' => $item->result?->flag,
                    'remarks' => $item->result?->remarks,
                ]),
            ]);

        return response()->json(['lab_orders' => $labOrders]);
    }

    public function invoices(Request $request)
    {
        $patient = $this->authPatient($request);

        $invoices = Invoice::with('items')
            ->where('patient_id', $patient->id)
            ->latest('invoice_date')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'date' => $invoice->invoice_date?->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'total' => (float) $invoice->total,
                'paid_amount' => (float) $invoice->paid_amount,
                'balance' => (float) $invoice->balance,
                // Not `status` directly: Invoice::recalculate() never sets
                // status back to 'unpaid' (only 'paid'/'partial'), so it
                // under-reports outstanding invoices. Balance is the one
                // number that's always kept accurate.
                'status' => $invoice->balance > 0 ? ($invoice->paid_amount > 0 ? 'partial' : 'unpaid') : 'paid',
                'items' => $invoice->items->map(fn ($item) => [
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ]),
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    /**
     * Mirrors PatientPortalController::requestPayment() — logs a help
     * request for the billing team rather than actually processing any
     * payment. No payment gateway exists in this app; be upfront in the
     * app UI about what this button does.
     */
    public function requestInvoiceHelp(Request $request, Invoice $invoice)
    {
        $patient = $this->authPatient($request);
        abort_if($invoice->patient_id !== $patient->id, 403);

        NotificationLog::create([
            'patient_id' => $patient->id,
            'channel' => 'email',
            'recipient' => 'billing@' . str_replace(' ', '', strtolower(config('hospital.name'))) . '.local',
            'template' => 'payment_request',
            'message' => "Patient {$patient->name} ({$patient->mrn}) has requested payment assistance for Invoice #{$invoice->invoice_no} (Balance: {$invoice->balance}).",
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json(['message' => 'Request sent — our billing team will contact you shortly.']);
    }

    public function doctors()
    {
        $doctors = Doctor::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'specialization']);

        return response()->json(['doctors' => $doctors]);
    }

    public function slots(Request $request)
    {
        $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $doctor = Doctor::findOrFail($request->doctor_id);
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
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get()
            ->map(fn (Appointment $a) => substr($a->appointment_time, 0, 5))
            ->toArray();

        for ($time = $start; $time < $end; $time += $duration) {
            $timeStr = date('H:i', $time);
            $slots[] = ['time' => $timeStr, 'available' => !in_array($timeStr, $booked, true)];
        }

        return response()->json(['slots' => $slots]);
    }

    /**
     * Mirrors AppointmentController::store's transaction + lockForUpdate
     * token assignment (and OPD token issuance for 'op' visits) rather than
     * PatientPortalController::storeAppointment's unguarded count() — this
     * is the safe version of that same booking flow.
     */
    public function bookAppointment(Request $request)
    {
        $patient = $this->authPatient($request);

        $data = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'type' => ['required', 'in:op,consult,followup'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $conflict = Appointment::where('doctor_id', $data['doctor_id'])
            ->whereDate('appointment_date', $data['appointment_date'])
            ->where('appointment_time', $data['appointment_time'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'appointment_time' => ['That time slot was just taken — please pick another.'],
            ]);
        }

        $appointment = DB::transaction(function () use ($data, $patient) {
            $tokenCount = Appointment::where('doctor_id', $data['doctor_id'])
                ->where('appointment_date', $data['appointment_date'])
                ->lockForUpdate()
                ->count();

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'],
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'type' => $data['type'],
                'status' => 'scheduled',
                'notes' => $data['notes'] ?? null,
                'token_number' => $tokenCount + 1,
                'booked_by' => 'patient_app',
            ]);

            if ($data['type'] === 'op') {
                $position = OpdToken::whereDate('token_date', $data['appointment_date'])
                    ->where('doctor_id', $data['doctor_id'])
                    ->lockForUpdate()
                    ->max('queue_position') + 1;

                OpdToken::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $data['doctor_id'],
                    'appointment_id' => $appointment->id,
                    'token_date' => $data['appointment_date'],
                    'status' => 'waiting',
                    'queue_position' => $position,
                ]);
            }

            return $appointment;
        });

        return response()->json([
            'message' => 'Appointment booked successfully.',
            'appointment' => [
                'id' => $appointment->id,
                'appointment_id' => $appointment->appointment_id,
                'token_number' => $appointment->token_number,
            ],
        ], 201);
    }

    public function cancelAppointment(Request $request, Appointment $appointment)
    {
        $patient = $this->authPatient($request);
        abort_if($appointment->patient_id !== $patient->id, 403);

        if ($appointment->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'status' => ['Only upcoming, scheduled appointments can be cancelled.'],
            ]);
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Appointment cancelled.']);
    }

    public function profile(Request $request)
    {
        $patient = $this->authPatient($request);

        return response()->json($this->profilePayload($request->user(), $patient));
    }

    /**
     * Mirrors UserProfileController::update — same validation rules and
     * profile-photo storage path, but a multipart JSON response instead of
     * a redirect since this is the mobile app's API.
     */
    public function updateProfile(Request $request)
    {
        $patient = $this->authPatient($request);
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->update(['profile_photo' => $request->file('profile_photo')->store('profile-photos', 'public')]);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password), 'must_change_password' => false]);
        }

        $patient->update(['email' => $user->email]);

        return response()->json($this->profilePayload($user->fresh(), $patient));
    }

    private function profilePayload(User $user, Patient $patient): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_photo_url' => $user->profile_photo_url,
            ],
            'mrn' => $patient->mrn,
            'stats' => [
                'total_appointments' => Appointment::where('patient_id', $patient->id)->count(),
                'today_appointments' => Appointment::where('patient_id', $patient->id)->whereDate('appointment_date', today())->count(),
                'lab_orders' => LabOrder::where('patient_id', $patient->id)->count(),
            ],
        ];
    }
}
