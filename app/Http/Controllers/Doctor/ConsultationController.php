<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function create(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor']);
        return view('consultations.create', compact('appointment'));
    }

    public function store(Request $request, Appointment $appointment)
    {
        $request->validate([
            'chief_complaint' => 'required|string|min:2|max:1000',
            'diagnosis'       => 'nullable|string|max:1000',
            'icd10_code'      => 'nullable|string|max:20',
            'treatment_plan'  => 'nullable|string|max:1000',
            'notes'           => 'nullable|string|max:1000',
            'blood_pressure'  => 'nullable|string|max:20',
            'temperature'     => 'nullable|numeric|min:0',
            'pulse_rate'      => 'nullable|integer|min:0',
            'weight'          => 'nullable|numeric|min:0',
            'height'          => 'nullable|numeric|min:0',
            'spo2'            => 'nullable|integer|min:0|max:100',
        ], $this->validationMessages());

        $consultation = Consultation::create([
            'appointment_id'  => $appointment->id,
            'patient_id'      => $appointment->patient_id,
            'doctor_id'       => $appointment->doctor_id,
            'blood_pressure'  => $request->blood_pressure,
            'temperature'     => $request->temperature,
            'pulse_rate'      => $request->pulse_rate,
            'weight'          => $request->weight,
            'height'          => $request->height,
            'spo2'            => $request->spo2,
            'chief_complaint' => $request->chief_complaint,
            'diagnosis'       => $request->diagnosis,
            'icd10_code'      => $request->icd10_code,
            'treatment_plan'  => $request->treatment_plan,
            'notes'           => $request->notes,
        ]);

        
        $appointment->update(['status' => 'completed']);

        return redirect()->route('prescriptions.create', $consultation->id)
            ->with('success', 'Consultation saved!');
    }
}