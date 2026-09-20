<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Consultation;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function create(Consultation $consultation)
    {
        $consultation->load(['patient', 'doctor', 'appointment']);
        return view('prescriptions.create', compact('consultation'));
    }

   public function store(Request $request, Consultation $consultation)
{
    $request->validate([
        'medicines'                    => 'required|array|min:1',
        'medicines.*.medicine_name'    => 'required|string|max:255',
        'medicines.*.dosage'           => 'required|string|max:255',
        'medicines.*.frequency'        => 'required|string|max:255',
        'medicines.*.duration'         => 'required|string|max:255',
        'medicines.*.route'            => 'nullable|string|max:255',
        'medicines.*.instructions'     => 'nullable|string|max:1000',
        'notes'                        => 'nullable|string|max:1000',
    ], $this->validationMessages());

    $prescription = Prescription::create([
        'consultation_id'   => $consultation->id,
        'patient_id'        => $consultation->patient_id,
        'doctor_id'         => $consultation->doctor_id,
        'prescription_date' => now()->toDateString(),
        'notes'             => $request->notes,
        'status'            => 'active',
    ]);

    foreach ($request->medicines as $medicine) {
        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_name'   => $medicine['medicine_name'],
            'dosage'          => $medicine['dosage'],
            'frequency'       => $medicine['frequency'],
            'duration'        => $medicine['duration'],
            'route'           => $medicine['route'] ?? null,
            'instructions'    => $medicine['instructions'] ?? null,
        ]);
    }

    
    \App\Models\PatientMedicalRecord::create([
        'patient_id'  => $consultation->patient_id,
        'record_type' => 'Prescription',
        'record_date' => now()->toDateString(),
        'description' => 'Prescription ' . $prescription->prescription_no . ' issued',
        'doctor_name' => $consultation->doctor->name ?? null,
        'notes'       => $request->notes ?? null,
        'reference_id'   => $prescription->id,        
        'reference_type' => 'prescription',  
    ]);

    return redirect()->route('prescriptions.print', $prescription->id)
        ->with('success', 'Prescription created!');
}


    public function show(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'items', 'consultation']);
        return view('prescriptions.show', compact('prescription'));
    }

    public function print(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'items', 'consultation']);
        return view('prescriptions.print', compact('prescription'));
    }
}