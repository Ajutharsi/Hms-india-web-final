@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Staff Rounds - HMS')

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

@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
  <i class="icon-base ti tabler-x me-2"></i>{{ session('error') }}
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
            <span class="text-heading">Active Assignments</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\RoundAssignment::where('status','active')->count() }}</h4>
            </div>
            <small>Total active</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-clipboard-list icon-26px"></i>
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
            <span class="text-heading">Overdue Rounds</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\RoundAssignment::where('status','active')->where('next_round_at','<=',now())->count() }}</h4>
            </div>
            <small>Need attention</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
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
            <span class="text-heading">Rounds Today</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\StaffRound::whereDate('round_time', today())->count() }}</h4>
            </div>
            <small>Completed</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-clock-check icon-26px"></i>
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
            <span class="text-heading">IP Patients</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Patient::where('patient_type','ip')->where('status','active')->count() }}</h4>
            </div>
            <small>Active</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-bed icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Assignments Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Round Assignments</h5>
    @role('super_admin|admin|nurse')
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAssign">
      <i class="icon-base ti tabler-plus me-1"></i> Assign Round
    </button>
    @endrole
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="assignmentsTable">
      <thead>
        <tr>
          <th></th>
          <th>Ward</th>
          <th>Ward Type</th>
          <th>Assigned Staff</th>
          <th>Assigned By</th>
          <th>Frequency</th>
          <th>Last Round</th>
          <th>Next Round</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Assign Round Offcanvas --}}
  @role('super_admin|admin|nurse')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAssign">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Assign Round</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('staff-rounds.assign') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Ward *</label>
          <select class="form-select @error('ward_id') is-invalid @enderror" name="ward_id" required>
            <option value="">Select Ward</option>
            @foreach($wards as $ward)
            <option value="{{ $ward->id }}" @selected(old('ward_id')==$ward->id)>
              {{ $ward->name }} ({{ $ward->available_beds }} beds available)
            </option>
            @endforeach
          </select>
          @error('ward_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Assign Staff *</label>
          <select class="form-select @error('staff_id') is-invalid @enderror" name="staff_id" required>
            <option value="">Select Staff / Nurse</option>
            @foreach($staffList as $staff)
            <option value="{{ $staff->id }}" @selected(old('staff_id')==$staff->id)>{{ $staff->name }}</option>
            @endforeach
          </select>
          @error('staff_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Check Frequency *</label>
          @php $defaultRoundHours = old('frequency_hours', $hospitalSettings->round_alert_hours ?: 1); @endphp
          <select class="form-select @error('frequency_hours') is-invalid @enderror" name="frequency_hours" required>
            <option value="1" {{ $defaultRoundHours == 1 ? 'selected' : '' }}>Every 1 Hour</option>
            <option value="2" {{ $defaultRoundHours == 2 ? 'selected' : '' }}>Every 2 Hours</option>
            <option value="3" {{ $defaultRoundHours == 3 ? 'selected' : '' }}>Every 3 Hours</option>
            <option value="4" {{ $defaultRoundHours == 4 ? 'selected' : '' }}>Every 4 Hours</option>
            <option value="6" {{ $defaultRoundHours == 6 ? 'selected' : '' }}>Every 6 Hours</option>
            <option value="8" {{ $defaultRoundHours == 8 ? 'selected' : '' }}>Every 8 Hours</option>
            <option value="12" {{ $defaultRoundHours == 12 ? 'selected' : '' }}>Every 12 Hours</option>
          </select>
          @error('frequency_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
            placeholder="Special instructions...">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Assign
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
  new bootstrap.Offcanvas(document.getElementById('offcanvasAssign')).show();
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#assignmentsTable').DataTable({
    processing: true,
    ajax: { url: '{{ route('staff-rounds.assignments.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'ward_name' },
      { data: 'ward_type', render: d => `<span class="badge bg-label-info text-capitalize">${d.replace('_',' ')}</span>` },
      { data: 'staff_name' },
      { data: 'assigned_by' },
      { data: 'frequency_hours', render: d => `Every ${d}hr` },
      { data: 'last_round_at', defaultContent: '—' },
      { data: null, render: d => {
        if (!d.next_round_at) return '—';
        const color = d.is_overdue ? 'danger' : 'success';
        return `<span class="badge bg-label-${color}">${d.is_overdue ? '⚠ Overdue' : d.next_round_at}</span>`;
      }},
      { data: 'id', orderable: false, searchable: false, render: d => `
        <form method="POST" action="/staff-rounds/deactivate/${d}"
          onsubmit="event.preventDefault(); confirmFormSubmit(this, 'Remove Assignment?', 'Are you sure you want to remove this assignment? This action cannot be undone.'); return false;">
          <input type="hidden" name="_token" value="{{ csrf_token() }}" />
          <input type="hidden" name="_method" value="PUT" />
          <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Remove">
            <i class="icon-base ti tabler-trash"></i>
          </button>
        </form>` }
    ]
  });
});
</script>

@endsection