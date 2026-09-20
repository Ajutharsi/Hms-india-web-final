@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Create Prescription - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')

<form action="{{ route('prescriptions.store', $consultation->id) }}" method="POST">
@csrf

@if ($errors->any())
<div class="alert alert-danger">
  <strong>Please fix the following:</strong>
  <ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul>
</div>
@endif

<div class="row invoice-add">

  {{-- Prescription Form --}}
  <div class="col-lg-9 col-12 mb-lg-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
          <div class="mb-md-0 mb-6">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-heartbeat text-primary" style="font-size:2rem;"></i>
              <span class="app-brand-text fw-bold fs-4">Hospital MS</span>
            </div>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-1">Sri Lanka 10001</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
            <dl class="row mb-0 gx-4">
              <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                <span class="h5 text-capitalize mb-0 text-nowrap">Prescription</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="RX-{{ date('Y') }}-AUTO" />
              </dd>
              <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Date:</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="{{ now()->format('d M Y') }}" />
              </dd>
              <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Appt ID:</span>
              </dt>
              <dd class="col-sm-7 mb-0">
                <input type="text" class="form-control" disabled value="{{ $consultation->appointment->appointment_id }}" />
              </dd>
            </dl>
          </div>
        </div>
      </div>

      {{-- Patient & Doctor Info --}}
      <div class="card-body px-0">
        <div class="row">
          <div class="col-md-6 mb-6 mb-md-0">
            <h6>Patient:</h6>
            <p class="mb-1 fw-medium">{{ $consultation->patient->name }}</p>
            <p class="mb-1 text-muted">MRN: {{ $consultation->patient->mrn }}</p>
            <p class="mb-1 text-muted">
              DOB: {{ $consultation->patient->dob ? \Carbon\Carbon::parse($consultation->patient->dob)->format('d M Y') : 'N/A' }}
            </p>
            <p class="mb-1 text-muted">Phone: {{ $consultation->patient->phone ?? 'N/A' }}</p>
            <p class="mb-0 text-muted">Blood Group: {{ $consultation->patient->blood_group ?? 'N/A' }}</p>
          </div>
          <div class="col-md-6">
            <h6>Doctor:</h6>
            <div class="d-flex align-items-center gap-2 mb-1">
              <img src="{{ $consultation->doctor->photo_url }}" alt="{{ $consultation->doctor->name }}"
                class="rounded-circle" style="width:32px;height:32px;object-fit:cover;"
                onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
              <p class="mb-0 fw-medium">Dr. {{ $consultation->doctor->name }}</p>
            </div>
            <p class="mb-1 text-muted">{{ $consultation->doctor->specialization }}</p>
            <p class="mb-1 text-muted">{{ $consultation->doctor->qualification ?? '' }}</p>
            @if($consultation->diagnosis)
            <div class="mt-3 p-3 bg-label-info rounded">
              <small class="fw-medium">Diagnosis:</small>
              <p class="mb-0 small">{{ $consultation->diagnosis }}</p>
            </div>
            @endif
          </div>
        </div>
      </div>

      <hr class="mt-0 mb-6" />

      {{-- Medicines --}}
      <div class="card-body pt-0 px-0">
        <h6 class="mb-4">Medicines</h6>

        <div id="medicinesContainer">
          <div class="medicine-row border rounded p-4 mb-4" id="medicine_0">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Medicine Name *</label>
                <input type="text" class="form-control @error('medicines.0.medicine_name') is-invalid @enderror" name="medicines[0][medicine_name]"
                  placeholder="e.g. Paracetamol 500mg" required />
                @error('medicines.0.medicine_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Dosage *</label>
                <input type="text" class="form-control @error('medicines.0.dosage') is-invalid @enderror" name="medicines[0][dosage]"
                  placeholder="e.g. 500mg" required />
                @error('medicines.0.dosage') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Frequency *</label>
                <select class="form-select @error('medicines.0.frequency') is-invalid @enderror" name="medicines[0][frequency]" required>
                  <option value="1-0-1">1-0-1</option>
                  <option value="1-1-1">1-1-1</option>
                  <option value="0-0-1">0-0-1</option>
                  <option value="1-0-0">1-0-0</option>
                  <option value="0-1-0">0-1-0</option>
                  <option value="SOS">SOS</option>
                  <option value="OD">OD</option>
                  <option value="BD">BD</option>
                  <option value="TDS">TDS</option>
                  <option value="QID">QID</option>
                </select>
                @error('medicines.0.frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Duration *</label>
                <input type="text" class="form-control @error('medicines.0.duration') is-invalid @enderror" name="medicines[0][duration]"
                  placeholder="e.g. 5 days" required />
                @error('medicines.0.duration') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Route</label>
                <select class="form-select" name="medicines[0][route]">
                  <option value="oral">Oral</option>
                  <option value="injection">Injection</option>
                  <option value="topical">Topical</option>
                  <option value="inhaler">Inhaler</option>
                  <option value="drops">Drops</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Instructions</label>
                <input type="text" class="form-control" name="medicines[0][instructions]"
                  placeholder="e.g. Take after food" />
              </div>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-sm btn-primary" onclick="addMedicine()">
          <i class="icon-base ti tabler-plus icon-xs me-1"></i> Add Medicine
        </button>
      </div>

      <hr class="my-6" />

      {{-- Notes --}}
      <div class="card-body px-0 pb-0">
        <label class="text-heading mb-2 fw-medium">Additional Notes:</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
          placeholder="Additional instructions for patient...">{{ old('notes') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

    </div>
  </div>

  {{-- Actions --}}
  <div class="col-lg-3 col-12 invoice-actions">
    <div class="card mb-6">
      <div class="card-body">
        <button type="submit" class="btn btn-primary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-device-floppy icon-xs me-2"></i>
            Save Prescription
          </span>
        </button>
        <a href="{{ route('appointments.index') }}" class="btn btn-label-secondary d-grid w-100">
          Cancel
        </a>
      </div>
    </div>

    {{-- Consultation Summary --}}
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Consultation Summary</h6>
      </div>
      <div class="card-body">
        @if($consultation->chief_complaint)
        <div class="mb-3">
          <small class="text-muted d-block">Chief Complaint</small>
          <p class="mb-0 small">{{ $consultation->chief_complaint }}</p>
        </div>
        @endif
        @if($consultation->diagnosis)
        <div class="mb-3">
          <small class="text-muted d-block">Diagnosis</small>
          <p class="mb-0 small">{{ $consultation->diagnosis }}</p>
        </div>
        @endif
        @if($consultation->blood_pressure)
        <div class="mb-2">
          <small class="text-muted">BP:</small>
          <span class="small ms-1">{{ $consultation->blood_pressure }}</span>
        </div>
        @endif
        @if($consultation->temperature)
        <div class="mb-2">
          <small class="text-muted">Temp:</small>
          <span class="small ms-1">{{ $consultation->temperature }}°F</span>
        </div>
        @endif
        @if($consultation->pulse_rate)
        <div class="mb-2">
          <small class="text-muted">Pulse:</small>
          <span class="small ms-1">{{ $consultation->pulse_rate }} bpm</span>
        </div>
        @endif
      </div>
    </div>
  </div>

</div>
</form>

<script>
let medicineCount = 1;

function addMedicine() {
  const container = document.getElementById('medicinesContainer');
  const i = medicineCount++;

  const html = `
    <div class="medicine-row border rounded p-4 mb-4" id="medicine_${i}">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Medicine Name *</label>
          <input type="text" class="form-control" name="medicines[${i}][medicine_name]"
            placeholder="e.g. Paracetamol 500mg" required />
        </div>
        <div class="col-md-2">
          <label class="form-label">Dosage *</label>
          <input type="text" class="form-control" name="medicines[${i}][dosage]"
            placeholder="e.g. 500mg" required />
        </div>
        <div class="col-md-2">
          <label class="form-label">Frequency *</label>
          <select class="form-select" name="medicines[${i}][frequency]" required>
            <option value="1-0-1">1-0-1</option>
            <option value="1-1-1">1-1-1</option>
            <option value="0-0-1">0-0-1</option>
            <option value="1-0-0">1-0-0</option>
            <option value="0-1-0">0-1-0</option>
            <option value="SOS">SOS</option>
            <option value="OD">OD</option>
            <option value="BD">BD</option>
            <option value="TDS">TDS</option>
            <option value="QID">QID</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Duration *</label>
          <input type="text" class="form-control" name="medicines[${i}][duration]"
            placeholder="e.g. 5 days" required />
        </div>
        <div class="col-md-2">
          <label class="form-label">Route</label>
          <select class="form-select" name="medicines[${i}][route]">
            <option value="oral">Oral</option>
            <option value="injection">Injection</option>
            <option value="topical">Topical</option>
            <option value="inhaler">Inhaler</option>
            <option value="drops">Drops</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Instructions</label>
          <div class="d-flex gap-2">
            <input type="text" class="form-control" name="medicines[${i}][instructions]"
              placeholder="e.g. Take after food" />
            <button type="button" class="btn btn-label-danger"
              onclick="removeMedicine(${i})">
              <i class="icon-base ti tabler-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  container.insertAdjacentHTML('beforeend', html);
}

function removeMedicine(i) {
  document.getElementById(`medicine_${i}`)?.remove();
}
</script>

@endsection