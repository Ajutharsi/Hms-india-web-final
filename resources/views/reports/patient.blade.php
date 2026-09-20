@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Patient Report - HMS')

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

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="mb-1">Patient Report</h4>
    <p class="text-muted mb-0">Patient registration data & demographics</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('reports.patients.pdf') }}?{{ http_build_query(request()->all()) }}"
      class="btn btn-danger">
      <i class="icon-base ti tabler-file-type-pdf me-1"></i> Export PDF
    </a>
    <a href="{{ route('reports.index') }}" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

{{-- Filters --}}
<div class="card mb-6">
  <div class="card-header">
    <h5 class="mb-0"><i class="icon-base ti tabler-filter me-2 text-primary"></i>Filters</h5>
  </div>
  <div class="card-body">
    <form method="GET" action="{{ route('reports.patients') }}">
      <div class="row g-4">
        <div class="col-md-3">
          <label class="form-label">From Date</label>
          <input type="date" class="form-control" name="from_date"
            value="{{ request('from_date') }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label">To Date</label>
          <input type="date" class="form-control" name="to_date"
            value="{{ request('to_date') }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Patient Type</label>
          <select class="form-select" name="patient_type">
            <option value="">All</option>
            <option value="op" {{ request('patient_type') === 'op' ? 'selected' : '' }}>Out Patient</option>
            <option value="ip" {{ request('patient_type') === 'ip' ? 'selected' : '' }}>In Patient</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="">All</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="discharged" {{ request('status') === 'discharged' ? 'selected' : '' }}>Discharged</option>
            <option value="deceased" {{ request('status') === 'deceased' ? 'selected' : '' }}>Deceased</option>
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary me-2">
            <i class="icon-base ti tabler-search me-1"></i> Filter
          </button>
          <a href="{{ route('reports.patients') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-x me-1"></i> Clear
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Stats --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Total Results</span>
            <h4 class="mb-0 mt-1">{{ $patients->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-primary avatar">
            <i class="icon-base ti tabler-users icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Active</span>
            <h4 class="mb-0 mt-1">{{ $patients->where('status','active')->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-success avatar">
            <i class="icon-base ti tabler-circle-check icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Out Patients</span>
            <h4 class="mb-0 mt-1">{{ $patients->where('patient_type','op')->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-info avatar">
            <i class="icon-base ti tabler-user icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">In Patients</span>
            <h4 class="mb-0 mt-1">{{ $patients->where('patient_type','ip')->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-warning avatar">
            <i class="icon-base ti tabler-bed icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Patient List</h5>
    <div class="d-flex gap-2">
      <button onclick="exportTable('copy')" class="btn btn-sm btn-label-secondary">
        <i class="icon-base ti tabler-copy me-1"></i> Copy
      </button>
      <button onclick="exportTable('csv')" class="btn btn-sm btn-label-info">
        <i class="icon-base ti tabler-file-text me-1"></i> CSV
      </button>
      <button onclick="exportTable('excel')" class="btn btn-sm btn-label-success">
        <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Excel
      </button>
      <button onclick="exportTable('print')" class="btn btn-sm btn-label-primary">
        <i class="icon-base ti tabler-printer me-1"></i> Print
      </button>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="patientReportTable">
      <thead>
        <tr>
          <th>#</th>
          <th>MRN</th>
          <th>Name</th>
          <th>Gender</th>
          <th>Blood Group</th>
          <th>Phone</th>
          <th>Type</th>
          <th>Status</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @foreach($patients as $index => $patient)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td><span class="badge bg-label-primary">{{ $patient->mrn }}</span></td>
          <td class="fw-medium">{{ $patient->name }}</td>
          <td class="text-capitalize">{{ $patient->gender }}</td>
          <td>{{ $patient->blood_group ?? 'N/A' }}</td>
          <td>{{ $patient->phone ?? 'N/A' }}</td>
          <td>
            <span class="badge bg-label-{{ $patient->patient_type === 'op' ? 'info' : 'warning' }}">
              {{ $patient->patient_type === 'op' ? 'Out Patient' : 'In Patient' }}
            </span>
          </td>
          <td>
            <span class="badge bg-label-{{ $patient->status === 'active' ? 'success' : ($patient->status === 'discharged' ? 'secondary' : 'danger') }}">
              {{ ucfirst($patient->status) }}
            </span>
          </td>
          <td>{{ $patient->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@section('page-script')
<script>
let dtTable;

document.addEventListener('DOMContentLoaded', function () {
  dtTable = $('#patientReportTable').DataTable({
    pageLength: 25,
    order: [[0, 'asc']],
    dom: 'Bfrtip',
    buttons: [
      { extend: 'copy',  className: 'd-none' },
      { extend: 'csv',   className: 'd-none', filename: 'patient-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'excel', className: 'd-none', filename: 'patient-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'print', className: 'd-none', title: 'HMS — Patient Report {{ now()->format("d M Y") }}' },
    ],
  });
});

function exportTable(type) {
  dtTable.button('.buttons-' + type).trigger();
}
</script>
@endsection

@endsection