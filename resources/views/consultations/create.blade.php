@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Consultation - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-user-view.scss')
@vite('resources/assets/vendor/libs/select2/select2.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/select2/select2.js')
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="mb-1">Consultation</h4>
    <p class="mb-0 text-muted">
      Appointment: <strong>{{ $appointment->appointment_id }}</strong>
      — {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}
      at {{ $appointment->appointment_time }}
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('radiology.index', ['patient_id' => $appointment->patient_id, 'doctor_id' => $appointment->doctor_id]) }}" class="btn btn-label-warning">
      <i class="icon-base ti tabler-radioactive me-1"></i> Order Radiology
    </a>
    <a href="{{ route('appointments.index') }}" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

<div class="row">

  {{-- Left Sidebar — Patient & Doctor Info --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Patient Card --}}
    <div class="card mb-6">
      <div class="card-body pt-12">
        <div class="d-flex align-items-center flex-column mb-6">
          <div class="avatar avatar-xl mb-4">
            <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:2rem;">
              {{ strtoupper(substr($appointment->patient->name, 0, 1)) }}
            </span>
          </div>
          <h5 class="mb-0">{{ $appointment->patient->name }}</h5>
          <span class="text-muted small">{{ $appointment->patient->mrn }}</span>
          <div class="mt-2">
            <span class="badge bg-label-{{ $appointment->patient->gender === 'male' ? 'primary' : 'danger' }}">
              {{ ucfirst($appointment->patient->gender) }}
            </span>
            <span class="badge bg-label-info ms-1">
              {{ $appointment->patient->blood_group ?? 'N/A' }}
            </span>
          </div>
        </div>

        <h5 class="pb-4 border-bottom mb-4">Patient Details</h5>
        <ul class="list-unstyled mb-6">
          <li class="mb-2">
            <span class="h6">DOB:</span>
            <span>{{ $appointment->patient->dob ? \Carbon\Carbon::parse($appointment->patient->dob)->format('d M Y') : 'N/A' }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Phone:</span>
            <span>{{ $appointment->patient->phone ?? 'N/A' }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Type:</span>
            <span class="badge bg-label-{{ $appointment->type === 'op' ? 'info' : 'danger' }}">
              {{ strtoupper($appointment->type) }}
            </span>
          </li>
          <li class="mb-2">
            <span class="h6">Token:</span>
            <span class="badge bg-label-primary">#{{ $appointment->token_number ?? 'N/A' }}</span>
          </li>
        </ul>

        <h5 class="pb-4 border-bottom mb-4">Doctor Details</h5>
        <div class="d-flex align-items-center gap-2 mb-2">
          <img src="{{ $appointment->doctor->photo_url }}" alt="{{ $appointment->doctor->name }}"
            class="rounded-circle" style="width:36px;height:36px;object-fit:cover;"
            onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
          <span class="fw-medium">Dr. {{ $appointment->doctor->name }}</span>
        </div>
        <ul class="list-unstyled mb-6">
          <li class="mb-2">
            <span class="h6">Doctor:</span>
            <span>Dr. {{ $appointment->doctor->name }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Specialization:</span>
            <span>{{ $appointment->doctor->specialization }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Qualification:</span>
            <span>{{ $appointment->doctor->qualification ?? 'N/A' }}</span>
          </li>
        </ul>

      </div>
    </div>

    {{-- Allergies Card --}}
    @if($appointment->patient->allergies->count() > 0)
    <div class="card mb-6 border border-danger">
      <div class="card-header bg-label-danger">
        <h6 class="mb-0 text-danger">
          <i class="icon-base ti tabler-alert-triangle me-2"></i>Known Allergies
        </h6>
      </div>
      <div class="card-body">
        @foreach($appointment->patient->allergies as $allergy)
        <div class="d-flex justify-content-between mb-2">
          <span>{{ $allergy->allergen }}</span>
          <span class="badge bg-label-{{ $allergy->severity === 'severe' ? 'danger' : ($allergy->severity === 'moderate' ? 'warning' : 'success') }}">
            {{ $allergy->severity }}
          </span>
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>
  {{-- /Left Sidebar --}}

  {{-- Right Content — Consultation Form --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    <form action="{{ route('consultations.store', $appointment->id) }}" method="POST">
      @csrf

      {{-- Vitals --}}
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ti tabler-activity me-2 text-primary"></i>Vitals</h5>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-4">
              <label class="form-label">Blood Pressure</label>
              <div class="input-group">
                <input type="text" class="form-control @error('blood_pressure') is-invalid @enderror" name="blood_pressure" placeholder="120/80" value="{{ old('blood_pressure') }}" />
                <span class="input-group-text">mmHg</span>
              </div>
              @error('blood_pressure') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Temperature</label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control @error('temperature') is-invalid @enderror" name="temperature" placeholder="98.6" value="{{ old('temperature') }}" />
                <span class="input-group-text">°F</span>
              </div>
              @error('temperature') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Pulse Rate</label>
              <div class="input-group">
                <input type="number" min="0" class="form-control @error('pulse_rate') is-invalid @enderror" name="pulse_rate" placeholder="72" value="{{ old('pulse_rate') }}" />
                <span class="input-group-text">bpm</span>
              </div>
              @error('pulse_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Weight</label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control @error('weight') is-invalid @enderror" name="weight" placeholder="70" value="{{ old('weight') }}" />
                <span class="input-group-text">kg</span>
              </div>
              @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Height</label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control @error('height') is-invalid @enderror" name="height" placeholder="170" value="{{ old('height') }}" />
                <span class="input-group-text">cm</span>
              </div>
              @error('height') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">SpO2</label>
              <div class="input-group">
                <input type="number" min="0" max="100" class="form-control @error('spo2') is-invalid @enderror" name="spo2" placeholder="98" value="{{ old('spo2') }}" />
                <span class="input-group-text">%</span>
              </div>
              @error('spo2') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
        </div>
      </div>

      {{-- Consultation Notes --}}
      <div class="card mb-6">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ti tabler-notes me-2 text-info"></i>Consultation Notes</h5>
        </div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Chief Complaint *</label>
            <textarea class="form-control @error('chief_complaint') is-invalid @enderror" name="chief_complaint" rows="3"
              placeholder="Patient's main complaint..." required>{{ old('chief_complaint') }}</textarea>
            @error('chief_complaint') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Diagnosis</label>
            <textarea class="form-control @error('diagnosis') is-invalid @enderror" name="diagnosis" rows="3"
              placeholder="Doctor's diagnosis...">{{ old('diagnosis') }}</textarea>
            @error('diagnosis') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">ICD-10 Diagnosis Code</label>
            <select class="form-select @error('icd10_code') is-invalid @enderror" name="icd10_code" id="icd10Select">
              @if(old('icd10_code'))
              <option value="{{ old('icd10_code') }}" selected>{{ old('icd10_code') }}</option>
              @endif
            </select>
            @error('icd10_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Treatment Plan</label>
            <textarea class="form-control @error('treatment_plan') is-invalid @enderror" name="treatment_plan" rows="3"
              placeholder="Treatment plan...">{{ old('treatment_plan') }}</textarea>
            @error('treatment_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Additional Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
              placeholder="Any additional notes...">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>

      {{-- Submit --}}
      <div class="d-flex gap-3 mb-6">
        <button type="submit" class="btn btn-primary">
          <i class="icon-base ti tabler-device-floppy me-1"></i> Save & Go to Prescription
        </button>
        <a href="{{ route('appointments.index') }}" class="btn btn-label-secondary">
          Cancel
        </a>
      </div>

    </form>

  </div>
  {{-- /Right Content --}}

</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#icd10Select').select2({
    placeholder: 'Search ICD-10 code or description...',
    allowClear: true,
    ajax: {
      url: '{{ route('icd10.search') }}',
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({ results: data.results }),
    },
    minimumInputLength: 1,
  });
});
</script>
@endsection

@endsection