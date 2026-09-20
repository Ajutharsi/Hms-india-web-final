@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Lab Tests - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
])
@endsection

@section('page-style')
<style>
#labTestsTable tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
  cursor: pointer;
}
</style>
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
])
@endsection

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
            <span class="text-heading">Total Tests</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\LabTest::count() }}</h4>
            </div>
            <small>All lab tests</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-flask icon-26px"></i>
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
            <span class="text-heading">Active Tests</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\LabTest::where('status','active')->count() }}</h4>
            </div>
            <small>Currently active</small>
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
            <span class="text-heading">Total Orders</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\LabOrder::count() }}</h4>
            </div>
            <small>All lab orders</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
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
            <span class="text-heading">Pending Orders</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\LabOrder::where('status','pending')->count() }}</h4>
            </div>
            <small>Awaiting results</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-clock icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Lab Tests Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Lab Test Catalogue</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('lab.orders.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-clipboard-list me-1"></i> Lab Orders
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLabTestAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Test
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="labTestsTable">
      <thead>
        <tr>
          <th></th>
          <th>Test Code</th>
          <th>Test Name</th>
          <th>Category</th>
          <th>Sample Type</th>
          <th>Unit</th>
          <th>Normal Range (M)</th>
          <th>Normal Range (F)</th>
          <th>Price (LKR)</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Lab Test Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLabTestAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Lab Test</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('lab.tests.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Test Name *</label>
          <input type="text" class="form-control @error('test_name') is-invalid @enderror" name="test_name" placeholder="e.g. Complete Blood Count" value="{{ old('test_name') }}" required />
          @error('test_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category *</label>
          <select class="form-select @error('category') is-invalid @enderror" name="category" required>
            <option value="">Select</option>
            @foreach (['Hematology','Biochemistry','Microbiology','Immunology','Urine Analysis','Hormones','Radiology','Other'] as $cat)
            <option value="{{ $cat }}" @selected(old('category')==$cat)>{{ $cat }}</option>
            @endforeach
          </select>
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Sample Type *</label>
          <select class="form-select @error('sample_type') is-invalid @enderror" name="sample_type" required>
            <option value="">Select</option>
            @foreach (['Blood','Urine','Stool','Sputum','Swab','Other'] as $st)
            <option value="{{ $st }}" @selected(old('sample_type')==$st)>{{ $st }}</option>
            @endforeach
          </select>
          @error('sample_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Unit</label>
          <input type="text" class="form-control @error('unit') is-invalid @enderror" name="unit" placeholder="e.g. mg/dL, g/dL" value="{{ old('unit') }}" />
          @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Normal Range (Male)</label>
          <input type="text" class="form-control @error('normal_range_male') is-invalid @enderror" name="normal_range_male" placeholder="e.g. 70-110" value="{{ old('normal_range_male') }}" />
          @error('normal_range_male') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Normal Range (Female)</label>
          <input type="text" class="form-control @error('normal_range_female') is-invalid @enderror" name="normal_range_female" placeholder="e.g. 70-110" value="{{ old('normal_range_female') }}" />
          @error('normal_range_female') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Price (LKR) *</label>
          <input type="number" class="form-control @error('price') is-invalid @enderror" name="price" placeholder="500" step="0.01" min="0" value="{{ old('price') }}" required />
          @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Edit Lab Test Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLabTestEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Lab Test</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editLabTestForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_test_id" id="edit_test_id" value="{{ old('_edit_test_id') }}" />
        <div class="mb-4">
          <label class="form-label">Test Name *</label>
          <input type="text" class="form-control @error('test_name') is-invalid @enderror" name="test_name" id="edit_test_name" value="{{ old('test_name') }}" required />
          @error('test_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category *</label>
          <select class="form-select @error('category') is-invalid @enderror" name="category" id="edit_category" required>
            <option value="">Select</option>
            @foreach (['Hematology','Biochemistry','Microbiology','Immunology','Urine Analysis','Hormones','Radiology','Other'] as $cat)
            <option value="{{ $cat }}" @selected(old('category')==$cat)>{{ $cat }}</option>
            @endforeach
          </select>
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Sample Type *</label>
          <select class="form-select @error('sample_type') is-invalid @enderror" name="sample_type" id="edit_sample_type" required>
            <option value="">Select</option>
            @foreach (['Blood','Urine','Stool','Sputum','Swab','Other'] as $st)
            <option value="{{ $st }}" @selected(old('sample_type')==$st)>{{ $st }}</option>
            @endforeach
          </select>
          @error('sample_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Unit</label>
          <input type="text" class="form-control @error('unit') is-invalid @enderror" name="unit" id="edit_unit" value="{{ old('unit') }}" />
          @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Normal Range (Male)</label>
          <input type="text" class="form-control @error('normal_range_male') is-invalid @enderror" name="normal_range_male" id="edit_normal_range_male" value="{{ old('normal_range_male') }}" />
          @error('normal_range_male') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Normal Range (Female)</label>
          <input type="text" class="form-control @error('normal_range_female') is-invalid @enderror" name="normal_range_female" id="edit_normal_range_female" value="{{ old('normal_range_female') }}" />
          @error('normal_range_female') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Price (LKR) *</label>
          <input type="number" class="form-control @error('price') is-invalid @enderror" name="price" id="edit_price" step="0.01" min="0" value="{{ old('price') }}" required />
          @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
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

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#labTestsTable').DataTable({
    processing: true,
    ajax: { url: '{{ route('lab.tests.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'test_code' },
      { data: 'test_name' },
      { data: 'category', render: d => `<span class="badge bg-label-primary">${d}</span>` },
      { data: 'sample_type', render: d => `<span class="badge bg-label-info">${d}</span>` },
      { data: 'unit', defaultContent: 'N/A' },
      { data: 'normal_range_male', defaultContent: 'N/A' },
      { data: 'normal_range_female', defaultContent: 'N/A' },
      { data: 'price', render: d => 'LKR ' + parseFloat(d).toLocaleString() },
      { data: 'status', render: d =>
        `<span class="badge bg-label-${d === 'active' ? 'success' : 'danger'}">${d}</span>`
      },
      { data: 'id', orderable: false, searchable: false, render: d => `
        <div class="d-flex gap-2">
          <button onclick="editLabTest(${d})" class="btn btn-sm btn-icon btn-label-warning">
            <i class="icon-base ti tabler-edit"></i>
          </button>
          <button onclick="deleteLabTest(${d})" class="btn btn-sm btn-icon btn-label-danger">
            <i class="icon-base ti tabler-trash"></i>
          </button>
        </div>
      `}
    ]
  });
});

function editLabTest(id) {
  fetch(`/lab/tests/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editLabTestForm');
      f.action = `/lab/tests/${id}`;
      f.querySelector('[name="_edit_test_id"]').value      = id;
      f.querySelector('[name="test_name"]').value          = d.test_name || '';
      f.querySelector('[name="category"]').value           = d.category || '';
      f.querySelector('[name="sample_type"]').value        = d.sample_type || '';
      f.querySelector('[name="unit"]').value               = d.unit || '';
      f.querySelector('[name="normal_range_male"]').value  = d.normal_range_male || '';
      f.querySelector('[name="normal_range_female"]').value = d.normal_range_female || '';
      f.querySelector('[name="price"]').value              = d.price || '';
      f.querySelector('[name="description"]').value        = d.description || '';
      f.querySelector('[name="status"]').value             = d.status || '';
      new bootstrap.Offcanvas(document.getElementById('offcanvasLabTestEdit')).show();
    });
}

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_edit_test_id'))
    document.getElementById('editLabTestForm').action = '/lab/tests/{{ old('_edit_test_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasLabTestEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasLabTestAdd')).show();
  @endif
});
</script>
@endif

function deleteLabTest(id) {
  confirmDelete(`/lab/tests/${id}`, 'Lab Test', function() {
    $('#labTestsTable').DataTable().ajax.reload(null, false);
  });
}
</script>
@endsection

@endsection