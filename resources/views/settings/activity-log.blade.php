@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Activity Log - HMS')

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

<div class="row">
  <div class="col-md-12">

    {{-- Nav Tabs --}}
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
        <li class="nav-item">
          <a class="nav-link" href="{{ route('settings.index') }}">
            <i class="icon-base ti tabler-building-hospital icon-sm me-1_5"></i> Hospital
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('settings.departments') }}">
            <i class="icon-base ti tabler-layout-grid icon-sm me-1_5"></i> Departments
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="{{ route('settings.activity-log') }}">
            <i class="icon-base ti tabler-history icon-sm me-1_5"></i> Activity Log
          </a>
        </li>
      </ul>
    </div>

    {{-- Stats --}}
    <div class="card mb-6">
      <div class="card-widget-separator-wrapper">
        <div class="card-body card-widget-separator">
          <div class="row gy-4 gy-sm-1">
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
                <div>
                  <h4 class="mb-0">{{ \App\Models\ActivityLog::count() }}</h4>
                  <p class="mb-0">Total Logs</p>
                </div>
                <div class="avatar me-sm-6">
                  <span class="avatar-initial rounded bg-label-primary text-heading">
                    <i class="icon-base ti tabler-history icon-26px"></i>
                  </span>
                </div>
              </div>
              <hr class="d-none d-sm-block d-lg-none me-6" />
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
                <div>
                  <h4 class="mb-0">{{ \App\Models\ActivityLog::whereDate('created_at', today())->count() }}</h4>
                  <p class="mb-0">Today's Activity</p>
                </div>
                <div class="avatar me-lg-6">
                  <span class="avatar-initial rounded bg-label-success text-heading">
                    <i class="icon-base ti tabler-calendar-event icon-26px"></i>
                  </span>
                </div>
              </div>
              <hr class="d-none d-sm-block d-lg-none" />
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
                <div>
                  <h4 class="mb-0">{{ \App\Models\ActivityLog::where('action','created')->count() }}</h4>
                  <p class="mb-0">Created</p>
                </div>
                <div class="avatar me-sm-6">
                  <span class="avatar-initial rounded bg-label-info text-heading">
                    <i class="icon-base ti tabler-plus icon-26px"></i>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-lg-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4 class="mb-0">{{ \App\Models\ActivityLog::where('action','deleted')->count() }}</h4>
                  <p class="mb-0">Deleted</p>
                </div>
                <div class="avatar">
                  <span class="avatar-initial rounded bg-label-danger text-heading">
                    <i class="icon-base ti tabler-trash icon-26px"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
      <div class="card-header">
        <h5 class="mb-0"><i class="icon-base ti tabler-filter me-2 text-primary"></i>Filters</h5>
      </div>
      <div class="card-body">
        <div class="row g-4 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Module</label>
            <select class="form-select" id="filterModule">
              <option value="">All Modules</option>
              <option value="Patient">Patient</option>
              <option value="Doctor">Doctor</option>
              <option value="Appointment">Appointment</option>
              <option value="Lab">Lab</option>
              <option value="Pharmacy">Pharmacy</option>
              <option value="Invoice">Invoice</option>
              <option value="Payment">Payment</option>
              <option value="Settings">Settings</option>
              <option value="Department">Department</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Action</label>
            <select class="form-select" id="filterAction">
              <option value="">All Actions</option>
              <option value="created">Created</option>
              <option value="updated">Updated</option>
              <option value="deleted">Deleted</option>
              <option value="viewed">Viewed</option>
              <option value="login">Login</option>
              <option value="logout">Logout</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" id="filterFromDate" />
          </div>
          <div class="col-md-2">
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" id="filterToDate" />
          </div>
          <div class="col-md-2">
            <button onclick="applyFilters()" class="btn btn-primary w-100">
              <i class="icon-base ti tabler-search me-1"></i> Filter
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Activity Log Table --}}
    <div class="card">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Activity Log</h5>
        <form action="{{ route('settings.activity-log.clear') }}" method="POST"
          onsubmit="event.preventDefault(); confirmFormSubmit(this, 'Clear All Activity Logs?', 'Are you sure you want to clear all activity logs? This action cannot be undone.'); return false;">
          @csrf
          <button type="submit" class="btn btn-sm btn-label-danger">
            <i class="icon-base ti tabler-trash me-1"></i> Clear All
          </button>
        </form>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table border-top" id="activityLogTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Action</th>
              <th>Module</th>
              <th>Description</th>
              <th>IP Address</th>
              <th>Date & Time</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

  </div>
</div>

@section('page-script')
<script>
let dtTable;

document.addEventListener('DOMContentLoaded', function () {
  dtTable = $('#activityLogTable').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: '{{ route('settings.activity-log.data') }}',
      type: 'GET',
      data: function(d) {
        d.module    = document.getElementById('filterModule').value;
        d.action    = document.getElementById('filterAction').value;
        d.from_date = document.getElementById('filterFromDate').value;
        d.to_date   = document.getElementById('filterToDate').value;
      }
    },
    columns: [
      { data: 'id' },
      { data: 'user_name', render: d => `
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-sm">
            <span class="avatar-initial rounded-circle bg-label-primary">
              ${d.charAt(0).toUpperCase()}
            </span>
          </div>
          <span class="fw-medium">${d}</span>
        </div>
      `},
      { data: 'action', render: d => {
        const colors = {
          created: 'success',
          updated: 'warning',
          deleted: 'danger',
          viewed:  'info',
          login:   'primary',
          logout:  'secondary',
        };
        return `<span class="badge bg-label-${colors[d] || 'secondary'} text-capitalize">${d}</span>`;
      }},
      { data: 'module', render: d => `<span class="badge bg-label-primary">${d}</span>` },
      { data: 'description' },
      { data: 'ip_address', defaultContent: '—', render: d => `<small class="text-muted">${d || '—'}</small>` },
      { data: 'created_at', render: d => `<small>${d}</small>` },
    ],
    order: [[0, 'desc']],
    pageLength: 25,
  });
});

function applyFilters() {
  dtTable.ajax.reload();
}
</script>
@endsection

@endsection