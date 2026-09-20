@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Operation Theatre - HMS')

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
              <h4 class="mb-0">{{ $todayCount }}</h4>
              <p class="mb-0">Today's OT</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-calendar icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $scheduledCount }}</h4>
              <p class="mb-0">Scheduled</p>
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
              <h4 class="mb-0">{{ $inProgressCount }}</h4>
              <p class="mb-0">In Progress</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-scissors icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $completedCount }}</h4>
              <p class="mb-0">Completed Today</p>
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

{{-- Today's OT --}}
@if ($todayOt->count() > 0)
<div class="card mb-6">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0"><i class="icon-base ti tabler-scissors me-2 text-danger"></i>Today's Operation Theatre List</h5>
  </div>
  <div class="card-body">
    <div class="row g-4">
      @foreach ($todayOt as $o)
      <div class="col-md-6 col-lg-4">
        <div class="border rounded p-4 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <strong>{{ $o->ot_no }}</strong>
            <span class="badge bg-label-{{ $o->priority === 'emergency' ? 'danger' : ($o->priority === 'urgent' ? 'warning' : 'success') }}">{{ $o->priority }}</span>
          </div>
          <div class="mb-1">{{ $o->patient->name ?? 'N/A' }} <small class="text-muted">({{ $o->patient->mrn ?? '' }})</small></div>
          <div class="mb-1 text-muted small">{{ $o->procedure_name }}</div>
          <div class="mb-1 text-muted small">Surgeon: {{ $o->surgeon->name ?? '—' }}</div>
          <div class="mb-2 text-muted small">Theatre: {{ $o->theatre_no ?? '—' }} | {{ $o->scheduled_start->format('h:i A') }}</div>
          <span class="badge bg-label-{{ $o->status === 'in_progress' ? 'info' : ($o->status === 'completed' ? 'success' : 'secondary') }}">{{ str_replace('_',' ', $o->status) }}</span>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- OT Schedule Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">OT Schedule</h5>
    <div class="d-flex gap-2 align-items-center">
      <select id="statusFilter" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="postponed">Postponed</option>
      </select>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasOtAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Schedule OT
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="otTable">
      <thead>
        <tr>
          <th></th>
          <th>OT No</th>
          <th>Patient</th>
          <th>Surgeon</th>
          <th>Procedure</th>
          <th>Theatre</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Scheduled</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Schedule OT Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasOtAdd" data-bs-scroll="true">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Schedule Operation Theatre</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('ot.store') }}" method="POST">
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
          <label class="form-label">Procedure Name *</label>
          <input type="text" class="form-control @error('procedure_name') is-invalid @enderror" name="procedure_name" value="{{ old('procedure_name') }}" required />
          @error('procedure_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Surgeon</label>
            <select class="form-select @error('surgeon_id') is-invalid @enderror" name="surgeon_id">
              <option value="">Select</option>
              @foreach ($doctors as $d)
              <option value="{{ $d->id }}" @selected(old('surgeon_id')==$d->id)>{{ $d->name }}</option>
              @endforeach
            </select>
            @error('surgeon_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Anaesthetist</label>
            <select class="form-select @error('anaesthetist_id') is-invalid @enderror" name="anaesthetist_id">
              <option value="">Select</option>
              @foreach ($doctors as $d)
              <option value="{{ $d->id }}" @selected(old('anaesthetist_id')==$d->id)>{{ $d->name }}</option>
              @endforeach
            </select>
            @error('anaesthetist_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Scrub Nurse</label>
            <select class="form-select @error('scrub_nurse_id') is-invalid @enderror" name="scrub_nurse_id">
              <option value="">Select</option>
              @foreach ($nurses as $n)
              <option value="{{ $n->id }}" @selected(old('scrub_nurse_id')==$n->id)>{{ $n->name }}</option>
              @endforeach
            </select>
            @error('scrub_nurse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Theatre No</label>
            <input type="text" class="form-control @error('theatre_no') is-invalid @enderror" name="theatre_no" placeholder="OT-1" value="{{ old('theatre_no') }}" />
            @error('theatre_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Anaesthesia Type</label>
            <select class="form-select @error('anaesthesia_type') is-invalid @enderror" name="anaesthesia_type">
              <option value="">Select</option>
              <option value="general" @selected(old('anaesthesia_type')=='general')>General</option>
              <option value="spinal" @selected(old('anaesthesia_type')=='spinal')>Spinal</option>
              <option value="epidural" @selected(old('anaesthesia_type')=='epidural')>Epidural</option>
              <option value="local" @selected(old('anaesthesia_type')=='local')>Local</option>
              <option value="sedation" @selected(old('anaesthesia_type')=='sedation')>Sedation</option>
            </select>
            @error('anaesthesia_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Priority *</label>
            <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
              <option value="elective" @selected(old('priority','elective')=='elective')>Elective</option>
              <option value="urgent" @selected(old('priority')=='urgent')>Urgent</option>
              <option value="emergency" @selected(old('priority')=='emergency')>Emergency</option>
            </select>
            @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Scheduled Start *</label>
            <input type="datetime-local" class="form-control @error('scheduled_start') is-invalid @enderror" name="scheduled_start" value="{{ old('scheduled_start') }}" required />
            @error('scheduled_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Scheduled End</label>
            <input type="datetime-local" class="form-control @error('scheduled_end') is-invalid @enderror" name="scheduled_end" value="{{ old('scheduled_end') }}" />
            @error('scheduled_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-6">
          <label class="form-label">Pre-Op Notes</label>
          <textarea class="form-control @error('pre_op_notes') is-invalid @enderror" name="pre_op_notes" rows="2">{{ old('pre_op_notes') }}</textarea>
          @error('pre_op_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Complete OT Modal --}}
  <div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Complete Surgery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="completeForm">
            <input type="hidden" id="complete_ot_id" />
            <div class="mb-4">
              <label class="form-label">Post-Op Notes</label>
              <textarea class="form-control" id="complete_post_op_notes" rows="4"></textarea>
            </div>
            <div class="mb-4">
              <label class="form-label">Complications</label>
              <textarea class="form-control" id="complete_complications" rows="3"></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ti tabler-check me-1"></i> Mark Completed
              </button>
              <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
let otTable;

function priorityColor(p) {
  return { emergency: 'danger', urgent: 'warning', elective: 'success' }[p] || 'secondary';
}

function statusColor(s) {
  const map = { scheduled: 'secondary', in_progress: 'info', completed: 'success', cancelled: 'danger', postponed: 'warning' };
  return map[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  otTable = $('#otTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('ot.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'ot_no', name: 'ot_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'surgeon_name', name: 'surgeon_name', orderable: false },
      { data: 'procedure_name', name: 'procedure_name' },
      { data: 'theatre_no', name: 'theatre_no', defaultContent: '—' },
      { data: 'priority_badge', name: 'priority' },
      { data: 'status_badge', name: 'status' },
      { data: 'scheduled_start', name: 'scheduled_start' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[8, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('statusFilter').addEventListener('change', function () {
    otTable.column(7).search(this.value).draw();
  });
});

function startOt(id) {
  fetch(`/ot/${id}/start`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
  }).then(res => res.json()).then(() => {
    otTable.ajax.reload(null, false);
  });
}

function openComplete(id) {
  document.getElementById('complete_ot_id').value = id;
  document.getElementById('complete_post_op_notes').value = '';
  document.getElementById('complete_complications').value = '';
  new bootstrap.Modal(document.getElementById('completeModal')).show();
}

document.getElementById('completeForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('complete_ot_id').value;
  fetch(`/ot/${id}/complete`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      post_op_notes: document.getElementById('complete_post_op_notes').value,
      complications: document.getElementById('complete_complications').value,
    })
  }).then(res => res.json()).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('completeModal')).hide();
    otTable.ajax.reload(null, false);
  });
});

function deleteOt(id) {
  confirmDelete(`/ot/${id}`, 'OT Schedule', function() {
    otTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasOtAdd')).show();
});
@endif
</script>
@endsection

@endsection
