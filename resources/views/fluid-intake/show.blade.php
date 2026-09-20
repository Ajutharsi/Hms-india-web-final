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

<div class="row">

  {{-- Left Sidebar --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Patient Card --}}
    <div class="card mb-4">
      <div class="card-body pt-6">
        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-lg mb-3">
            <span class="avatar-initial rounded-circle bg-label-primary"
              style="font-size:1.5rem;width:64px;height:64px;">
              {{ strtoupper(substr($patient->name, 0, 1)) }}
            </span>
          </div>
          <h6 class="mb-1">{{ $patient->name }}</h6>
          <small class="text-muted mb-2">{{ $patient->mrn }}</small>
        </div>

        <div class="mb-4">
          <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span class="text-muted" style="font-size:0.8rem;">Ward</span>
            <span class="fw-medium" style="font-size:0.85rem;">{{ $patient->currentAdmission?->ward?->name ?? 'N/A' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span class="text-muted" style="font-size:0.8rem;">Bed</span>
            <span class="fw-medium" style="font-size:0.85rem;">{{ $patient->currentAdmission?->bed?->bed_no ?? 'N/A' }}</span>
          </div>
        </div>

        <a href="{{ route('fluid-intake.index') }}" class="btn btn-label-secondary btn-sm w-100">
          <i class="icon-base ti tabler-arrow-left me-1"></i> Back
        </a>
      </div>
    </div>

    {{-- Today's Balance Card --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-chart-pie me-2 text-primary"></i>Today's Balance</h6>
      </div>
      <div class="card-body">
        <div class="row text-center g-0 mb-3">
          <div class="col-6 border-end">
            <i class="icon-base ti tabler-droplet text-success mb-1" style="font-size:1.5rem;"></i>
            <h5 class="mb-0 text-success">{{ number_format($todayIntake) }}</h5>
            <small class="text-muted">Intake (ml)</small>
          </div>
          <div class="col-6">
            <i class="icon-base ti tabler-droplet-off text-warning mb-1" style="font-size:1.5rem;"></i>
            <h5 class="mb-0 text-warning">{{ number_format($todayOutput) }}</h5>
            <small class="text-muted">Output (ml)</small>
          </div>
        </div>
        @php $balance = $todayIntake - $todayOutput; @endphp
        <div class="text-center p-3 rounded" style="background:rgba(var(--bs-{{ $balance >= 0 ? 'success' : 'danger' }}-rgb),0.08);">
          <small class="text-muted d-block">Net Balance</small>
          <h4 class="mb-0 text-{{ $balance >= 0 ? 'success' : 'danger' }}">
            {{ $balance >= 0 ? '+' : '' }}{{ number_format($balance) }} ml
          </h4>
        </div>
      </div>
    </div>

    {{-- Quick Add --}}
    @can('edit patients')
    <div class="card">
      <div class="card-body d-flex gap-2">
        <button class="btn btn-success flex-fill" onclick="openFluidModal('intake')">
          <i class="icon-base ti tabler-plus me-1"></i> Add Intake
        </button>
        <button class="btn btn-warning flex-fill" onclick="openFluidModal('output')">
          <i class="icon-base ti tabler-plus me-1"></i> Add Output
        </button>
      </div>
    </div>
    @endcan

  </div>

  {{-- Right Content --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    <div class="card">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-history me-2 text-info"></i>Fluid Log History</h6>
      </div>
      <div class="card-body p-0">
        @forelse($fluidLogs as $date => $logs)
        <div class="px-4 pt-3 pb-1" style="background:var(--bs-body-bg);">
          <small class="fw-medium text-muted">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
        </div>
        @foreach($logs as $log)
        <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-{{ $log->flow_type === 'intake' ? 'success' : 'warning' }} text-capitalize" style="font-size:0.68rem;">
              {{ $log->flow_type }}
            </span>
            <span class="text-capitalize fw-medium small">{{ str_replace('_', ' ', $log->category) }}</span>
            <span class="fw-medium" style="font-size:0.85rem;">{{ $log->amount_ml }}ml</span>
            @if($log->notes)
            <small class="text-muted">— {{ $log->notes }}</small>
            @endif
          </div>
          <div class="d-flex align-items-center gap-2">
            <small class="text-muted">{{ \Carbon\Carbon::parse($log->log_time)->format('h:i A') }} · {{ $log->loggedBy->name ?? 'N/A' }}</small>
            @can('edit patients')
            <button onclick="deleteEntry({{ $log->id }})" class="btn btn-sm btn-icon btn-label-danger">
              <i class="icon-base ti tabler-trash icon-sm"></i>
            </button>
            @endcan
          </div>
        </div>
        @endforeach
        @empty
        <p class="text-muted text-center py-4 mb-0">No fluid logs found.</p>
        @endforelse
      </div>
    </div>

  </div>

</div>

{{-- Log Fluid Offcanvas --}}
@can('edit patients')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLogFluid">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title" id="fluidModalTitle">Add Intake</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('fluid-intake.log', $patient->id) }}" method="POST">
      @csrf
      <input type="hidden" name="flow_type" id="flow_type_input" value="{{ old('flow_type') }}" />

      <div class="mb-4">
        <label class="form-label">Category *</label>
        <select class="form-select @error('category') is-invalid @enderror" name="category" id="category_input" required>
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Amount (ml) *</label>
        <input type="number" class="form-control @error('amount_ml') is-invalid @enderror" name="amount_ml" placeholder="e.g. 250" min="1" value="{{ old('amount_ml') }}" required />
        @error('amount_ml') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Time *</label>
        <input type="time" class="form-control @error('log_time') is-invalid @enderror" name="log_time" value="{{ old('log_time', now()->format('H:i')) }}" required />
        @error('log_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-6">
        <label class="form-label">Notes</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
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
@endcan

<script>
const intakeCategories = [
  { value: 'oral', label: 'Oral (Water/Juice)' },
  { value: 'iv', label: 'IV Fluids' },
  { value: 'tube_feed', label: 'Tube Feeding' },
  { value: 'other', label: 'Other' },
];

const outputCategories = [
  { value: 'urine', label: 'Urine' },
  { value: 'vomit', label: 'Vomit' },
  { value: 'drain', label: 'Drain' },
  { value: 'stool', label: 'Stool (Liquid)' },
  { value: 'other', label: 'Other' },
];

function openFluidModal(flowType) {
  document.getElementById('fluidModalTitle').textContent = flowType === 'intake' ? 'Add Intake' : 'Add Output';
  document.getElementById('flow_type_input').value = flowType;

  const categorySelect = document.getElementById('category_input');
  const categories = flowType === 'intake' ? intakeCategories : outputCategories;
  categorySelect.innerHTML = categories.map(c => `<option value="${c.value}">${c.label}</option>`).join('');

  new bootstrap.Offcanvas(document.getElementById('offcanvasLogFluid')).show();
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('flow_type'))
    openFluidModal('{{ old('flow_type') }}');
    document.getElementById('category_input').value = '{{ old('category') }}';
  @endif
});
@endif

function deleteEntry(id) {
  confirmDelete(`/fluid-logs/${id}`, 'Entry');
}
</script>

@endsection