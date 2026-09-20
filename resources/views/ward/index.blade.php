@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Ward Management - HMS')

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
            <span class="text-heading">Total Wards</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Ward::count() }}</h4>
            </div>
            <small class="mb-0">All wards</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-building-hospital icon-26px"></i>
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
            <span class="text-heading">Total Beds</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Bed::count() }}</h4>
            </div>
            <small class="mb-0">All beds</small>
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
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Available Beds</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Bed::where('status','available')->count() }}</h4>
            </div>
            <small class="mb-0">Ready for admission</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-circle-check icon-26px"></i>
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
            <span class="text-heading">Occupied Beds</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Bed::where('status','occupied')->count() }}</h4>
            </div>
            <small class="mb-0">Currently occupied</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-user-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Wards Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Ward Management</h5>
    @role('super_admin|admin')
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWardAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Add Ward
    </button>
    @endrole
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="wardsTable">
      <thead>
        <tr>
          <th></th>
          <th>Ward No</th>
          <th>Name</th>
          <th>Type</th>
          <th>Floor</th>
          <th>Total Beds</th>
          <th>Available</th>
          <th>Occupied</th>
          <th>Charge/Day</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Ward Offcanvas --}}
  @role('super_admin|admin')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWardAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Ward</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('ward.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Ward Name *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="e.g. General Ward A" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Ward Type *</label>
          <select class="form-select @error('type') is-invalid @enderror" name="type" required>
            <option value="">Select Type</option>
            @foreach (['general'=>'General','private'=>'Private','semi_private'=>'Semi Private','icu'=>'ICU','nicu'=>'NICU','emergency'=>'Emergency'] as $val => $label)
            <option value="{{ $val }}" @selected(old('type')==$val)>{{ $label }}</option>
            @endforeach
          </select>
          @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Floor</label>
          <input type="text" class="form-control @error('floor') is-invalid @enderror" name="floor" placeholder="e.g. Floor 2" value="{{ old('floor') }}" />
          @error('floor') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Building</label>
          <input type="text" class="form-control @error('building') is-invalid @enderror" name="building" placeholder="e.g. Block A" value="{{ old('building') }}" />
          @error('building') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Total Beds *</label>
          <input type="number" class="form-control @error('total_beds') is-invalid @enderror" name="total_beds" min="1" placeholder="10" value="{{ old('total_beds') }}" required />
          @error('total_beds') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Charge Per Day (LKR) *</label>
          <input type="number" class="form-control @error('charge_per_day') is-invalid @enderror" name="charge_per_day" step="0.01" min="0" placeholder="1500" value="{{ old('charge_per_day') }}" required />
          @error('charge_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Description</label>
          <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="2">{{ old('description') }}</textarea>
          @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status">
            <option value="active" @selected(old('status', 'active')=='active')>Active</option>
            <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
  @endrole

  {{-- Edit Ward Offcanvas --}}
  @role('super_admin|admin')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWardEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Ward</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editWardForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_ward_id" id="edit_ward_id" value="{{ old('_edit_ward_id') }}" />
        <div class="mb-4">
          <label class="form-label">Ward Name *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="edit_ward_name" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Ward Type *</label>
          <select class="form-select @error('type') is-invalid @enderror" name="type" id="edit_ward_type" required>
            @foreach (['general'=>'General','private'=>'Private','semi_private'=>'Semi Private','icu'=>'ICU','nicu'=>'NICU','emergency'=>'Emergency'] as $val => $label)
            <option value="{{ $val }}" @selected(old('type')==$val)>{{ $label }}</option>
            @endforeach
          </select>
          @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Floor</label>
          <input type="text" class="form-control @error('floor') is-invalid @enderror" name="floor" id="edit_ward_floor" value="{{ old('floor') }}" />
          @error('floor') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Building</label>
          <input type="text" class="form-control @error('building') is-invalid @enderror" name="building" id="edit_ward_building" value="{{ old('building') }}" />
          @error('building') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Charge Per Day (LKR) *</label>
          <input type="number" class="form-control @error('charge_per_day') is-invalid @enderror" name="charge_per_day" id="edit_ward_charge" step="0.01" min="0" value="{{ old('charge_per_day') }}" />
          @error('charge_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Description</label>
          <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="edit_ward_description" rows="2">{{ old('description') }}</textarea>
          @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status" id="edit_ward_status">
            <option value="active" @selected(old('status')=='active')>Active</option>
            <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
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

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#wardsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('ward.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'ward_no', name: 'ward_no' },
      { data: 'name_display', name: 'name' },
      { data: 'type_badge', name: 'type' },
      { data: 'floor', name: 'floor' },
      { data: 'total_beds', name: 'total_beds' },
      { data: 'available_beds_badge', name: 'available_beds' },
      { data: 'occupied_beds_badge', name: 'occupied_beds_badge', orderable: false, searchable: false },
      { data: 'charge_fmt', name: 'charge_per_day' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[1, 'asc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    },
    createdRow: function(row, data) {
      $(row).css('cursor', 'pointer');
      $(row).on('click', function(e) {
        if ($(e.target).closest('.d-flex').length) return;
        window.location.href = '/ward/' + data.id;
      });
    }
  });
});

function editWard(id) {
  fetch(`/ward/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editWardForm');
      f.action = `/ward/${id}`;
      document.getElementById('edit_ward_id').value          = id;
      document.getElementById('edit_ward_name').value        = d.name || '';
      document.getElementById('edit_ward_type').value        = d.type || '';
      document.getElementById('edit_ward_floor').value       = d.floor || '';
      document.getElementById('edit_ward_building').value    = d.building || '';
      document.getElementById('edit_ward_charge').value      = d.charge_per_day || '';
      document.getElementById('edit_ward_description').value = d.description || '';
      document.getElementById('edit_ward_status').value      = d.status || '';
      new bootstrap.Offcanvas(document.getElementById('offcanvasWardEdit')).show();
    });
}

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_edit_ward_id'))
    document.getElementById('editWardForm').action = '/ward/{{ old('_edit_ward_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasWardEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasWardAdd')).show();
  @endif
});
</script>
@endif

function deleteWard(id) {
  confirmAction('Delete Ward?', 'All beds in this ward will be deleted too. This action cannot be undone.', function() {
    fetch(`/ward/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(res => res.json())
    .then(data => {
      const toastMsg = document.getElementById('successToastMsg');
      if (toastMsg) toastMsg.innerHTML = '<i class="icon-base ti tabler-check me-2"></i> Deleted successfully!';
      new bootstrap.Toast(document.getElementById('successToast')).show();
      $('#wardsTable').DataTable().ajax.reload(null, false);
    });
  });
}
</script>

@endsection