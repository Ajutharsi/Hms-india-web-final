@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Referrals - HMS')

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
            <span class="text-heading">Total Referrals</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalReferrals }}</h4>
            </div>
            <small>All time</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-transfer icon-26px"></i>
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
              <h4 class="mb-0 me-2">{{ $pendingReferrals }}</h4>
            </div>
            <small>Awaiting response</small>
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
            <span class="text-heading">Accepted</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $acceptedReferrals }}</h4>
            </div>
            <small>In progress</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
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
            <span class="text-heading">Completed</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $completedReferrals }}</h4>
            </div>
            <small>Done</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Referrals Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Referrals</h5>
    @role('super_admin|admin|doctor|receptionist')
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasReferralAdd">
      <i class="icon-base ti tabler-plus me-1"></i> New Referral
    </button>
    @endrole
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="referralsTable">
      <thead>
        <tr>
          <th></th>
          <th>Ref No</th>
          <th>Patient</th>
          <th>Referred By</th>
          <th>Referred To</th>
          <th>Priority</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Referral Offcanvas --}}
  @role('super_admin|admin|doctor|receptionist')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasReferralAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Referral</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('referrals.store') }}" method="POST">
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
          <label class="form-label">Referred By (Doctor) *</label>
          <select class="form-select @error('referred_by') is-invalid @enderror" name="referred_by" required>
            <option value="">Select Doctor</option>
            @foreach($doctors as $doctor)
            <option value="{{ $doctor->id }}" @selected(old('referred_by')==$doctor->id)>Dr. {{ $doctor->name }} — {{ $doctor->specialization }}</option>
            @endforeach
          </select>
          @error('referred_by') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Referred To (Doctor) *</label>
          <select class="form-select @error('referred_to') is-invalid @enderror" name="referred_to" required>
            <option value="">Select Doctor</option>
            @foreach($doctors as $doctor)
            <option value="{{ $doctor->id }}" @selected(old('referred_to')==$doctor->id)>Dr. {{ $doctor->name }} — {{ $doctor->specialization }}</option>
            @endforeach
          </select>
          @error('referred_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Referral Date *</label>
          <input type="date" class="form-control @error('referral_date') is-invalid @enderror" name="referral_date" value="{{ old('referral_date', now()->toDateString()) }}" required />
          @error('referral_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Priority *</label>
          <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
            <option value="routine" @selected(old('priority', 'routine')=='routine')>Routine</option>
            <option value="urgent" @selected(old('priority')=='urgent')>Urgent</option>
            <option value="emergency" @selected(old('priority')=='emergency')>Emergency</option>
          </select>
          @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Reason *</label>
          <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="3" placeholder="Reason for referral..." required>{{ old('reason') }}</textarea>
          @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2" placeholder="Additional notes...">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Create Referral
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

</div>

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasReferralAdd')).show();
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {

  const priorityColors = {
    routine: 'secondary', urgent: 'warning', emergency: 'danger',
  };

  const statusColors = {
    pending: 'warning', accepted: 'info',
    completed: 'success', cancelled: 'secondary',
  };

  $('#referralsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('referrals.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'referral_no', name: 'referral_no', render: d => `<span class="badge bg-label-secondary">${d}</span>` },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'referred_by', name: 'referred_by', orderable: false },
      { data: 'referred_to', name: 'referred_to', orderable: false },
      { data: 'priority', name: 'priority', render: d => `<span class="badge bg-label-${priorityColors[d] || 'secondary'} text-capitalize">${d}</span>` },
      { data: 'referral_date', name: 'referral_date' },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColors[d] || 'secondary'} text-capitalize">${d}</span>` },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[6, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

});

function updateStatus(id, status) {
  fetch(`/referrals/${id}/status`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ status })
  }).then(() => {
    $('#referralsTable').DataTable().ajax.reload(null, false);
  });
}

function deleteReferral(id) {
  confirmDelete(`/referrals/${id}`, 'Referral', function() {
    $('#referralsTable').DataTable().ajax.reload(null, false);
  });
}
</script>

@endsection