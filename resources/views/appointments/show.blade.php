@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Appointment Details - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-user-view.scss')
<style>
  .appt-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px dashed var(--bs-border-color);
  }
  .appt-detail-item:last-child { border-bottom: none; }
  .appt-detail-item .detail-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    min-width: 100px;
  }
  .appt-detail-item .detail-value {
    font-size: 0.875rem;
    color: var(--bs-heading-color);
    font-weight: 500;
    text-align: right;
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
  .timeline-card {
    background: var(--bs-card-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 0.75rem;
    border-left: 3px solid var(--bs-primary);
  }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

  {{-- Left Sidebar --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Appointment Card --}}
    <div class="card mb-4">
      <div class="card-body pt-8">

        {{-- Avatar --}}
        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-xl mb-3">
            <span class="avatar-initial rounded-circle bg-label-primary"
              style="font-size:2rem;width:72px;height:72px;">
              {{ strtoupper(substr($appointment->patient->name, 0, 1)) }}
            </span>
          </div>
          <h5 class="mb-1">{{ $appointment->patient->name }}</h5>
          <span class="text-muted small mb-2">{{ $appointment->patient->mrn }}</span>
          <div class="d-flex gap-1 flex-wrap justify-content-center">
            @php
              $statusColors = [
                'scheduled' => 'warning',
                'completed' => 'success',
                'cancelled' => 'danger',
                'no_show'   => 'secondary',
              ];
            @endphp
            <span class="badge bg-label-{{ $statusColors[$appointment->status] ?? 'secondary' }}">
              {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
            </span>
            <span class="badge bg-label-{{ $appointment->type === 'op' ? 'info' : 'danger' }}">
              {{ strtoupper($appointment->type) }}
            </span>
          </div>
        </div>

        {{-- Stats --}}
        <div class="row text-center mb-4 mx-0 p-3 rounded"
          style="background:rgba(var(--bs-primary-rgb),0.04);">
          <div class="col-6 border-end">
            <i class="icon-base ti tabler-calendar-check text-primary mb-1" style="font-size:1.25rem;"></i>
            <h5 class="mb-0">{{ $appointment->appointment_id }}</h5>
            <small class="text-muted">Appt ID</small>
          </div>
          <div class="col-6">
            <i class="icon-base ti tabler-hash text-warning mb-1" style="font-size:1.25rem;"></i>
            <h5 class="mb-0">#{{ $appointment->token_number ?? 'N/A' }}</h5>
            <small class="text-muted">Token</small>
          </div>
        </div>

        {{-- Appointment Details --}}
        <div class="section-title">Appointment Details</div>
        <div class="mb-4">
          <div class="appt-detail-item">
            <span class="detail-label">Date</span>
            <span class="detail-value">
              {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}
            </span>
          </div>
          <div class="appt-detail-item">
            <span class="detail-label">Time</span>
            <span class="detail-value">
              {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
            </span>
          </div>
          <div class="appt-detail-item">
            <span class="detail-label">Type</span>
            <span class="detail-value">
              <span class="badge bg-label-{{ $appointment->type === 'op' ? 'info' : 'danger' }}">
                {{ $appointment->type === 'op' ? 'Out Patient' : 'Emergency' }}
              </span>
            </span>
          </div>
          <div class="appt-detail-item">
            <span class="detail-label">Status</span>
            <span class="detail-value">
              <span class="badge bg-label-{{ $statusColors[$appointment->status] ?? 'secondary' }}">
                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
              </span>
            </span>
          </div>
          @if($appointment->notes)
          <div class="appt-detail-item">
            <span class="detail-label">Notes</span>
            <span class="detail-value" style="font-size:0.8rem;">{{ $appointment->notes }}</span>
          </div>
          @endif
        </div>

        {{-- Doctor Details --}}
        <div class="section-title">Doctor Details</div>
        <div class="d-flex align-items-center gap-2 mb-3">
          <img src="{{ $appointment->doctor->photo_url }}" alt="{{ $appointment->doctor->name }}"
            class="rounded-circle" style="width:40px;height:40px;object-fit:cover;"
            onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
          <span class="fw-medium">Dr. {{ $appointment->doctor->name }}</span>
        </div>
        <div class="mb-4">
          <div class="appt-detail-item">
            <span class="detail-label">Name</span>
            <span class="detail-value">Dr. {{ $appointment->doctor->name }}</span>
          </div>
          <div class="appt-detail-item">
            <span class="detail-label">Specialization</span>
            <span class="detail-value">{{ $appointment->doctor->specialization }}</span>
          </div>
          <div class="appt-detail-item">
            <span class="detail-label">Qualification</span>
            <span class="detail-value">{{ $appointment->doctor->qualification ?? 'N/A' }}</span>
          </div>
          <div class="appt-detail-item">
            <span class="detail-label">Fee</span>
            <span class="detail-value">LKR {{ number_format($appointment->doctor->consultation_fee, 2) }}</span>
          </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <a href="{{ route('appointments.index') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ti tabler-arrow-left me-1"></i> Back
          </a>
          <a href="{{ route('patients.show', $appointment->patient_id) }}" class="btn btn-label-info btn-sm">
            <i class="icon-base ti tabler-user me-1"></i> Patient
          </a>
          @role('super_admin|admin|receptionist')
          @if($appointment->status === 'scheduled')
          <a href="{{ route('consultations.create', $appointment->id) }}" class="btn btn-primary btn-sm">
            <i class="icon-base ti tabler-stethoscope me-1"></i> Consult
          </a>
          @endif
          @endrole
        </div>

      </div>
    </div>

    {{-- Patient Quick Info Card --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-user me-2 text-primary"></i>Patient Info
        </h6>
      </div>
      <div class="card-body py-3">
        <div class="appt-detail-item">
          <span class="detail-label">DOB</span>
          <span class="detail-value">
            {{ $appointment->patient->dob ? \Carbon\Carbon::parse($appointment->patient->dob)->format('d M Y') : 'N/A' }}
          </span>
        </div>
        <div class="appt-detail-item">
          <span class="detail-label">Gender</span>
          <span class="detail-value text-capitalize">{{ $appointment->patient->gender }}</span>
        </div>
        <div class="appt-detail-item">
          <span class="detail-label">Blood Group</span>
          <span class="detail-value">
            <span class="badge bg-label-danger">{{ $appointment->patient->blood_group ?? 'N/A' }}</span>
          </span>
        </div>
        <div class="appt-detail-item">
          <span class="detail-label">Phone</span>
          <span class="detail-value">{{ $appointment->patient->phone ?? 'N/A' }}</span>
        </div>
        <div class="appt-detail-item">
          <span class="detail-label">Allergies</span>
          <span class="detail-value">
            @if($appointment->patient->allergies->count() > 0)
              <span class="badge bg-label-danger">{{ $appointment->patient->allergies->count() }} Known</span>
            @else
              <span class="text-muted">None</span>
            @endif
          </span>
        </div>
      </div>
    </div>

  </div>
  {{-- /Left Sidebar --}}

  {{-- Right Content --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    {{-- Tabs --}}
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-4 row-gap-2">
        <li class="nav-item">
          <a class="nav-link active" href="#consultation" data-bs-toggle="pill">
            <i class="icon-base ti tabler-stethoscope icon-sm me-1_5"></i>Consultation
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#prescription" data-bs-toggle="pill">
            <i class="icon-base ti tabler-pill icon-sm me-1_5"></i>Prescription
          </a>
        </li>
      </ul>
    </div>

    <div class="tab-content">

      {{-- Consultation Tab --}}
      <div class="tab-pane fade show active" id="consultation">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0">
              <i class="icon-base ti tabler-stethoscope me-2 text-primary"></i>Consultation Notes
            </h6>
            @if(!$appointment->consultation)
              @role('super_admin|admin|doctor')
              <a href="{{ route('consultations.create', $appointment->id) }}"
                class="btn btn-primary btn-sm">
                <i class="icon-base ti tabler-plus me-1"></i> Add Consultation
              </a>
              @endrole
            @endif
          </div>
          <div class="card-body">
            @if($appointment->consultation)
              {{-- Vitals --}}
              @if($appointment->consultation->blood_pressure || $appointment->consultation->temperature)
              <div class="mb-4">
                <div class="section-title">Vitals</div>
                <div class="row g-3">
                  @if($appointment->consultation->blood_pressure)
                  <div class="col-6 col-md-4">
                    <div class="p-3 border rounded text-center">
                      <i class="icon-base ti tabler-activity text-danger mb-1" style="font-size:1.25rem;"></i>
                      <div class="fw-bold">{{ $appointment->consultation->blood_pressure }}</div>
                      <small class="text-muted">mmHg (BP)</small>
                    </div>
                  </div>
                  @endif
                  @if($appointment->consultation->temperature)
                  <div class="col-6 col-md-4">
                    <div class="p-3 border rounded text-center">
                      <i class="icon-base ti tabler-temperature text-warning mb-1" style="font-size:1.25rem;"></i>
                      <div class="fw-bold">{{ $appointment->consultation->temperature }}°F</div>
                      <small class="text-muted">Temperature</small>
                    </div>
                  </div>
                  @endif
                  @if($appointment->consultation->pulse_rate)
                  <div class="col-6 col-md-4">
                    <div class="p-3 border rounded text-center">
                      <i class="icon-base ti tabler-heart text-danger mb-1" style="font-size:1.25rem;"></i>
                      <div class="fw-bold">{{ $appointment->consultation->pulse_rate }} bpm</div>
                      <small class="text-muted">Pulse Rate</small>
                    </div>
                  </div>
                  @endif
                  @if($appointment->consultation->weight)
                  <div class="col-6 col-md-4">
                    <div class="p-3 border rounded text-center">
                      <i class="icon-base ti tabler-weight text-success mb-1" style="font-size:1.25rem;"></i>
                      <div class="fw-bold">{{ $appointment->consultation->weight }} kg</div>
                      <small class="text-muted">Weight</small>
                    </div>
                  </div>
                  @endif
                  @if($appointment->consultation->height)
                  <div class="col-6 col-md-4">
                    <div class="p-3 border rounded text-center">
                      <i class="icon-base ti tabler-ruler text-info mb-1" style="font-size:1.25rem;"></i>
                      <div class="fw-bold">{{ $appointment->consultation->height }} cm</div>
                      <small class="text-muted">Height</small>
                    </div>
                  </div>
                  @endif
                  @if($appointment->consultation->spo2)
                  <div class="col-6 col-md-4">
                    <div class="p-3 border rounded text-center">
                      <i class="icon-base ti tabler-lungs text-primary mb-1" style="font-size:1.25rem;"></i>
                      <div class="fw-bold">{{ $appointment->consultation->spo2 }}%</div>
                      <small class="text-muted">SpO2</small>
                    </div>
                  </div>
                  @endif
                </div>
              </div>
              @endif

              {{-- Notes --}}
              @if($appointment->consultation->chief_complaint)
              <div class="timeline-card mb-3" style="border-left-color: var(--bs-danger);">
                <div class="section-title" style="border-color:var(--bs-danger);color:var(--bs-danger);">Chief Complaint</div>
                <p class="mb-0">{{ $appointment->consultation->chief_complaint }}</p>
              </div>
              @endif

              @if($appointment->consultation->diagnosis)
              <div class="timeline-card mb-3" style="border-left-color: var(--bs-warning);">
                <div class="section-title" style="border-color:var(--bs-warning);color:var(--bs-warning);">Diagnosis</div>
                <p class="mb-0">{{ $appointment->consultation->diagnosis }}</p>
              </div>
              @endif

              @if($appointment->consultation->treatment_plan)
              <div class="timeline-card mb-3" style="border-left-color: var(--bs-success);">
                <div class="section-title" style="border-color:var(--bs-success);color:var(--bs-success);">Treatment Plan</div>
                <p class="mb-0">{{ $appointment->consultation->treatment_plan }}</p>
              </div>
              @endif

              @if($appointment->consultation->notes)
              <div class="timeline-card" style="border-left-color: var(--bs-info);">
                <div class="section-title" style="border-color:var(--bs-info);color:var(--bs-info);">Additional Notes</div>
                <p class="mb-0">{{ $appointment->consultation->notes }}</p>
              </div>
              @endif

            @else
              <div class="text-center py-6">
                <i class="icon-base ti tabler-stethoscope text-muted" style="font-size:3rem;"></i>
                <p class="text-muted mt-2 mb-0">No consultation recorded yet.</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Prescription Tab --}}
      <div class="tab-pane fade" id="prescription">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0">
              <i class="icon-base ti tabler-pill me-2 text-primary"></i>Prescriptions
            </h6>
          </div>
          <div class="card-body">
            @if($appointment->consultation && $appointment->consultation->prescriptions->count() > 0)
              @foreach($appointment->consultation->prescriptions as $prescription)
              <div class="timeline-card mb-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                    <h6 class="mb-1">{{ $prescription->prescription_no }}</h6>
                    <small class="text-muted">
                      {{ \Carbon\Carbon::parse($prescription->prescription_date)->format('d M Y') }}
                    </small>
                  </div>
                  <div class="d-flex gap-2">
                    <a href="{{ route('prescriptions.show', $prescription->id) }}"
                      class="btn btn-sm btn-label-info">
                      <i class="icon-base ti tabler-eye me-1"></i> View
                    </a>
                    <a href="{{ route('prescriptions.print', $prescription->id) }}"
                      target="_blank" class="btn btn-sm btn-label-primary">
                      <i class="icon-base ti tabler-printer me-1"></i> Print
                    </a>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($prescription->items as $i => $item)
                      <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $item->medicine_name }}</td>
                        <td>{{ $item->dosage }}</td>
                        <td><span class="badge bg-label-primary">{{ $item->frequency }}</span></td>
                        <td>{{ $item->duration }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
              @endforeach
            @else
              <div class="text-center py-6">
                <i class="icon-base ti tabler-pill text-muted" style="font-size:3rem;"></i>
                <p class="text-muted mt-2 mb-0">No prescriptions found.</p>
              </div>
            @endif
          </div>
        </div>
      </div>

    </div>
  </div>
  {{-- /Right Content --}}

</div>

@endsection