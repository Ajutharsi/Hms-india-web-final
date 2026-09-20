@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'OPD Queue - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
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
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0" id="statTotal">{{ $totalToday }}</h4>
              <p class="mb-0">Total Today</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-list-numbers icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0" id="statWaiting">{{ $waitingCount }}</h4>
              <p class="mb-0">Waiting</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0" id="statInConsult">{{ $inConsultCount }}</h4>
              <p class="mb-0">In Consultation</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-stethoscope icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0" id="statCompleted">{{ $completedCount }}</h4>
              <p class="mb-0">Completed</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Queue Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Today's OPD Queue</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="doctorFilter" class="form-select form-select-sm" style="width:180px;">
        <option value="">All Doctors</option>
        @foreach ($doctors as $d)
        <option value="{{ $d->name }}" data-photo="{{ $d->photo_url }}">{{ $d->name }}</option>
        @endforeach
      </select>
      <select id="callNextDoctor" class="form-select form-select-sm" style="width:180px;">
        <option value="">Select Doctor to Call Next</option>
        @foreach ($doctors as $d)
        <option value="{{ $d->id }}" data-photo="{{ $d->photo_url }}">{{ $d->name }}</option>
        @endforeach
      </select>
      <button class="btn btn-label-success" onclick="callNext()">
        <i class="icon-base ti tabler-bell-ringing me-1"></i> Call Next
      </button>
      <a href="{{ route('opd.display') }}" target="_blank" class="btn btn-label-info">
        <i class="icon-base ti tabler-device-tv me-1"></i> Display Board
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasTokenAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Issue Token
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="queueTable">
      <thead>
        <tr>
          <th></th>
          <th>Token No</th>
          <th>Patient</th>
          <th>Doctor</th>
          <th>Position</th>
          <th>Status</th>
          <th>Called At</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Issue Token Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTokenAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Issue Token</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('opd.queue.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
            <option value="">Select Patient</option>
            @foreach ($patients as $p)
            <option value="{{ $p->id }}" @selected(old('patient_id')==$p->id)>{{ $p->name }} ({{ $p->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Doctor *</label>
          <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id" required>
            <option value="">Select Doctor</option>
            @foreach ($doctors as $d)
            <option value="{{ $d->id }}" data-photo="{{ $d->photo_url }}" @selected(old('doctor_id')==$d->id)>{{ $d->name }}</option>
            @endforeach
          </select>
          @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Issue Token
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('page-script')
<script>
let queueTable;
let refreshInterval;

function statusColor(s) {
  const map = { waiting: 'warning', in_consultation: 'info', completed: 'success', skipped: 'secondary', cancelled: 'danger' };
  return map[s] || 'secondary';
}

function loadStats() {
  fetch('{{ route('opd.queue.stats') }}')
    .then(res => res.json())
    .then(res => {
      document.getElementById('statTotal').textContent = res.total;
      document.getElementById('statWaiting').textContent = res.waiting;
      document.getElementById('statInConsult').textContent = res.in_consult;
      document.getElementById('statCompleted').textContent = res.completed;
    });
}

document.addEventListener('DOMContentLoaded', function () {
  queueTable = $('#queueTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('opd.queue.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'token_no', name: 'token_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'doctor_name', name: 'doctor_name' },
      { data: 'queue_position', name: 'queue_position' },
      { data: 'status_badge', name: 'status' },
      { data: 'called_at', name: 'called_at', defaultContent: '—' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[4, 'asc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('doctorFilter').addEventListener('change', function () {
    queueTable.column(3).search(this.value).draw();
  });

  refreshInterval = setInterval(function () {
    queueTable.ajax.reload(null, false);
    loadStats();
  }, 30000);
});

function setStatus(id, status) {
  fetch(`/opd/queue/${id}/status`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ status })
  }).then(res => res.json()).then(() => {
    queueTable.ajax.reload(null, false);
    loadStats();
  });
}

function callNext() {
  const doctorId = document.getElementById('callNextDoctor').value;
  if (!doctorId) { alert('Please select a doctor first.'); return; }
  fetch(`/opd/queue/call-next/${doctorId}`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
  }).then(res => res.json()).then(res => {
    if (res.message) { alert(res.message); return; }
    queueTable.ajax.reload(null, false);
    loadStats();
  });
}

function deleteToken(id) {
  confirmDelete(`/opd/queue/${id}`, 'Token', function() {
    queueTable.ajax.reload(null, false);
    loadStats();
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasTokenAdd')).show();
});
@endif
</script>
@endsection

@endsection
