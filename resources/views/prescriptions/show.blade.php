@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Prescription - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')
<div class="row invoice-preview">

  {{-- Prescription Preview --}}
  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column align-items-xl-center align-items-md-start align-items-sm-center align-items-start">
          <div class="mb-xl-0 mb-6 text-heading">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-heartbeat text-primary" style="font-size:2rem;"></i>
              <span class="app-brand-text fw-bold fs-4">Hospital MS</span>
            </div>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-1">Sri Lanka 10001</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div>
            <h5 class="mb-4">{{ $prescription->prescription_no }}</h5>
            <div class="mb-1 text-heading">
              <span>Date: </span>
              <span class="fw-medium">{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('d M Y') }}</span>
            </div>
            <div class="text-heading">
              <span>Status: </span>
              <span class="badge bg-label-{{ $prescription->status === 'active' ? 'success' : 'secondary' }}">
                {{ ucfirst($prescription->status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- Patient & Doctor --}}
      <div class="card-body px-0">
        <div class="row">
          <div class="col-md-6 mb-6 mb-md-0">
            <h6>Patient:</h6>
            <p class="mb-1 fw-medium text-heading">{{ $prescription->patient->name }}</p>
            <p class="mb-1">MRN: {{ $prescription->patient->mrn }}</p>
            <p class="mb-1">
              DOB: {{ $prescription->patient->dob ? \Carbon\Carbon::parse($prescription->patient->dob)->format('d M Y') : 'N/A' }}
            </p>
            <p class="mb-1">Phone: {{ $prescription->patient->phone ?? 'N/A' }}</p>
            <p class="mb-0">Blood Group: {{ $prescription->patient->blood_group ?? 'N/A' }}</p>
          </div>
          <div class="col-md-6">
            <h6>Doctor:</h6>
            <div class="d-flex align-items-center gap-2 mb-1">
              <img src="{{ $prescription->doctor->photo_url }}" alt="{{ $prescription->doctor->name }}"
                class="rounded-circle" style="width:32px;height:32px;object-fit:cover;"
                onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
              <p class="mb-0 fw-medium text-heading">Dr. {{ $prescription->doctor->name }}</p>
            </div>
            <p class="mb-1">{{ $prescription->doctor->specialization }}</p>
            <p class="mb-1">{{ $prescription->doctor->qualification ?? '' }}</p>
            @if($prescription->consultation->diagnosis)
            <div class="mt-3 p-3 bg-label-info rounded">
              <small class="fw-medium d-block mb-1">Diagnosis:</small>
              <p class="mb-0 small">{{ $prescription->consultation->diagnosis }}</p>
            </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Vitals --}}
      @if($prescription->consultation->blood_pressure || $prescription->consultation->temperature)
      <div class="card-body px-0 pt-0">
        <h6 class="mb-3">Vitals:</h6>
        <div class="d-flex flex-wrap gap-4">
          @if($prescription->consultation->blood_pressure)
          <div class="badge bg-label-primary p-2">
            <i class="icon-base ti tabler-activity me-1"></i>
            BP: {{ $prescription->consultation->blood_pressure }} mmHg
          </div>
          @endif
          @if($prescription->consultation->temperature)
          <div class="badge bg-label-warning p-2">
            <i class="icon-base ti tabler-temperature me-1"></i>
            Temp: {{ $prescription->consultation->temperature }}°F
          </div>
          @endif
          @if($prescription->consultation->pulse_rate)
          <div class="badge bg-label-danger p-2">
            <i class="icon-base ti tabler-heart me-1"></i>
            Pulse: {{ $prescription->consultation->pulse_rate }} bpm
          </div>
          @endif
          @if($prescription->consultation->weight)
          <div class="badge bg-label-success p-2">
            <i class="icon-base ti tabler-weight me-1"></i>
            Weight: {{ $prescription->consultation->weight }} kg
          </div>
          @endif
          @if($prescription->consultation->spo2)
          <div class="badge bg-label-info p-2">
            <i class="icon-base ti tabler-lungs me-1"></i>
            SpO2: {{ $prescription->consultation->spo2 }}%
          </div>
          @endif
        </div>
      </div>
      @endif

      {{-- Medicines Table --}}
      <div class="table-responsive border border-bottom-0 border-top-0 rounded mt-4">
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
              <td class="fw-medium text-heading">{{ $item->medicine_name }}</td>
              <td>{{ $item->dosage }}</td>
              <td><span class="badge bg-label-primary">{{ $item->frequency }}</span></td>
              <td>{{ $item->duration }}</td>
              <td class="text-capitalize">{{ $item->route ?? 'Oral' }}</td>
              <td><small class="text-muted">{{ $item->instructions ?? '-' }}</small></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Notes --}}
      @if($prescription->notes)
      <hr class="mt-6 mb-4" />
      <div class="card-body p-0">
        <span class="fw-medium text-heading">Notes: </span>
        <span>{{ $prescription->notes }}</span>
      </div>
      @endif

      {{-- Doctor Signature --}}
      <hr class="mt-6 mb-4" />
      <div class="card-body p-0">
        <div class="d-flex justify-content-end">
          <div class="text-center">
            <div style="border-top: 2px solid #000; width: 200px; margin-bottom: 8px;"></div>
            <p class="mb-0 fw-medium">Dr. {{ $prescription->doctor->name }}</p>
            <p class="mb-0 text-muted small">{{ $prescription->doctor->specialization }}</p>
            <p class="mb-0 text-muted small">{{ $prescription->doctor->qualification ?? '' }}</p>
          </div>
        </div>
      </div>

    </div>
  </div>
  {{-- /Prescription --}}

  {{-- Actions --}}
  <div class="col-xl-3 col-md-4 col-12 invoice-actions">
    <div class="card">
      <div class="card-body">
        <a href="{{ route('prescriptions.print', $prescription->id) }}"
          target="_blank"
          class="btn btn-primary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-printer icon-xs me-2"></i>Print Prescription
          </span>
        </a>
        <a href="{{ route('appointments.index') }}"
          class="btn btn-label-secondary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-arrow-left icon-xs me-2"></i>Back to Appointments
          </span>
        </a>
        <a href="{{ route('patients.show', $prescription->patient_id) }}"
          class="btn btn-label-info d-grid w-100">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-user icon-xs me-2"></i>View Patient
          </span>
        </a>
      </div>
    </div>

    {{-- Prescription Info --}}
    <div class="card mt-6">
      <div class="card-header">
        <h6 class="mb-0">Prescription Info</h6>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <small class="text-muted">Prescription No:</small>
          <p class="mb-0 fw-medium">{{ $prescription->prescription_no }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted">Date:</small>
          <p class="mb-0">{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('d M Y') }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted">Total Medicines:</small>
          <p class="mb-0">{{ $prescription->items->count() }}</p>
        </div>
        <div>
          <small class="text-muted">Status:</small>
          <p class="mb-0">
            <span class="badge bg-label-{{ $prescription->status === 'active' ? 'success' : 'secondary' }}">
              {{ ucfirst($prescription->status) }}
            </span>
          </p>
        </div>
      </div>
    </div>

  </div>

</div>
@endsection