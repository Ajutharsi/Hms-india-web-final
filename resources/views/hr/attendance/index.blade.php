@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Attendance - HMS')

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
              <h4 class="mb-0">{{ $todayPresent }}</h4>
              <p class="mb-0">Present Today</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-clock-check icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $todayAbsent }}</h4>
              <p class="mb-0">Absent Today</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-user-x icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $todayLate }}</h4>
              <p class="mb-0">Late Today</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock-exclamation icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $onLeaveToday }}</h4>
              <p class="mb-0">On Leave Today</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-calendar-off icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Attendance Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Attendance Records</h5>
    <div class="d-flex gap-2 align-items-center">
      <a href="{{ route('hr.leaves.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-calendar-off me-1"></i> Leave Requests
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAttendanceAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Mark Attendance
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="attendanceTable">
      <thead>
        <tr>
          <th></th>
          <th>Employee</th>
          <th>Date</th>
          <th>Shift</th>
          <th>Check In</th>
          <th>Check Out</th>
          <th>Hours</th>
          <th>Status</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Mark Attendance Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAttendanceAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Mark Attendance</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('hr.attendance.store') }}" method="POST">
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
          <label class="form-label">Date *</label>
          <input type="date" class="form-control @error('attendance_date') is-invalid @enderror" name="attendance_date" value="{{ old('attendance_date', now()->toDateString()) }}" required />
          @error('attendance_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Shift *</label>
            <select class="form-select @error('shift') is-invalid @enderror" name="shift" required>
              <option value="morning" @selected(old('shift','morning')=='morning')>Morning</option>
              <option value="afternoon" @selected(old('shift')=='afternoon')>Afternoon</option>
              <option value="night" @selected(old('shift')=='night')>Night</option>
              <option value="full_day" @selected(old('shift')=='full_day')>Full Day</option>
            </select>
            @error('shift') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Status *</label>
            <select class="form-select @error('status') is-invalid @enderror" name="status" required>
              <option value="present" @selected(old('status','present')=='present')>Present</option>
              <option value="absent" @selected(old('status')=='absent')>Absent</option>
              <option value="late" @selected(old('status')=='late')>Late</option>
              <option value="half_day" @selected(old('status')=='half_day')>Half Day</option>
              <option value="leave" @selected(old('status')=='leave')>Leave</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Check In</label>
            <input type="time" class="form-control @error('check_in') is-invalid @enderror" name="check_in" value="{{ old('check_in') }}" />
            @error('check_in') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Check Out</label>
            <input type="time" class="form-control @error('check_out') is-invalid @enderror" name="check_out" value="{{ old('check_out') }}" />
            @error('check_out') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
</div>

@section('page-script')
<script>
let attendanceTable;

function statusColor(s) {
  const map = { present: 'success', absent: 'danger', late: 'warning', half_day: 'info', leave: 'secondary' };
  return map[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  attendanceTable = $('#attendanceTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('hr.attendance.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'user_name', name: 'user_name', orderable: false },
      { data: 'attendance_date', name: 'attendance_date' },
      { data: 'shift', name: 'shift', render: d => d.replace('_',' ') },
      { data: 'check_in', name: 'check_in', defaultContent: '—' },
      { data: 'check_out', name: 'check_out', defaultContent: '—' },
      { data: 'hours_worked', name: 'hours_worked', defaultContent: '—' },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColor(d)}">${d.replace('_',' ')}</span>` },
    ],
    pageLength: 25,
    order: [[2, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });
});

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasAttendanceAdd')).show();
});
@endif
</script>
@endsection

@endsection
