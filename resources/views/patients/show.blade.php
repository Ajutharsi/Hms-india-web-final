@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Patient Details - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/animate-css/animate.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-user-view.scss')
<style>
  .patient-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px dashed var(--bs-border-color);
  }
  .patient-detail-item:last-child { border-bottom: none; }
  .patient-detail-item .detail-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    min-width: 100px;
  }
  .patient-detail-item .detail-value {
    font-size: 0.875rem;
    color: var(--bs-heading-color);
    font-weight: 500;
    text-align: right;
  }

  .medical-record-card {
    background: var(--bs-card-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
    border-left: 3px solid var(--bs-primary);
  }
  .medical-record-card:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    transform: translateX(2px);
  }
  .medical-record-card.clickable {
    cursor: pointer;
  }
  .medical-record-card.clickable:hover {
    box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.15);
    transform: translateX(4px);
    border-left-color: var(--bs-primary);
  }
  .medical-record-card .record-type {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--bs-primary);
    margin-bottom: 0.35rem;
  }
  .medical-record-card .record-desc {
    font-size: 0.9rem;
    color: var(--bs-heading-color);
    font-weight: 500;
    margin-bottom: 0.35rem;
  }
  .medical-record-card .record-meta {
    font-size: 0.8rem;
    color: var(--bs-secondary-color);
  }
  .medical-record-card .record-date {
    font-size: 0.78rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
  }

  .section-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--bs-secondary-color);
    padding: 0.5rem 0;
    border-bottom: 2px solid var(--bs-border-color);
    margin-bottom: 0.75rem;
  }

  /* Modal footer — same padding/gap/alignment fix already applied to the
     dashboard's popups, so every modal on this page matches that convention. */
  .modal-footer {
    padding: 1rem 1.5rem !important;
    gap: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: space-between !important;
  }
  .modal-footer > * { margin: 0 !important; }
  .modal-footer .btn {
    padding: 0.55rem 1rem;
    font-weight: 600;
    border-radius: 8px;
  }
