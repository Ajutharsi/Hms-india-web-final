<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\NotificationLog;
use App\Models\Prescription;
use App\Traits\AuthorizesAccess;
use Illuminate\Http\Request;

class PatientPortalController extends Controller
{
    use AuthorizesAccess;

    public function bookAppointment()
    {
        $patient = $this->getAuthPatient();
        abort_if(!$patient, 403, 'No patient profile found for this account.');

        $doctors = Doctor::where('status', 'active')->orderBy('name')->get(['id', 'name', 'specialization', 'photo']);

        return view('portal.book-appointment', compact('doctors', 'patient'));
    }

    public function storeAppointment(Request $request)
    {
        $patient = $this->getAuthPatient();
        abort_if(!$patient, 403, 'No patient profile found for this account.');

        $request->validate([
            'doctor_id'        => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'type'             => 'required|in:op,consult,followup',
            'notes'            => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $tokenCount = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $request->appointment_date)
            ->count();

        Appointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $request->doctor_id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'type'             => $request->type,
            'status'           => 'scheduled',
            'notes'            => $request->notes,
            'token_number'     => $tokenCount + 1,
        ]);

        return redirect()->route('appointments.index')->with('success', 'Appointment booked successfully!');
    }

    public function slots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date'      => 'required|date',
        ], $this->validationMessages());

        $doctor = Doctor::find($request->doctor_id);
        $day    = strtolower(date('l', strtotime($request->date)));

        $schedule = $doctor->schedules()->where('day', $day)->where('is_available', true)->first();

        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        $slots    = [];
        $start    = strtotime($schedule->start_time);
        $end      = strtotime($schedule->end_time);
        $duration = $schedule->slot_duration * 60;

        $booked = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('appointment_time')
            ->toArray();

        for ($time = $start; $time < $end; $time += $duration) {
            $timeStr = date('H:i', $time);
            $slots[] = [
                'time'      => $timeStr,
                'available' => !in_array($timeStr, $booked),
            ];
        }

        return response()->json(['slots' => $slots]);
    }

    public function prescriptions()
    {
        $patient = $this->getAuthPatient();
        abort_if(!$patient, 403, 'No patient profile found for this account.');

        $prescriptions = Prescription::with(['doctor', 'items'])
            ->where('patient_id', $patient->id)
            ->latest('prescription_date')
            ->get();

        return view('portal.prescriptions', compact('prescriptions'));
    }

    public function invoices()
    {
        $patient = $this->getAuthPatient();
        abort_if(!$patient, 403, 'No patient profile found for this account.');

        $invoices = Invoice::where('patient_id', $patient->id)
            ->latest('invoice_date')
            ->get();

        return view('portal.invoices', compact('invoices'));
    }

    public function requestPayment(Request $request, Invoice $invoice)
    {
        $patient = $this->getAuthPatient();
        $this->abortIfNotOwner((bool) ($patient && $invoice->patient_id === $patient->id));

        NotificationLog::create([
            'patient_id' => $patient->id,
            'channel'    => 'email',
            'recipient'  => 'billing@' . str_replace(' ', '', strtolower(config('hospital.name'))) . '.local',
            'template'   => 'payment_request',
            'message'    => "Patient {$patient->name} ({$patient->mrn}) has requested payment assistance for Invoice #{$invoice->invoice_no} (Balance: {$invoice->balance}).",
            'status'     => 'sent',
            'sent_at'    => now(),
        ]);

        return back()->with('success', 'Payment request sent! Our billing team will contact you shortly.');
    }

    public function labResults()
    {
        $patient = $this->getAuthPatient();
        abort_if(!$patient, 403, 'No patient profile found for this account.');

        $labOrders = LabOrder::with(['items.labTest', 'items.result'])
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->latest('order_date')
            ->get();

        return view('portal.lab-results', compact('labOrders'));
    }

    public function downloadLabResult(LabOrder $order)
    {
        $patient = $this->getAuthPatient();
        $this->abortIfNotOwner((bool) ($patient && $order->patient_id === $patient->id));

        $order->load(['patient', 'doctor', 'items.labTest', 'items.result']);
        $labOrder = $order;

        return view('lab.orders.print', compact('labOrder'));
    }
}
