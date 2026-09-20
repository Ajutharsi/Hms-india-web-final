@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Leave Requests - HMS')

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
              <h4 class="mb-0">{{ $totalCount }}</h4>
              <p class="mb-0">Total Requests</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-calendar-off icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $pendingCount }}</h4>
              <p class="mb-0">Pending</p>
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
              <h4 class="mb-0">{{ $approvedCount }}</h4>
              <p class="mb-0">Approved</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $rejectedCount }}</h4>
              <p class="mb-0">Rejected</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-x icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Leaves Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Leave Requests</h5>
    <div class="d-flex gap-2 align-items-center">
      <select id="statusFilter" class="form-select form-select-sm" style="width:140px;">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <a href="{{ route('hr.attendance.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-clock-check me-1"></i> Attendance
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeaveAdd">
        <i class="icon-base ti tabler-plus me-1"></i> New Leave Request
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="leavesTable">
      <thead>
        <tr>
          <th></th>
          <th>Employee</th>
          <th>Type</th>
          <th>From</th>
          <th>To</th>
          <th>Days</th>
          <th>Reason</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- New Leave Request Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLeaveAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Leave Request</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('hr.leaves.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Employee *</label>
          <select class="form-select @error('user_id') is-invalid @enderror" name="user_id" required>
            <option value="">Select Employee</option>
            @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected(old('user_id')==$u->id)>{{ $u->name }}</option>
            @endforeach
          </select>
          @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Leave Type *</label>
          <select class="form-select @error('leave_type') is-invalid @enderror" name="leave_type" required>
            <option value="casual" @selected(old('leave_type','casual')=='casual')>Casual</option>
            <option value="sick" @selected(old('leave_type')=='sick')>Sick</option>
            <option value="annual" @selected(old('leave_type')=='annual')>Annual</option>
            <option value="emergency" @selected(old('leave_type')=='emergency')>Emergency</option>
            <option value="maternity" @selected(old('leave_type')=='maternity')>Maternity</option>
            <option value="paternity" @selected(old('leave_type')=='paternity')>Paternity</option>
          </select>
          @error('leave_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">From Date *</label>
            <input type="date" class="form-control @error('from_date') is-invalid @enderror" name="from_date" value="{{ old('from_date') }}" required />
            @error('from_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">To Date *</label>
            <input type="date" class="form-control @error('to_date') is-invalid @enderror" name="to_date" value="{{ old('to_date') }}" required />
            @error('to_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-6">
          <label class="form-label">Reason *</label>
          <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="3" required>{{ old('reason') }}</textarea>
          @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Submit
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Approve/Reject Modal --}}
  <div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="actionModalTitle">Approve Leave</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="actionForm">
            <input type="hidden" id="action_leave_id" />
            <input type="hidden" id="action_type" />
            <div class="mb-4">
              <label class="form-label">Remarks</label>
              <textarea class="form-control" id="action_remarks" rows="3"></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" id="actionSubmitBtn">Confirm</button>
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
let leavesTable;

function statusColor(s) {
  return { pending: 'warning', approved: 'success', rejected: 'danger' }[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  leavesTable = $('#leavesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('hr.leaves.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'user_name', name: 'user_name', orderable: false },
      { data: 'leave_type', name: 'leave_type', render: d => `<span class="badge bg-label-primary">${d}</span>` },
      { data: 'from_date', name: 'from_date' },
      { data: 'to_date', name: 'to_date' },
      { data: 'days', name: 'days' },
      { data: 'reason', name: 'reason', render: d => `<small class="text-muted">${d}</small>` },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColor(d)}">${d}</span>` },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[3, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('statusFilter').addEventListener('change', function () {
    leavesTable.column(7).search(this.value).draw();
  });
});

function openAction(id, type) {
  document.getElementById('action_leave_id').value = id;
  document.getElementById('action_type').value = type;
  document.getElementById('action_remarks').value = '';
  document.getElementById('actionModalTitle').textContent = type === 'approve' ? 'Approve Leave' : 'Reject Leave';
  document.getElementById('actionSubmitBtn').className = type === 'approve' ? 'btn btn-success' : 'btn btn-danger';
  new bootstrap.Modal(document.getElementById('actionModal')).show();
}

document.getElementById('actionForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('action_leave_id').value;
  const type = document.getElementById('action_type').value;
  fetch(`/hr/leaves/${id}/${type}`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ remarks: document.getElementById('action_remarks').value })
  }).then(res => res.json()).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
    leavesTable.ajax.reload(null, false);
  });
});

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasLeaveAdd')).show();
});
@endif
</script>
@endsection

@endsection
