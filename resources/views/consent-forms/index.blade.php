@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Consent Forms - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Total Forms</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalConsentForms }}</h4>
            </div>
            <small>All time</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-writing icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Pending</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $pendingConsentForms }}</h4>
            </div>
            <small>Awaiting signature</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-clock icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Signed</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $signedConsentForms }}</h4>
            </div>
            <small>Completed</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-circle-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Declined</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $declinedConsentForms }}</h4>
            </div>
            <small>Refused</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-x icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Consent Forms</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasConsentAdd">
      <i class="icon-base ti tabler-plus me-1"></i> New Consent Form
    </button>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="consentTable">
      <thead>
        <tr>
          <th></th>
          <th>Consent No</th>
          <th>Patient</th>
          <th>Type</th>
          <th>Procedure</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Consent Form Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasConsentAdd" style="width:500px;max-width:92vw;">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Consent Form</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('consent-forms.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
            <option value="">Select Patient</option>
            @foreach($patients as $patient)
            <option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->name }} ({{ $patient->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Consent Type *</label>
          <select class="form-select @error('consent_type') is-invalid @enderror" name="consent_type" required>
            <option value="general" @selected(old('consent_type', 'general')=='general')>General</option>
            <option value="surgery" @selected(old('consent_type')=='surgery')>Surgery</option>
            <option value="anesthesia" @selected(old('consent_type')=='anesthesia')>Anesthesia</option>
            <option value="blood_transfusion" @selected(old('consent_type')=='blood_transfusion')>Blood Transfusion</option>
            <option value="procedure" @selected(old('consent_type')=='procedure')>Procedure</option>
            <option value="other" @selected(old('consent_type')=='other')>Other</option>
          </select>
          @error('consent_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Procedure / Treatment Name</label>
          <input type="text" class="form-control @error('procedure_name') is-invalid @enderror" name="procedure_name" placeholder="e.g. Appendectomy" value="{{ old('procedure_name') }}" />
          @error('procedure_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Description *</label>
          <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4"
            placeholder="Describe the procedure, risks, and benefits..." required>{{ old('description') }}</textarea>
          @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Doctor Name</label>
          <input type="text" class="form-control @error('doctor_name') is-invalid @enderror" name="doctor_name" placeholder="Dr. Name" value="{{ old('doctor_name') }}" />
          @error('doctor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Witness Name</label>
          <input type="text" class="form-control @error('witness_name') is-invalid @enderror" name="witness_name" placeholder="Witness full name" value="{{ old('witness_name') }}" />
          @error('witness_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Consent Date *</label>
          <input type="date" class="form-control @error('consent_date') is-invalid @enderror" name="consent_date" value="{{ old('consent_date', now()->toDateString()) }}" required />
          @error('consent_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-arrow-right me-1"></i> Next: Get Signature
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

</div>

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasConsentAdd')).show();
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {

  const typeColors = {
    general: 'secondary', surgery: 'danger', anesthesia: 'warning',
    blood_transfusion: 'info', procedure: 'primary', other: 'secondary',
  };

  const statusColors = {
    pending: 'warning', signed: 'success', declined: 'danger',
  };

  $('#consentTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('consent-forms.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'consent_no', name: 'consent_no', render: d => `<span class="badge bg-label-secondary">${d}</span>` },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'consent_type', name: 'consent_type', render: d => `<span class="badge bg-label-${typeColors[d] || 'secondary'} text-capitalize">${d.replace('_',' ')}</span>` },
      { data: 'procedure_name', name: 'procedure_name' },
      { data: 'consent_date', name: 'consent_date' },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColors[d] || 'secondary'} text-capitalize">${d}</span>` },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[5, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

});

function deleteConsent(id) {
  confirmDelete(`/consent-forms/${id}`, 'Consent Form', function() {
    $('#consentTable').DataTable().ajax.reload(null, false);
  });
}
</script>

@endsection