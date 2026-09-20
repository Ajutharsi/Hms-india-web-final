@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice-print.scss')
@endsection

@section('page-script')
@vite('resources/assets/js/app-invoice-print.js')
@endsection

@section('content')
<div class="invoice-print p-12">

  {{-- Header --}}
  <div class="d-flex justify-content-between flex-row mb-6">
    <div>
      @if($hospitalSettings->logo_url)
        <img src="{{ $hospitalSettings->logo_url }}"
          alt="Logo" style="height:60px;object-fit:contain;" class="mb-2 d-block" />
      @else
        <div class="d-flex align-items-center mb-2 gap-2">
          <i class="icon-base ti tabler-building-hospital text-primary" style="font-size:2rem;"></i>
        </div>
      @endif
      <span class="fw-bold fs-4">{{ $hospitalSettings->hospital_name }}</span>
      @if($hospitalSettings->address)
      <p class="mb-1">{{ $hospitalSettings->address }}{{ $hospitalSettings->city ? ', ' . $hospitalSettings->city : '' }}</p>
      @endif
      @if($hospitalSettings->country)
      <p class="mb-1">{{ $hospitalSettings->country }}</p>
      @endif
      <p class="mb-0">
        @if($hospitalSettings->phone) {{ $hospitalSettings->phone }} @endif
        @if($hospitalSettings->phone && $hospitalSettings->email) | @endif
        @if($hospitalSettings->email) {{ $hospitalSettings->email }} @endif
      </p>
      @if($hospitalSettings->registration_number)
      <p class="mb-0 small text-muted">Reg No: {{ $hospitalSettings->registration_number }}</p>
      @endif
    </div>
    <div class="text-end">
      <h5 class="mb-2">PRESCRIPTION</h5>
      <p class="mb-1 fw-medium fs-5">{{ $prescription->prescription_no }}</p>
      <p class="mb-1">Date: {{ \Carbon\Carbon::parse($prescription->prescription_date)->format('d M Y') }}</p>
      <p class="mb-0">Appt: {{ $prescription->consultation->appointment->appointment_id }}</p>
    </div>
  </div>

  <hr class="mb-6" />

  {{-- Patient & Doctor --}}
  <div class="row d-flex justify-content-between mb-6">
    <div class="col-sm-6 w-50">
      <h6>Patient Details:</h6>
      <p class="mb-1 fw-medium">{{ $prescription->patient->name }}</p>
      <p class="mb-1">MRN: {{ $prescription->patient->mrn }}</p>
      <p class="mb-1">DOB: {{ $prescription->patient->dob ? \Carbon\Carbon::parse($prescription->patient->dob)->format('d M Y') : 'N/A' }}</p>
      <p class="mb-1">Phone: {{ $prescription->patient->phone ?? 'N/A' }}</p>
      <p class="mb-0">Blood Group: {{ $prescription->patient->blood_group ?? 'N/A' }}</p>
    </div>
    <div class="col-sm-6 w-50">
      <h6>Doctor Details:</h6>
      <p class="mb-1 fw-medium">Dr. {{ $prescription->doctor->name }}</p>
      <p class="mb-1">{{ $prescription->doctor->specialization }}</p>
      <p class="mb-0">{{ $prescription->doctor->qualification ?? '' }}</p>
      @if($prescription->consultation->diagnosis)
      <div class="mt-3 p-2 border rounded">
        <small class="fw-medium">Diagnosis: </small>
        <small>{{ $prescription->consultation->diagnosis }}</small>
      </div>
      @endif
    </div>
  </div>

  {{-- Vitals --}}
  @if($prescription->consultation->blood_pressure || $prescription->consultation->temperature)
  <div class="mb-6 p-3 border rounded">
    <h6 class="mb-3">Vitals:</h6>
    <div class="d-flex flex-wrap gap-6">
      @if($prescription->consultation->blood_pressure)
      <div><strong>BP:</strong> {{ $prescription->consultation->blood_pressure }} mmHg</div>
      @endif
      @if($prescription->consultation->temperature)
      <div><strong>Temp:</strong> {{ $prescription->consultation->temperature }}°F</div>
      @endif
      @if($prescription->consultation->pulse_rate)
      <div><strong>Pulse:</strong> {{ $prescription->consultation->pulse_rate }} bpm</div>
      @endif
      @if($prescription->consultation->weight)
      <div><strong>Weight:</strong> {{ $prescription->consultation->weight }} kg</div>
      @endif
      @if($prescription->consultation->height)
      <div><strong>Height:</strong> {{ $prescription->consultation->height }} cm</div>
      @endif
      @if($prescription->consultation->spo2)
      <div><strong>SpO2:</strong> {{ $prescription->consultation->spo2 }}%</div>
      @endif
    </div>
  </div>
  @endif

  {{-- Medicines Table --}}
  <div class="table-responsive border border-bottom-0 border-top-0 rounded mb-6">
    <table class="table m-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Medicine</th>
          <th>Dosage</th>
          <th>Frequency</th>
          <th>Duration</th>
          <th>Route</th>
          <th>Instructions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($prescription->items as $index => $item)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td class="fw-medium">{{ $item->medicine_name }}</td>
          <td>{{ $item->dosage }}</td>
          <td>{{ $item->frequency }}</td>
          <td>{{ $item->duration }}</td>
          <td class="text-capitalize">{{ $item->route ?? 'Oral' }}</td>
          <td><small>{{ $item->instructions ?? '-' }}</small></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Notes --}}
  @if($prescription->notes)
  <hr class="mt-0 mb-4" />
  <div>
    <span class="fw-medium">Notes: </span>
    <span>{{ $prescription->notes }}</span>
  </div>
  @endif

  {{-- Signature --}}
  <hr class="mt-6 mb-4" />
  <div class="d-flex justify-content-between align-items-end">
    <div>
      @if($hospitalSettings->footer_note)
      <p class="mb-1 text-muted small">{{ $hospitalSettings->footer_note }}</p>
      @endif
      <p class="mb-0 text-muted small">This prescription is valid for 30 days from the date of issue.</p>
      <p class="mb-0 text-muted small">Generated by {{ $hospitalSettings->hospital_name }} — {{ now()->format('d M Y H:i') }}</p>
    </div>
    <div class="text-center">
      <div style="border-top: 2px solid #333; width: 200px; margin-bottom: 8px;"></div>
      <p class="mb-0 fw-medium">Dr. {{ $prescription->doctor->name }}</p>
      <p class="mb-0 small text-muted">{{ $prescription->doctor->specialization }}</p>
      <p class="mb-0 small text-muted">{{ $prescription->doctor->qualification ?? '' }}</p>
    </div>
  </div>

</div>
@endsection