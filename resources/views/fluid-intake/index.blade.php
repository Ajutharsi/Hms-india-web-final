@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Fluid Intake - HMS')

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
            <span class="text-heading">IP Patients</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Patient::where('patient_type','ip')->where('status','active')->count() }}</h4>
            </div>
            <small>Active in-patients</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-bed icon-26px"></i>
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
            <span class="text-heading">Total Intake Today</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ number_format(\App\Models\FluidLog::whereDate('log_date', today())->where('flow_type','intake')->sum('amount_ml')) }}</h4>
            </div>
            <small>ml</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-droplet icon-26px"></i>
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
            <span class="text-heading">Total Output Today</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ number_format(\App\Models\FluidLog::whereDate('log_date', today())->where('flow_type','output')->sum('amount_ml')) }}</h4>
            </div>
            <small>ml</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-droplet-off icon-26px"></i>
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
            <span class="text-heading">Entries Today</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\FluidLog::whereDate('log_date', today())->count() }}</h4>
            </div>
            <small>Total logs</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-clipboard-list icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Search --}}
<div class="card mb-6">
  <div class="card-body py-4">
    <div class="input-group input-group-merge">
      <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
      <input type="text" class="form-control" id="searchInput" placeholder="Search by name or MRN..." />
    </div>
  </div>
</div>

{{-- Patient Tiles --}}
<div class="row g-4" id="tilesContainer">
  <div class="col-12 text-center py-5">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 text-muted">Loading patients...</p>
  </div>
</div>

<script>
let allPatients = [];

function getColor(name) {
  const colors = ['primary','success','danger','warning','info','secondary'];
  let sum = 0;
  for (let c of name) sum += c.charCodeAt(0);
  return colors[sum % colors.length];
}

function renderTiles() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const filtered = allPatients.filter(p =>
    !search || p.name.toLowerCase().includes(search) || p.mrn.toLowerCase().includes(search)
  );

  const container = document.getElementById('tilesContainer');

  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="col-12 text-center py-5">
        <i class="icon-base ti tabler-user-off" style="font-size:3rem;color:var(--bs-secondary-color);"></i>
        <p class="mt-2 text-muted">No IP patients found.</p>
      </div>`;
    return;
  }

  container.innerHTML = filtered.map(p => {
    const initial = p.name.charAt(0).toUpperCase();
    const color   = getColor(p.name);
    const balanceColor = p.balance > 0 ? 'success' : (p.balance < 0 ? 'danger' : 'secondary');

    return `
    <div class="col-xl-3 col-lg-4 col-md-6">
      <div class="card h-100" style="cursor:pointer;border:1px solid var(--bs-border-color);box-shadow:none;"
        onclick="window.location.href='/fluid-intake/${p.id}'">

        <div class="d-flex align-items-center gap-3 px-4 pt-4 pb-3" style="border-bottom:1px solid var(--bs-border-color);">
          <div class="avatar">
            <span class="avatar-initial rounded-circle bg-label-${color}"
              style="font-size:0.85rem;width:42px;height:42px;font-weight:500;">
              ${initial}
            </span>
          </div>
          <div class="flex-grow-1" style="min-width:0;">
            <p class="mb-0 fw-medium text-truncate" style="font-size:0.875rem;">${p.name}</p>
            <small class="text-muted">${p.mrn}</small>
          </div>
        </div>

        <div class="px-4 py-3 d-flex flex-column gap-2">
          <div class="d-flex justify-content-between" style="font-size:0.78rem;">
            <span class="text-muted d-flex align-items-center gap-1">
              <i class="icon-base ti tabler-building" style="font-size:0.85rem;"></i> Ward
            </span>
            <span class="fw-medium">${p.ward_name || '—'}</span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:0.78rem;">
            <span class="text-muted d-flex align-items-center gap-1">
              <i class="icon-base ti tabler-bed" style="font-size:0.85rem;"></i> Bed
            </span>
            <span class="fw-medium">${p.bed_no || '—'}</span>
          </div>
        </div>

        <div class="px-4 py-3" style="border-top:1px solid var(--bs-border-color);background:var(--bs-body-bg);">
          <div class="row text-center g-0">
            <div class="col-4 border-end">
              <small class="text-muted d-block" style="font-size:0.68rem;">Intake</small>
              <span class="fw-medium text-success" style="font-size:0.82rem;">${p.intake}ml</span>
            </div>
            <div class="col-4 border-end">
              <small class="text-muted d-block" style="font-size:0.68rem;">Output</small>
              <span class="fw-medium text-warning" style="font-size:0.82rem;">${p.output}ml</span>
            </div>
            <div class="col-4">
              <small class="text-muted d-block" style="font-size:0.68rem;">Balance</small>
              <span class="fw-medium text-${balanceColor}" style="font-size:0.82rem;">${p.balance > 0 ? '+' : ''}${p.balance}ml</span>
            </div>
          </div>
        </div>

      </div>
    </div>`;
  }).join('');
}

document.addEventListener('DOMContentLoaded', function() {
  fetch('{{ route('fluid-intake.data') }}')
    .then(res => res.json())
    .then(data => {
      allPatients = data.data;
      renderTiles();
    });

  document.getElementById('searchInput').addEventListener('input', renderTiles);
});
</script>

@endsection