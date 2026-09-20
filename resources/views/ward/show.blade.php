@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Ward Details - HMS')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

  {{-- Left --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Ward Info Card --}}
    <div class="card mb-4">
      <div class="card-body pt-6">
        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-xl mb-3">
            <span class="avatar-initial rounded-circle bg-label-primary"
              style="font-size:2rem;width:72px;height:72px;">
              <i class="icon-base ti tabler-building-hospital"></i>
            </span>
          </div>
          <h5 class="mb-1">{{ $ward->name }}</h5>
          <span class="text-muted small mb-2">{{ $ward->ward_no }}</span>
          <div class="d-flex gap-1 flex-wrap justify-content-center">
            @php
              $typeColors = ['general'=>'primary','private'=>'success','semi_private'=>'info','icu'=>'danger','nicu'=>'warning','emergency'=>'danger'];
            @endphp
            <span class="badge bg-label-{{ $typeColors[$ward->type] ?? 'secondary' }} text-capitalize">
              {{ str_replace('_', ' ', $ward->type) }}
            </span>
            <span class="badge bg-label-{{ $ward->status === 'active' ? 'success' : 'danger' }}">
              {{ ucfirst($ward->status) }}
            </span>
          </div>
        </div>

        {{-- Bed Stats --}}
        <div class="row text-center mb-4 mx-0 p-3 rounded" style="background:rgba(var(--bs-primary-rgb),0.04);">
          <div class="col-4 border-end">
            <h5 class="mb-0 text-primary">{{ $ward->total_beds }}</h5>
            <small class="text-muted">Total</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0 text-success">{{ $ward->available_beds }}</h5>
            <small class="text-muted">Available</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0 text-danger">{{ $ward->total_beds - $ward->available_beds }}</h5>
            <small class="text-muted">Occupied</small>
          </div>
        </div>

        {{-- Ward Details --}}
        <div class="mb-4">
          @foreach([
            ['label' => 'Ward No',       'value' => $ward->ward_no],
            ['label' => 'Type',          'value' => str_replace('_', ' ', $ward->type)],
            ['label' => 'Floor',         'value' => $ward->floor ?? 'N/A'],
            ['label' => 'Building',      'value' => $ward->building ?? 'N/A'],
            ['label' => 'Charge/Day',    'value' => 'LKR ' . number_format($ward->charge_per_day, 2)],
            ['label' => 'Description',   'value' => $ward->description ?? 'N/A'],
          ] as $detail)
          <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span style="font-size:0.8rem;font-weight:600;color:var(--bs-secondary-color);text-transform:uppercase;">
              {{ $detail['label'] }}
            </span>
            <span style="font-size:0.875rem;color:var(--bs-heading-color);font-weight:500;text-align:right;">
              {{ $detail['value'] }}
            </span>
          </div>
          @endforeach
        </div>

        <div class="d-flex justify-content-center gap-2">
          <a href="{{ route('ward.index') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ti tabler-arrow-left me-1"></i> Back
          </a>
          @role('super_admin|admin')
          <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWardEdit">
            <i class="icon-base ti tabler-edit me-1"></i> Edit
          </button>
          @endrole
        </div>
      </div>
    </div>

  </div>

  {{-- Right --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    {{-- Beds Grid --}}
    <div class="card mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-bed me-2 text-primary"></i>Bed Status
        </h6>
        <div class="d-flex gap-2">
          <span class="badge bg-label-success">Available</span>
          <span class="badge bg-label-danger">Occupied</span>
          <span class="badge bg-label-warning">Maintenance</span>
          <span class="badge bg-label-secondary">Reserved</span>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @foreach($ward->beds as $bed)
          @php
            $bedColors = ['available'=>'success','occupied'=>'danger','maintenance'=>'warning','reserved'=>'secondary'];
            $bedColor  = $bedColors[$bed->status] ?? 'secondary';
          @endphp
          <div class="col-md-3 col-sm-4 col-6">
            <div class="card border text-center p-3" style="border-color: var(--bs-{{ $bedColor }}) !important;">
              <i class="icon-base ti tabler-bed text-{{ $bedColor }} mb-1" style="font-size:1.5rem;"></i>
              <p class="fw-bold mb-0 small">{{ $bed->bed_no }}</p>
              <small class="text-{{ $bedColor }} text-capitalize">{{ $bed->status }}</small>
              @if($bed->currentAdmission)
              <small class="text-muted d-block" style="font-size:0.7rem;">
                {{ $bed->currentAdmission->patient->name ?? '' }}
              </small>
              @endif
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Recent Admissions --}}
    <div class="card">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-clipboard-list me-2 text-warning"></i>Recent Admissions
        </h6>
        <a href="{{ route('ward.admissions') }}" class="btn btn-sm btn-label-primary">View All</a>
      </div>
      <div class="card-body p-0">
        @forelse($ward->admissions as $admission)
        <div class="d-flex align-items-center p-3 border-bottom">
          <div class="avatar me-3">
            <img src="{{ $admission->patient?->photo_url ?? asset('images/defaults/patient.png') }}" alt=""
              class="rounded-circle" style="width:100%;height:100%;object-fit:cover;"
              onerror="this.onerror=null;this.src='{{ asset('images/defaults/patient.png') }}';" />
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0 small fw-medium">{{ $admission->patient->name ?? 'N/A' }}</h6>
            <small class="text-muted">
              Bed: {{ $admission->bed->bed_no ?? 'N/A' }} |
              Dr. {{ $admission->doctor->name ?? 'N/A' }} |
              {{ \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') }}
            </small>
          </div>
          <span class="badge bg-label-{{ $admission->status === 'admitted' ? 'success' : ($admission->status === 'discharged' ? 'secondary' : 'warning') }}">
            {{ ucfirst($admission->status) }}
          </span>
        </div>
        @empty
        <p class="text-muted text-center py-4 mb-0">No admissions found.</p>
        @endforelse
      </div>
    </div>

  </div>

</div>

{{-- Edit Offcanvas --}}
@role('super_admin|admin')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWardEdit">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Ward</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('ward.update', $ward->id) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="mb-4">
        <label class="form-label">Ward Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $ward->name) }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Ward Type *</label>
        <select class="form-select @error('type') is-invalid @enderror" name="type" required>
          @foreach(['general','private','semi_private','icu','nicu','emergency'] as $type)
          <option value="{{ $type }}" {{ old('type', $ward->type) === $type ? 'selected' : '' }}>
            {{ ucfirst(str_replace('_', ' ', $type)) }}
          </option>
          @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Floor</label>
        <input type="text" class="form-control @error('floor') is-invalid @enderror" name="floor" value="{{ old('floor', $ward->floor) }}" />
        @error('floor') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Building</label>
        <input type="text" class="form-control @error('building') is-invalid @enderror" name="building" value="{{ old('building', $ward->building) }}" />
        @error('building') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Charge Per Day (LKR)</label>
        <input type="number" class="form-control @error('charge_per_day') is-invalid @enderror" name="charge_per_day" value="{{ old('charge_per_day', $ward->charge_per_day) }}" step="0.01" min="0" />
        @error('charge_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="2">{{ old('description', $ward->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-6">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status">
          <option value="active" {{ old('status', $ward->status) === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ old('status', $ward->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="icon-base ti tabler-device-floppy me-1"></i> Update
        </button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endrole

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasWardEdit')).show();
});
</script>
@endif

@endsection