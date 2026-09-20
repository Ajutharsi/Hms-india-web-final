@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Shift Handover Report - HMS')

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

<x-breadcrumb :items="[
  ['label' => 'Shift Handover', 'url' => route('shift-handover.index')],
  ['label' => 'Report'],
]" />

<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="mb-1">Shift Handover Report</h4>
    <p class="text-muted mb-0">Filter, review, and export handover records</p>
  </div>
  <a href="{{ route('shift-handover.index') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-arrow-left me-1"></i> Back</a>
</div>

{{-- Filters --}}
<div class="card mb-6">
  <div class="card-header"><h5 class="mb-0"><i class="icon-base ti tabler-filter me-2 text-primary"></i>Filters</h5></div>
  <div class="card-body">
    <form method="GET" action="{{ route('shift-handover.report') }}">
      <div class="row g-4">
        <div class="col-md-2">
          <label class="form-label">From Date</label>
          <input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">To Date</label>
          <input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ward</label>
          <select class="form-select" name="ward_id">
            <option value="">All</option>
            @foreach($wards as $ward)
            <option value="{{ $ward->id }}" @selected(request('ward_id')==$ward->id)>{{ $ward->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Shift</label>
          <select class="form-select" name="shift">
            <option value="">All</option>
            <option value="morning" @selected(request('shift')=='morning')>Morning</option>
            <option value="evening" @selected(request('shift')=='evening')>Evening</option>
            <option value="night" @selected(request('shift')=='night')>Night</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Nurse</label>
          <select class="form-select" name="nurse_id">
            <option value="">All</option>
            @foreach($nurses as $nurse)
            <option value="{{ $nurse->id }}" @selected(request('nurse_id')==$nurse->id)>{{ $nurse->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="">All</option>
            <option value="draft" @selected(request('status')=='draft')>Draft</option>
            <option value="pending_acceptance" @selected(request('status')=='pending_acceptance')>Pending Acceptance</option>
            <option value="accepted" @selected(request('status')=='accepted')>Accepted</option>
            <option value="rejected" @selected(request('status')=='rejected')>Rejected</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Patient</label>
          <select class="form-select" name="patient_id">
            <option value="">All</option>
            @foreach($patients as $p)
            <option value="{{ $p->id }}" @selected(request('patient_id')==$p->id)>{{ $p->name }} ({{ $p->mrn }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary me-2"><i class="icon-base ti tabler-search me-1"></i> Filter</button>
          <a href="{{ route('shift-handover.report') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-x me-1"></i> Clear</a>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Results ({{ $handovers->count() }})</h5>
    <div class="d-flex gap-2">
      <button onclick="exportTable('copy')" class="btn btn-sm btn-label-secondary"><i class="icon-base ti tabler-copy me-1"></i> Copy</button>
      <button onclick="exportTable('csv')" class="btn btn-sm btn-label-info"><i class="icon-base ti tabler-file-text me-1"></i> CSV</button>
      <button onclick="exportTable('excel')" class="btn btn-sm btn-label-success"><i class="icon-base ti tabler-file-spreadsheet me-1"></i> Excel</button>
      <button onclick="exportTable('print')" class="btn btn-sm btn-label-primary"><i class="icon-base ti tabler-printer me-1"></i> Print</button>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="handoverReportTable">
      <thead>
        <tr>
          <th>Handover No</th>
          <th>Ward</th>
          <th>Shift</th>
          <th>Outgoing Nurse</th>
          <th>Incoming Nurse</th>
          <th>Patients</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($handovers as $h)
        <tr style="cursor:pointer;" onclick="window.location.href='{{ route('shift-handover.show', $h->id) }}'">
          <td><span class="badge bg-label-primary">{{ $h->handover_no }}</span></td>
          <td>{{ $h->ward->name ?? 'N/A' }}</td>
          <td>{{ ucfirst($h->current_shift) }} &rarr; {{ ucfirst($h->next_shift) }}</td>
          <td>{{ $h->outgoingNurse->name ?? 'N/A' }}</td>
          <td>{{ $h->incomingNurse->name ?? 'N/A' }}</td>
          <td>{{ $h->patients->count() }}</td>
          <td>{{ $h->handover_date->format('d M Y') }}</td>
          <td>
            <span class="badge bg-label-{{ ['draft'=>'secondary','pending_acceptance'=>'warning','accepted'=>'success','rejected'=>'danger'][$h->status] ?? 'secondary' }} text-capitalize">
              {{ str_replace('_', ' ', $h->status) }}
            </span>
          </td>
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
  dtTable = $('#handoverReportTable').DataTable({
    pageLength: 25,
    order: [[6, 'desc']],
    dom: 'Bfrtip',
    buttons: [
      { extend: 'copy',  className: 'd-none' },
      { extend: 'csv',   className: 'd-none', filename: 'shift-handover-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'excel', className: 'd-none', filename: 'shift-handover-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'print', className: 'd-none', title: 'HMS — Shift Handover Report {{ now()->format("d M Y") }}' },
    ],
  });
});

function exportTable(type) {
  dtTable.button('.buttons-' + type).trigger();
}
</script>
@endsection

@endsection
