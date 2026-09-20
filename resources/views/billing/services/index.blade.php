@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Billing Services - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('page-style')
<style>
#servicesTable tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
  cursor: pointer;
}
</style>
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

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
              <h4 class="mb-0">{{ \App\Models\BillingService::count() }}</h4>
              <p class="mb-0">Total Services</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-list icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ \App\Models\BillingService::where('status','active')->count() }}</h4>
              <p class="mb-0">Active Services</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ \App\Models\Invoice::count() }}</h4>
              <p class="mb-0">Total Invoices</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-file-invoice icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format(\App\Models\Invoice::sum('total'), 2) }}</h4>
              <p class="mb-0">Total Revenue</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-currency-dollar icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Services Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Billing Services</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('billing.invoices.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-file-invoice me-1"></i> Invoices
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasServiceAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Service
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="servicesTable">
      <thead>
        <tr>
          <th></th>
          <th>Code</th>
          <th>Service Name</th>
          <th>Category</th>
          <th>Price ({{ $currency }})</th>
          <th>Description</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Service Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasServiceAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Service</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('billing.services.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Service Name *</label>
          <input type="text" class="form-control @error('service_name') is-invalid @enderror" name="service_name"
            placeholder="e.g. Consultation Fee" value="{{ old('service_name') }}" required />
          @error('service_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category *</label>
          <select class="form-select @error('category') is-invalid @enderror" name="category" required>
            <option value="">Select</option>
            @foreach (['Consultation'=>'Consultation','Lab'=>'Laboratory','Pharmacy'=>'Pharmacy','Room'=>'Room/Ward','Procedure'=>'Procedure','Radiology'=>'Radiology','Other'=>'Other'] as $val => $label)
            <option value="{{ $val }}" @selected(old('category')==$val)>{{ $label }}</option>
            @endforeach
          </select>
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Price ({{ $currency }}) *</label>
          <input type="number" class="form-control @error('price') is-invalid @enderror" name="price"
            placeholder="0.00" step="0.01" min="0" value="{{ old('price') }}" required />
          @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
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

  {{-- Edit Service Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasServiceEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Service</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editServiceForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_service_id" id="edit_service_id" value="{{ old('_edit_service_id') }}" />
        <div class="mb-4">
          <label class="form-label">Service Name *</label>
          <input type="text" class="form-control @error('service_name') is-invalid @enderror" name="service_name" id="edit_service_name" value="{{ old('service_name') }}" required />
          @error('service_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category *</label>
          <select class="form-select @error('category') is-invalid @enderror" name="category" id="edit_category" required>
            <option value="">Select</option>
            @foreach (['Consultation'=>'Consultation','Lab'=>'Laboratory','Pharmacy'=>'Pharmacy','Room'=>'Room/Ward','Procedure'=>'Procedure','Radiology'=>'Radiology','Other'=>'Other'] as $val => $label)
            <option value="{{ $val }}" @selected(old('category')==$val)>{{ $label }}</option>
            @endforeach
          </select>
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Price ({{ $currency }}) *</label>
          <input type="number" class="form-control @error('price') is-invalid @enderror" name="price" id="edit_price" step="0.01" min="0" value="{{ old('price') }}" required />
          @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Description</label>
          <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="edit_description" rows="2">{{ old('description') }}</textarea>
          @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status" id="edit_status">
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

</div>

{{-- verticalMenu.json-ல் சேர்க்கு --}}

@section('page-script')
<script>
const currency = '{{ $currency }}';
document.addEventListener('DOMContentLoaded', function () {
  $('#servicesTable').DataTable({
    processing: true,
    ajax: { url: '{{ route('billing.services.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'service_code' },
      { data: 'service_name' },
      { data: 'category', render: d => `<span class="badge bg-label-primary">${d}</span>` },
      { data: 'price', render: d => currency + ' ' + parseFloat(d).toLocaleString() },
      { data: 'description', defaultContent: '—', render: d => d ? `<small class="text-muted">${d}</small>` : '—' },
      { data: 'status', render: d =>
        `<span class="badge bg-label-${d === 'active' ? 'success' : 'danger'}">${d}</span>`
      },
      { data: 'id', orderable: false, searchable: false, render: d => `
        <div class="d-flex gap-2">
          <button onclick="editService(${d})" class="btn btn-sm btn-icon btn-label-warning">
            <i class="icon-base ti tabler-edit"></i>
          </button>
          <button onclick="deleteService(${d})" class="btn btn-sm btn-icon btn-label-danger">
            <i class="icon-base ti tabler-trash"></i>
          </button>
        </div>
      `}
    ]
  });
});

function editService(id) {
  fetch(`/billing/services/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editServiceForm');
      f.action = `/billing/services/${id}`;
      f.querySelector('[name="_edit_service_id"]').value = id;
      f.querySelector('[name="service_name"]').value = d.service_name || '';
      f.querySelector('[name="category"]').value     = d.category || '';
      f.querySelector('[name="price"]').value        = d.price || '';
      f.querySelector('[name="description"]').value  = d.description || '';
      f.querySelector('[name="status"]').value       = d.status || '';
      new bootstrap.Offcanvas(document.getElementById('offcanvasServiceEdit')).show();
    });
}

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_edit_service_id'))
    document.getElementById('editServiceForm').action = '/billing/services/{{ old('_edit_service_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasServiceEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasServiceAdd')).show();
  @endif
});
</script>
@endif

function deleteService(id) {
  confirmDelete(`/billing/services/${id}`, 'Service', function() {
    $('#servicesTable').DataTable().ajax.reload(null, false);
  });
}
</script>
@endsection

@endsection