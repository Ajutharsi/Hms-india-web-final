@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Shift Handover - HMS')

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
  ['label' => 'Shift Handover'],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Dashboard stat tiles --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">Pending Handovers</span><h4 class="mb-0 mt-1">{{ $pendingCount }}</h4><small class="text-muted">Awaiting your acceptance</small></div>
        <span class="avatar-initial rounded bg-label-warning avatar"><i class="icon-base ti tabler-transfer-in icon-26px"></i></span>
      </div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">Accepted Handovers</span><h4 class="mb-0 mt-1">{{ $acceptedCount }}</h4><small class="text-muted">All time</small></div>
        <span class="avatar-initial rounded bg-label-success avatar"><i class="icon-base ti tabler-circle-check icon-26px"></i></span>
      </div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">Today's Handovers</span><h4 class="mb-0 mt-1">{{ $todayCount }}</h4><small class="text-muted">{{ now()->format('d M Y') }}</small></div>
        <span class="avatar-initial rounded bg-label-info avatar"><i class="icon-base ti tabler-calendar icon-26px"></i></span>
      </div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">High Priority Patients</span><h4 class="mb-0 mt-1">{{ $highPriorityCount }}</h4><small class="text-muted">Active handovers</small></div>
        <span class="avatar-initial rounded bg-label-danger avatar"><i class="icon-base ti tabler-alert-triangle icon-26px"></i></span>
      </div>
    </div></div>
  </div>
</div>

<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">My Current Shift</span><h4 class="mb-0 mt-1 text-capitalize">{{ $latestHandover->current_shift ?? '—' }}</h4><small class="text-muted">{{ $latestHandover?->ward?->name ?? 'No recent handover' }}</small></div>
        <span class="avatar-initial rounded bg-label-primary avatar"><i class="icon-base ti tabler-sun icon-26px"></i></span>
      </div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">Next Shift</span><h4 class="mb-0 mt-1 text-capitalize">{{ $latestHandover->next_shift ?? '—' }}</h4><small class="text-muted">Handed over to</small></div>
        <span class="avatar-initial rounded bg-label-secondary avatar"><i class="icon-base ti tabler-moon icon-26px"></i></span>
      </div>
    </div></div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div><span class="text-heading">Pending Tasks</span><h4 class="mb-0 mt-1">{{ $pendingTasksCount }}</h4><small class="text-muted">Not yet completed</small></div>
        <span class="avatar-initial rounded bg-label-warning avatar"><i class="icon-base ti tabler-checklist icon-26px"></i></span>
      </div>
    </div></div>
  </div>
  @can('create handovers')
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('shift-handover.create') }}" class="text-decoration-none">
      <div class="card h-100 bg-label-primary"><div class="card-body d-flex align-items-center justify-content-center flex-column text-center">
        <i class="icon-base ti tabler-plus icon-26px mb-2 text-primary"></i>
        <span class="fw-medium text-primary">New Shift Handover</span>
      </div></div>
    </a>
  </div>
  @endcan
</div>

{{-- Recently Completed Handovers --}}
@if($recentAccepted->count())
<div class="card mb-6">
  <div class="card-header border-bottom">
    <h6 class="mb-0"><i class="icon-base ti tabler-history me-2 text-success"></i>Recently Completed Handovers</h6>
  </div>
  <div class="p-0">
    @foreach($recentAccepted as $h)
    <a href="{{ route('shift-handover.show', $h->id) }}" class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom text-decoration-none text-reset">
      <div>
        <span class="fw-medium small">{{ $h->handover_no }}</span> &middot; {{ $h->ward->name ?? 'N/A' }}
        <div class="text-muted small">{{ $h->outgoingNurse->name ?? 'N/A' }} &rarr; {{ $h->incomingNurse->name ?? 'N/A' }}</div>
      </div>
      <span class="badge bg-label-success">Accepted {{ $h->accepted_at?->diffForHumans() }}</span>
    </a>
    @endforeach
  </div>
</div>
@endif

{{-- Handovers Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">All Handovers</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="statusFilter" class="form-select form-select-sm" style="width:160px;">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="pending_acceptance">Pending Acceptance</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
      </select>
      <a href="{{ route('shift-handover.report') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-report me-1"></i> Reports</a>
      @can('create handovers')
      <a href="{{ route('shift-handover.create') }}" class="btn btn-primary"><i class="icon-base ti tabler-plus me-1"></i> New Shift Handover</a>
      @endcan
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="handoversTable">
      <thead>
        <tr>
          <th>Handover No</th>
          <th>Ward</th>
          <th>Shift</th>
          <th>Outgoing Nurse</th>
          <th>Incoming Nurse</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@section('page-script')
<script>
let handoversTable;

document.addEventListener('DOMContentLoaded', function () {
  handoversTable = $('#handoversTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route('shift-handover.data') }}',
      type: 'GET',
      data: function (d) { d.status = document.getElementById('statusFilter').value; }
    },
    columns: [
      { data: 'handover_no', name: 'handover_no' },
      { data: 'ward_name', name: 'ward_name' },
      { data: 'shift_info', name: 'shift_info', orderable: false },
      { data: 'outgoing_name', name: 'outgoing_name', orderable: false },
      { data: 'incoming_name', name: 'incoming_name', orderable: false },
      { data: 'handover_date', name: 'handover_date' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    pageLength: 25,
    order: [[5, 'desc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>' },
  });

  document.getElementById('statusFilter').addEventListener('change', function () {
    handoversTable.ajax.reload();
  });
});
</script>
@endsection

@endsection