</style>
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Patients', 'url' => route('patients.index')],
  ['label' => $patient->name],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>
  {{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-4">
  <a href="{{ route('vitals.index', ['patient_id' => $patient->id]) }}" class="btn btn-label-primary btn-sm">
    <i class="icon-base ti tabler-activity me-1"></i> Record Vitals
  </a>
  <a href="{{ route('nursing.index', ['patient_id' => $patient->id]) }}" class="btn btn-label-info btn-sm">
    <i class="icon-base ti tabler-notes me-1"></i> Add Nursing Note
  </a>
  <a href="{{ route('radiology.index', ['patient_id' => $patient->id]) }}" class="btn btn-label-warning btn-sm">
    <i class="icon-base ti tabler-radioactive me-1"></i> Order Radiology
  </a>
  <a href="{{ route('emar.index', ['patient_id' => $patient->id]) }}" class="btn btn-label-success btn-sm">
    <i class="icon-base ti tabler-vaccine me-1"></i> Add eMAR
  </a>
</div>

<div class="row">

  {{-- Left Sidebar --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Patient Card --}}
    <div class="card mb-4">
      <div class="card-body pt-8">

        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-xl mb-3">
            <img src="{{ $patient->photo_url }}" alt="{{ $patient->name }}"
              class="rounded-circle" style="width:72px;height:72px;object-fit:cover;"
              onerror="this.onerror=null;this.src='{{ asset('images/defaults/patient.png') }}';" />
          </div>
          <h5 class="mb-1">{{ $patient->name }}</h5>
          <span class="text-muted small mb-2">{{ $patient->mrn }}</span>
          <div class="d-flex gap-1 flex-wrap justify-content-center">
            @if($patient->status === 'active')
              <span class="badge bg-label-success">Active</span>
            @elseif($patient->status === 'discharged')
              <span class="badge bg-label-secondary">Discharged</span>
            @else
              <span class="badge bg-label-danger">Deceased</span>
            @endif
            @if($patient->patient_type === 'op')
              <span class="badge bg-label-info">Out Patient</span>
            @else
              <span class="badge bg-label-warning">In Patient</span>
            @endif
          </div>
        </div>

        <div class="row text-center mb-4 mx-0 p-3 rounded" style="background:rgba(var(--bs-primary-rgb),0.04);">
          <div class="col-6 border-end">
            <i class="icon-base ti tabler-calendar text-primary mb-1" style="font-size:1.25rem;"></i>
            <h5 class="mb-0">{{ $patient->appointments->count() }}</h5>
            <small class="text-muted">Appointments</small>
          </div>
          <div class="col-6">
            <i class="icon-base ti tabler-file text-info mb-1" style="font-size:1.25rem;"></i>
            <h5 class="mb-0">{{ $patient->attachments->count() }}</h5>
            <small class="text-muted">Documents</small>
          </div>
        </div>

        <div class="section-title">Patient Details</div>
        <div class="mb-4">
          <div class="patient-detail-item">
            <span class="detail-label">DOB</span>
            <span class="detail-value">{{ $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('M d, Y') : 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Gender</span>
            <span class="detail-value text-capitalize">{{ $patient->gender }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Blood Group</span>
            <span class="detail-value">
              <span class="badge bg-label-danger">{{ $patient->blood_group ?? 'N/A' }}</span>
            </span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Phone</span>
            <span class="detail-value">{{ $patient->phone ?? 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Email</span>
            <span class="detail-value" style="font-size:0.8rem;">{{ $patient->email ?? 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Nationality</span>
            <span class="detail-value">{{ $patient->nationality ?? 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Marital</span>
            <span class="detail-value text-capitalize">{{ $patient->marital_status ?? 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Occupation</span>
            <span class="detail-value">{{ $patient->occupation ?? 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Address</span>
            <span class="detail-value">{{ $patient->address ?? 'N/A' }}</span>
          </div>
        </div>

        <div class="section-title">Emergency Contact</div>
        <div class="mb-4">
          <div class="patient-detail-item">
            <span class="detail-label">Name</span>
            <span class="detail-value">{{ $patient->emergency_contact_name ?? 'N/A' }}</span>
          </div>
          <div class="patient-detail-item">
            <span class="detail-label">Phone</span>
            <span class="detail-value">{{ $patient->emergency_contact ?? 'N/A' }}</span>
          </div>
        </div>

        <div class="d-flex justify-content-center gap-2">
          <a href="{{ route('patients.index') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ti tabler-arrow-left me-1"></i> Back
          </a>
          @can('edit patients')
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#offcanvasPatientEditShow">
            <i class="icon-base ti tabler-edit me-1"></i> Edit
          </button>
          @endcan
        </div>

      </div>
    </div>

    {{-- Allergies --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-alert-triangle me-2 text-warning"></i>Allergies
        </h6>
        @can('edit patients')
        <button class="btn btn-sm btn-label-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#addAllergyModal">
          <i class="icon-base ti tabler-plus icon-sm"></i>
        </button>
        @endcan
      </div>
      <div class="card-body py-3">
        @forelse($patient->allergies as $allergy)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div>
            <span class="fw-semibold small">{{ $allergy->allergen }}</span>
            <div class="mt-1">
              <span class="badge bg-label-secondary me-1" style="font-size:0.7rem;">{{ $allergy->type }}</span>
              <span class="badge bg-label-{{ $allergy->severity === 'severe' ? 'danger' : ($allergy->severity === 'moderate' ? 'warning' : 'success') }}" style="font-size:0.7rem;">
                {{ $allergy->severity }}
              </span>
            </div>
          </div>
          @can('edit patients')
          <button onclick="deleteItem('/allergies/{{ $allergy->id }}')"
            class="btn btn-sm btn-icon btn-label-danger">
            <i class="icon-base ti tabler-trash icon-sm"></i>
          </button>
          @endcan
        </div>
        @empty
        <p class="text-muted text-center mb-0 small py-2">No allergies recorded.</p>
        @endforelse
      </div>
    </div>

    {{-- Problems --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-stethoscope me-2 text-danger"></i>Problems
        </h6>
        @can('edit patients')
        <button class="btn btn-sm btn-label-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#addProblemModal">
          <i class="icon-base ti tabler-plus icon-sm"></i>
        </button>
        @endcan
      </div>
      <div class="card-body py-3">
        @forelse($patient->problems as $problem)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div>
            <span class="fw-semibold small">{{ $problem->problem_name }}</span>
            <div class="mt-1">
              @if($problem->onset_date)
              <span class="text-muted" style="font-size:0.75rem;">{{ \Carbon\Carbon::parse($problem->onset_date)->format('M Y') }} — </span>
              @endif
              <span class="badge bg-label-{{ $problem->status === 'active' ? 'danger' : ($problem->status === 'chronic' ? 'warning' : 'success') }}" style="font-size:0.7rem;">
                {{ $problem->status }}
              </span>
            </div>
          </div>
          @can('edit patients')
          <button onclick="deleteItem('/problems/{{ $problem->id }}')"
            class="btn btn-sm btn-icon btn-label-danger">
            <i class="icon-base ti tabler-trash icon-sm"></i>
          </button>
          @endcan
        </div>
        @empty
        <p class="text-muted text-center mb-0 small py-2">No problems recorded.</p>
        @endforelse
      </div>
    </div>

  </div>
  {{-- /Left Sidebar --}}

  {{-- Right Content --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-4 row-gap-2">
        <li class="nav-item">
          <a class="nav-link active" href="#medical-records" data-bs-toggle="pill">
            <i class="icon-base ti tabler-notes icon-sm me-1_5"></i>Medical History
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#attachments" data-bs-toggle="pill">
            <i class="icon-base ti tabler-paperclip icon-sm me-1_5"></i>Attachments
          </a>
        </li>
        <li class="nav-item">
  <a class="nav-link" href="#referrals" data-bs-toggle="pill">
    <i class="icon-base ti tabler-transfer icon-sm me-1_5"></i>Referrals
    @if($referrals->count() > 0)
    <span class="badge bg-label-primary ms-1">{{ $referrals->count() }}</span>
    @endif
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" href="#followups" data-bs-toggle="pill">
    <i class="icon-base ti tabler-calendar-repeat icon-sm me-1_5"></i>Follow Ups
    @if($followups->count() > 0)
    <span class="badge bg-label-success ms-1">{{ $followups->count() }}</span>
    @endif
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-vitals-tab" href="#tab-vitals" data-bs-toggle="pill">
    <i class="icon-base ti tabler-activity icon-sm me-1_5"></i>Vitals
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-emar-tab" href="#tab-emar" data-bs-toggle="pill">
    <i class="icon-base ti tabler-vaccine icon-sm me-1_5"></i>eMAR
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-nursing-tab" href="#tab-nursing" data-bs-toggle="pill">
    <i class="icon-base ti tabler-notes icon-sm me-1_5"></i>Nursing Notes
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-radiology-tab" href="#tab-radiology" data-bs-toggle="pill">
    <i class="icon-base ti tabler-radioactive icon-sm me-1_5"></i>Radiology
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-insurance-tab" href="#tab-insurance" data-bs-toggle="pill">
    <i class="icon-base ti tabler-shield-check icon-sm me-1_5"></i>Insurance
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-deposits-tab" href="#tab-deposits" data-bs-toggle="pill">
    <i class="icon-base ti tabler-cash icon-sm me-1_5"></i>IPD Deposits
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" id="pill-ot-tab" href="#tab-ot" data-bs-toggle="pill">
    <i class="icon-base ti tabler-scissors icon-sm me-1_5"></i>OT History
  </a>
</li>
@if($showIcuTab)
<li class="nav-item">
  <a class="nav-link" id="pill-icu-tab" href="#tab-icu" data-bs-toggle="pill">
    <i class="icon-base ti tabler-heartbeat icon-sm me-1_5"></i>ICU Charts
  </a>
</li>
@endif
<li class="nav-item">
  <a class="nav-link" id="pill-blood-tab" href="#tab-blood" data-bs-toggle="pill">
    <i class="icon-base ti tabler-droplet icon-sm me-1_5"></i>Blood Transfusions
  </a>
</li>
      </ul>
    </div>

    <div class="tab-content">

      {{-- Medical Records Tab --}}
      <div class="tab-pane fade show active" id="medical-records">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0">Medical History</h6>
            @can('edit patients')
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMedicalRecordModal">
              <i class="icon-base ti tabler-plus me-1"></i> Add Record
            </button>
            @endcan
          </div>
          <div class="card-body">
            @forelse($patient->medicalRecords->sortByDesc('record_date') as $record)
            <div class="medical-record-card {{ $record->reference_id ? 'clickable' : '' }}"
              @if($record->reference_type === 'prescription' && $record->reference_id)
                onclick="window.location.href='/prescriptions/{{ $record->reference_id }}'"
              @elseif($record->reference_type === 'lab_order' && $record->reference_id)
                onclick="window.location.href='/lab/orders/{{ $record->reference_id }}'"
              @endif
            >
              <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1 me-3">
                  <div class="record-type">
                    <i class="icon-base ti tabler-tag icon-sm me-1"></i>{{ $record->record_type }}
                    @if($record->reference_id)
                      <i class="icon-base ti tabler-external-link icon-sm ms-1 text-primary"></i>
                    @endif
                  </div>
                  <div class="record-desc">{{ $record->description }}</div>
                  @if($record->doctor_name)
                  <div class="record-meta">
                    <i class="icon-base ti tabler-user icon-sm me-1"></i>Dr. {{ $record->doctor_name }}
                  </div>
                  @endif
                  @if($record->notes)
                  <div class="record-meta mt-1">
                    <i class="icon-base ti tabler-note icon-sm me-1"></i>{{ $record->notes }}
                  </div>
                  @endif
                </div>
                <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
                  <span class="record-date">
                    <i class="icon-base ti tabler-calendar icon-sm me-1"></i>
                    {{ \Carbon\Carbon::parse($record->record_date)->format('d M Y') }}
                  </span>
                  @can('edit patients')
                  <button onclick="event.stopPropagation(); deleteItem('/medical-records/{{ $record->id }}')"
                    class="btn btn-sm btn-icon btn-label-danger">
                    <i class="icon-base ti tabler-trash icon-sm"></i>
                  </button>
                  @endcan
                </div>
              </div>
            </div>
            @empty
            <div class="text-center py-6">
              <i class="icon-base ti tabler-notes text-muted" style="font-size:3rem;"></i>
              <p class="text-muted mt-2">No medical records found.</p>
            </div>
            @endforelse
          </div>
        </div>
      </div>
      {{-- Referrals Tab --}}
<div class="tab-pane fade" id="referrals">
  <div class="card">
    <div class="card-header py-3">
      <h6 class="mb-0">Referrals</h6>
    </div>
    <div class="card-body p-0">
      @forelse($referrals as $referral)
      <div class="d-flex justify-content-between align-items-start p-3 border-bottom">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-label-secondary">{{ $referral->referral_no }}</span>
            <span class="badge bg-label-{{ $referral->priority === 'emergency' ? 'danger' : ($referral->priority === 'urgent' ? 'warning' : 'secondary') }} text-capitalize">
              {{ $referral->priority }}
            </span>
            <span class="badge bg-label-{{ $referral->status === 'completed' ? 'success' : ($referral->status === 'accepted' ? 'info' : ($referral->status === 'cancelled' ? 'secondary' : 'warning')) }} text-capitalize">
              {{ $referral->status }}
            </span>
          </div>
          <p class="mb-1 small fw-medium">
            <i class="icon-base ti tabler-arrow-right me-1 text-primary"></i>
            Dr. {{ $referral->referredBy->name ?? 'N/A' }}
            <i class="icon-base ti tabler-arrow-right mx-1"></i>
            Dr. {{ $referral->referredTo->name ?? 'N/A' }}
          </p>
          <p class="mb-0 small text-muted">{{ $referral->reason }}</p>
        </div>
        <small class="text-muted flex-shrink-0">{{ \Carbon\Carbon::parse($referral->referral_date)->format('d M Y') }}</small>
      </div>
      @empty
      <div class="text-center py-5">
        <i class="icon-base ti tabler-transfer text-muted" style="font-size:2.5rem;"></i>
        <p class="text-muted mt-2">No referrals found.</p>
      </div>
      @endforelse
    </div>
  </div>
</div>

{{-- Follow Ups Tab --}}
<div class="tab-pane fade" id="followups">
  <div class="card">
    <div class="card-header py-3">
      <h6 class="mb-0">Follow Up Appointments</h6>
    </div>
    <div class="card-body p-0">
      @forelse($followups as $appt)
      <div class="d-flex justify-content-between align-items-start p-3 border-bottom">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-label-secondary">{{ $appt->appointment_id }}</span>
            <span class="badge bg-label-{{ $appt->status === 'completed' ? 'success' : ($appt->status === 'cancelled' ? 'danger' : ($appt->status === 'no_show' ? 'secondary' : 'warning')) }} text-capitalize">
              {{ $appt->status }}
            </span>
          </div>
          <p class="mb-1 small fw-medium d-flex align-items-center gap-2">
            @if($appt->doctor)
            <img src="{{ $appt->doctor->photo_url }}" alt="" class="rounded-circle" style="width:20px;height:20px;object-fit:cover;"
              onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
            @else
            <i class="icon-base ti tabler-stethoscope text-primary"></i>
            @endif
            Dr. {{ $appt->doctor->name ?? 'N/A' }}
            @if($appt->doctor?->specialization)
            <span class="text-muted">— {{ $appt->doctor->specialization }}</span>
            @endif
          </p>
          @if($appt->notes)
          <p class="mb-0 small text-muted">{{ $appt->notes }}</p>
          @endif
        </div>
        <div class="text-end flex-shrink-0">
          <small class="text-muted d-block">{{ \Carbon\Carbon::parse($appt->appointment_date)->format('d M Y') }}</small>
          <small class="text-muted">{{ \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') }}</small>
        </div>
      </div>
      @empty
      <div class="text-center py-5">
        <i class="icon-base ti tabler-calendar-repeat text-muted" style="font-size:2.5rem;"></i>
        <p class="text-muted mt-2">No follow up appointments found.</p>
      </div>
      @endforelse
    </div>
  </div>
</div>

{{-- Vitals Tab --}}
<div class="tab-pane fade" id="tab-vitals">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">Vitals</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientVitalsTable">
        <thead><tr><th>Time</th><th>BP</th><th>Temp</th><th>Pulse</th><th>SpO2</th><th>NEWS</th><th>Recorded By</th></tr></thead>
      </table>
    </div>
  </div>
</div>

{{-- eMAR Tab --}}
<div class="tab-pane fade" id="tab-emar">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">eMAR — Current Medications</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientEmarTable">
        <thead><tr><th>Scheduled</th><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Status</th></tr></thead>
      </table>
    </div>
  </div>
</div>

{{-- Nursing Notes Tab --}}
<div class="tab-pane fade" id="tab-nursing">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">Nursing Notes</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientNursingTable">
        <thead><tr><th>Time</th><th>Type</th><th>Note</th><th>Written By</th></tr></thead>
      </table>
    </div>
  </div>
</div>

{{-- Radiology Tab --}}
<div class="tab-pane fade" id="tab-radiology">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">Radiology Orders</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientRadiologyTable">
        <thead><tr><th>Date</th><th>Study</th><th>Doctor</th><th>Status</th></tr></thead>
      </table>
    </div>
  </div>
</div>

{{-- Insurance Tab --}}
<div class="tab-pane fade" id="tab-insurance">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">Insurance Claims</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientInsuranceTable">
        <thead><tr><th>Claim No</th><th>Insurer</th><th>Claim Amount</th><th>Status</th><th>Date</th></tr></thead>
      </table>
    </div>
  </div>
</div>

{{-- IPD Deposits Tab --}}
<div class="tab-pane fade" id="tab-deposits">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">IPD Deposits</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientDepositsTable">
        <thead><tr><th>Deposit No</th><th>Amount</th><th>Type</th><th>Balance</th><th>Date</th></tr></thead>
      </table>
    </div>
  </div>
</div>

{{-- OT History Tab --}}
<div class="tab-pane fade" id="tab-ot">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">OT History</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientOtTable">
        <thead><tr><th>OT No</th><th>Procedure</th><th>Surgeon</th><th>Scheduled</th><th>Status</th></tr></thead>
      </table>
    </div>
  </div>
</div>

@if($showIcuTab)
{{-- ICU Charts Tab --}}
<div class="tab-pane fade" id="tab-icu">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">ICU Charts</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientIcuTable">
        <thead><tr><th>Time</th><th>GCS</th><th>Ventilator</th><th>Recorded By</th></tr></thead>
      </table>
    </div>
  </div>
</div>
@endif

{{-- Blood Transfusions Tab --}}
<div class="tab-pane fade" id="tab-blood">
  <div class="card">
    <div class="card-header py-3"><h6 class="mb-0">Blood Transfusions</h6></div>
    <div class="card-datatable table-responsive">
      <table class="table border-top" id="patientBloodTable">
        <thead><tr><th>Transfusion No</th><th>Blood Group</th><th>Indication</th><th>Status</th><th>Started</th></tr></thead>
      </table>
    </div>
  </div>
</div>

      {{-- Attachments Tab --}}
      <div class="tab-pane fade" id="attachments">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0">Documents & Files</h6>
            @can('edit patients')
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAttachmentModal">
              <i class="icon-base ti tabler-upload me-1"></i> Upload File
            </button>
            @endcan
          </div>
          <div class="card-body">
            @forelse($patient->attachments as $attachment)
            <div class="d-flex justify-content-between align-items-center p-3 mb-3 border rounded">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                  <div class="avatar-initial rounded bg-label-{{ str_contains($attachment->file_type, 'image') ? 'success' : 'primary' }}">
                    <i class="icon-base ti {{ str_contains($attachment->file_type, 'image') ? 'tabler-photo' : 'tabler-file-text' }} icon-lg"></i>
                  </div>
                </div>
                <div>
                  <h6 class="mb-0 small fw-semibold">{{ $attachment->file_name }}</h6>
                  <small class="text-muted">{{ $attachment->file_size }} • {{ $attachment->created_at->format('d M Y') }}</small>
                  @if($attachment->description)
                  <p class="mb-0 small text-muted">{{ $attachment->description }}</p>
                  @endif
                </div>
              </div>
              <div class="d-flex gap-2">
                <a href="{{ route('patients.attachments.download', $attachment->id) }}"
                  class="btn btn-sm btn-icon btn-label-info">
                  <i class="icon-base ti tabler-download"></i>
                </a>
                @can('edit patients')
                <button onclick="deleteItem('/attachments/{{ $attachment->id }}')"
                  class="btn btn-sm btn-icon btn-label-danger">
                  <i class="icon-base ti tabler-trash"></i>
                </button>
                @endcan
              </div>
            </div>
            @empty
            <div class="text-center py-6">
              <i class="icon-base ti tabler-paperclip text-muted" style="font-size:3rem;"></i>
              <p class="text-muted mt-2">No files uploaded.</p>
            </div>
            @endforelse
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

{{-- Modals --}}

{{-- Add Medical Record --}}
<div class="modal fade" id="addMedicalRecordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Medical Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('patients.medical-records.store', $patient->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label">Record Type *</label>
            <select class="form-select @error('record_type') is-invalid @enderror" name="record_type" required>
              <option value="">Select Type</option>
              @foreach (['Diagnosis','Treatment','Surgery','Lab Result','Prescription','Vaccination','Other'] as $rt)
              <option value="{{ $rt }}" @selected(old('record_type')==$rt)>{{ $rt }}</option>
              @endforeach
            </select>
            @error('record_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Date *</label>
            <input type="date" class="form-control @error('record_date') is-invalid @enderror" name="record_date" value="{{ old('record_date') }}" required />
            @error('record_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Description *</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" required>{{ old('description') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Doctor Name</label>
            <input type="text" class="form-control @error('doctor_name') is-invalid @enderror" name="doctor_name" value="{{ old('doctor_name') }}" />
            @error('doctor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('record_type') ? old('notes') : '' }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Allergy --}}
<div class="modal fade" id="addAllergyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Allergy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('patients.allergies.store', $patient->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label">Allergen *</label>
            <input type="text" class="form-control @error('allergen') is-invalid @enderror" name="allergen" placeholder="e.g. Penicillin, Peanuts" value="{{ old('allergen') }}" required />
            @error('allergen') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Type *</label>
            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
              <option value="food" @selected(old('type')=='food')>Food</option>
              <option value="drug" @selected(old('type')=='drug')>Drug</option>
              <option value="environmental" @selected(old('type')=='environmental')>Environmental</option>
              <option value="other" @selected(old('type')=='other')>Other</option>
            </select>
            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Severity *</label>
            <select class="form-select @error('severity') is-invalid @enderror" name="severity" required>
              <option value="mild" @selected(old('severity')=='mild')>Mild</option>
              <option value="moderate" @selected(old('severity')=='moderate')>Moderate</option>
              <option value="severe" @selected(old('severity')=='severe')>Severe</option>
            </select>
            @error('severity') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Reaction</label>
            <textarea class="form-control @error('reaction') is-invalid @enderror" name="reaction" rows="2">{{ old('reaction') }}</textarea>
            @error('reaction') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Add Allergy</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Problem --}}
<div class="modal fade" id="addProblemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Problem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('patients.problems.store', $patient->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label">Problem Name *</label>
            <input type="text" class="form-control @error('problem_name') is-invalid @enderror" name="problem_name" placeholder="e.g. Hypertension, Diabetes" value="{{ old('problem_name') }}" required />
            @error('problem_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Onset Date</label>
            <input type="date" class="form-control @error('onset_date') is-invalid @enderror" name="onset_date" value="{{ old('onset_date') }}" />
            @error('onset_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Status *</label>
            <select class="form-select @error('status') is-invalid @enderror" name="status" required>
              <option value="active" @selected(old('status', 'active')=='active')>Active</option>
              <option value="chronic" @selected(old('status')=='chronic')>Chronic</option>
              <option value="resolved" @selected(old('status')=='resolved')>Resolved</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('problem_name') ? old('notes') : '' }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Add Problem</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Attachment --}}
<div class="modal fade" id="addAttachmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upload File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('patients.attachments.store', $patient->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="mb-4">
            <label class="form-label">File * (JPG, PNG, PDF, DOC — Max 10MB)</label>
            <input type="file" class="form-control @error('file') is-invalid @enderror" name="file"
              accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required />
            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Description</label>
            <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" placeholder="e.g. X-Ray report" value="{{ old('description') }}" />
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Patient Offcanvas (detail page) --}}
@can('edit patients')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPatientEditShow" style="width:500px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Patient</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('patients.update', $patient->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="mb-4 text-center">
        <img id="show_edit_patient_photo_preview" src="{{ $patient->photo_url }}" alt="Photo preview"
          class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:2px solid var(--bs-border-color);"
          onerror="this.onerror=null;this.src='{{ asset('images/defaults/patient.png') }}';" />
        <label class="form-label d-block">Patient Photo</label>
        <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept=".jpg,.jpeg,.png,.webp"
          onchange="previewPatientShowPhoto(this, 'show_edit_patient_photo_preview')" />
        @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <small class="text-muted">JPG, PNG or WEBP. Max 2MB. Leave empty to keep the current photo.</small>
      </div>
      <div class="mb-4">
        <label class="form-label">Full Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $patient->name) }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" value="{{ old('dob', $patient->dob) }}" />
        @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Gender *</label>
        <select class="form-select @error('gender') is-invalid @enderror" name="gender" required>
          <option value="male" @selected(old('gender', $patient->gender)=='male')>Male</option>
          <option value="female" @selected(old('gender', $patient->gender)=='female')>Female</option>
          <option value="other" @selected(old('gender', $patient->gender)=='other')>Other</option>
        </select>
        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Blood Group</label>
        <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group">
          <option value="">Select</option>
          @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
            <option value="{{ $bg }}" @selected(old('blood_group', $patient->blood_group)==$bg)>{{ $bg }}</option>
          @endforeach
        </select>
        @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $patient->phone) }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $patient->email) }}" />
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Address</label>
        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2">{{ old('address', $patient->address) }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Emergency Contact Name</label>
        <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}" />
        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Emergency Contact Number</label>
        <input type="tel" class="form-control @error('emergency_contact') is-invalid @enderror" name="emergency_contact" value="{{ old('emergency_contact', $patient->emergency_contact) }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('emergency_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Nationality</label>
        <input type="text" class="form-control @error('nationality') is-invalid @enderror" name="nationality" value="{{ old('nationality', $patient->nationality) }}" />
        @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Marital Status</label>
        <select class="form-select @error('marital_status') is-invalid @enderror" name="marital_status">
          <option value="">Select</option>
          <option value="single" @selected(old('marital_status', $patient->marital_status)=='single')>Single</option>
          <option value="married" @selected(old('marital_status', $patient->marital_status)=='married')>Married</option>
          <option value="divorced" @selected(old('marital_status', $patient->marital_status)=='divorced')>Divorced</option>
          <option value="widowed" @selected(old('marital_status', $patient->marital_status)=='widowed')>Widowed</option>
        </select>
        @error('marital_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Occupation</label>
        <input type="text" class="form-control @error('occupation') is-invalid @enderror" name="occupation" value="{{ old('occupation', $patient->occupation) }}" />
        @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Patient Type *</label>
        <select class="form-select @error('patient_type') is-invalid @enderror" name="patient_type" required>
          <option value="op" @selected(old('patient_type', $patient->patient_type)=='op')>Out Patient (OP)</option>
          <option value="ip" @selected(old('patient_type', $patient->patient_type)=='ip')>In Patient (IP)</option>
        </select>
        @error('patient_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-6">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status">
          <option value="active" @selected(old('status', $patient->status)=='active')>Active</option>
          <option value="discharged" @selected(old('status', $patient->status)=='discharged')>Discharged</option>
          <option value="deceased" @selected(old('status', $patient->status)=='deceased')>Deceased</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="icon-base ti tabler-device-floppy me-1"></i> Update
        </button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endcan

<script>
function deleteItem(url) {
  confirmDelete(url, 'Item');
}

function previewPatientShowPhoto(input, previewId) {
  const img = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; };
    reader.readAsDataURL(input.files[0]);
  } else {
    img.src = '{{ asset('images/defaults/patient.png') }}';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const patientId = {{ $patient->id }};
  const noProcessingLang = { processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' };

  function initPatientTab(pillSelector, tableSelector, url, columns) {
    initLazyTab(pillSelector, function () {
      $(tableSelector).DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthChange: false,
        pageLength: 10,
        ajax: { url: url, type: 'GET', data: function (d) { d.patient_id = patientId; } },
        columns: columns,
        language: noProcessingLang,
      });
    });
  }

  initPatientTab('#pill-vitals-tab', '#patientVitalsTable', '{{ route('vitals.data') }}', [
    { data: 'recorded_at', name: 'recorded_at' },
    { data: 'blood_pressure', name: 'blood_pressure', defaultContent: '—' },
    { data: 'temperature', name: 'temperature', defaultContent: '—' },
    { data: 'pulse_rate', name: 'pulse_rate', defaultContent: '—' },
    { data: 'spo2', name: 'spo2', defaultContent: '—' },
    { data: 'news_score', name: 'news_score', defaultContent: '—' },
    { data: 'recorded_by_name', name: 'recorded_by_name', orderable: false },
  ]);

  initPatientTab('#pill-emar-tab', '#patientEmarTable', '{{ route('emar.data') }}', [
    { data: 'scheduled_time', name: 'scheduled_time', defaultContent: '—' },
    { data: 'drug_display', name: 'drug_display' },
    { data: 'dosage', name: 'dosage' },
    { data: 'frequency_display', name: 'frequency_display' },
    { data: 'status_badge', name: 'status', orderable: false },
  ]);

  initPatientTab('#pill-nursing-tab', '#patientNursingTable', '{{ route('nursing.data') }}', [
    { data: 'noted_at', name: 'noted_at' },
    { data: 'note_type', name: 'note_type' },
    { data: 'note_preview', name: 'note' },
    { data: 'written_by_name', name: 'written_by_name', orderable: false },
  ]);

  initPatientTab('#pill-radiology-tab', '#patientRadiologyTable', '{{ route('radiology.data') }}', [
    { data: 'order_date', name: 'order_date' },
    { data: 'study_info', name: 'study_info' },
    { data: 'doctor_name', name: 'doctor_name', orderable: false },
    { data: 'status', name: 'status' },
  ]);

  initPatientTab('#pill-insurance-tab', '#patientInsuranceTable', '{{ route('insurance.data') }}', [
    { data: 'claim_no', name: 'claim_no' },
    { data: 'insurance_company', name: 'insurance_company' },
    { data: 'claim_amount', name: 'claim_amount' },
    { data: 'status', name: 'status' },
    { data: 'created_at', name: 'created_at' },
  ]);

  initPatientTab('#pill-deposits-tab', '#patientDepositsTable', '{{ route('ipd.deposits.data') }}', [
    { data: 'deposit_no', name: 'deposit_no' },
    { data: 'amount', name: 'amount' },
    { data: 'type', name: 'type' },
    { data: 'balance', name: 'balance', orderable: false },
    { data: 'deposited_at', name: 'deposited_at' },
  ]);

  initPatientTab('#pill-ot-tab', '#patientOtTable', '{{ route('ot.data') }}', [
    { data: 'ot_no', name: 'ot_no' },
    { data: 'procedure_name', name: 'procedure_name' },
    { data: 'surgeon_name', name: 'surgeon_name', orderable: false },
    { data: 'scheduled_start', name: 'scheduled_start' },
    { data: 'status_badge', name: 'status', orderable: false },
  ]);

  @if($showIcuTab)
  initPatientTab('#pill-icu-tab', '#patientIcuTable', '{{ route('icu.data') }}', [
    { data: 'charted_at', name: 'charted_at' },
    { data: 'gcs_total', name: 'gcs_total', defaultContent: '—' },
    { data: 'ventilator_mode', name: 'ventilator_mode', defaultContent: '—' },
    { data: 'recorded_by_name', name: 'recorded_by_name', orderable: false },
  ]);
  @endif

  initPatientTab('#pill-blood-tab', '#patientBloodTable', '{{ route('blood-bank.transfusions.data') }}', [
    { data: 'transfusion_no', name: 'transfusion_no' },
    { data: 'blood_group', name: 'blood_group', orderable: false },
    { data: 'indication', name: 'indication', defaultContent: '—' },
    { data: 'status_badge', name: 'status', orderable: false },
    { data: 'started_at', name: 'started_at' },
  ]);
});

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('record_type'))
    new bootstrap.Modal(document.getElementById('addMedicalRecordModal')).show();
  @elseif(old('allergen'))
    new bootstrap.Modal(document.getElementById('addAllergyModal')).show();
  @elseif(old('problem_name'))
    new bootstrap.Modal(document.getElementById('addProblemModal')).show();
  @elseif($errors->has('file'))
    new bootstrap.Modal(document.getElementById('addAttachmentModal')).show();
  @elseif($errors->has('name') || $errors->has('gender') || $errors->has('patient_type') || $errors->has('photo'))
    new bootstrap.Offcanvas(document.getElementById('offcanvasPatientEditShow')).show();
  @endif
});
@endif
</script>

@endsection