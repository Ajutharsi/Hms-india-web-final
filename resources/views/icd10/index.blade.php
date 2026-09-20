@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'ICD-10 Codes - HMS')

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

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <div>
      <h5 class="card-title mb-0">ICD-10 Diagnosis Codes</h5>
      <small class="text-muted">Used in Consultations, Insurance Claims and Scheme Billing</small>
    </div>
    @role('super_admin|admin')
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCodeAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Add Code
    </button>
    @endrole
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="codesTable">
      <thead>
        <tr>
          <th></th>
          <th>Code</th>
          <th>Description</th>
          <th>Category</th>
          <th>Status</th>
          @role('super_admin|admin')
          <th>Actions</th>
          @endrole
        </tr>
      </thead>
    </table>
  </div>

  @role('super_admin|admin')
  {{-- Add Code Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCodeAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add ICD-10 Code</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('icd10.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Code *</label>
          <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="e.g. A01.0" value="{{ old('code') }}" required />
          @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Description *</label>
          <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" value="{{ old('description') }}" required />
          @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category / Block</label>
          <input type="text" class="form-control @error('category') is-invalid @enderror" name="category" value="{{ old('category') }}" />
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Chapter</label>
          <input type="text" class="form-control @error('chapter') is-invalid @enderror" name="chapter" value="{{ old('chapter') }}" />
          @error('chapter') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Edit Code Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCodeEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit ICD-10 Code</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editCodeForm" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
          <label class="form-label">Code *</label>
          <input type="text" class="form-control" name="code" id="edit_code" required />
        </div>
        <div class="mb-4">
          <label class="form-label">Description *</label>
          <input type="text" class="form-control" name="description" id="edit_description" required />
        </div>
        <div class="mb-4">
          <label class="form-label">Category / Block</label>
          <input type="text" class="form-control" name="category" id="edit_category" />
        </div>
        <div class="mb-4">
          <label class="form-label">Chapter</label>
          <input type="text" class="form-control" name="chapter" id="edit_chapter" />
        </div>
        <div class="mb-6">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active" />
            <label class="form-check-label" for="edit_is_active">Active</label>
          </div>
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

@section('page-script')
<script>
let codesTable;
const canManage = @json(auth()->user()->hasAnyRole(['super_admin', 'admin']));

document.addEventListener('DOMContentLoaded', function () {
  const columns = [
    { data: null, defaultContent: '', orderable: false, searchable: false },
    { data: 'code', render: d => `<code>${d}</code>` },
    { data: 'description' },
    { data: 'category', defaultContent: '—' },
    { data: 'is_active', render: d => `<span class="badge bg-label-${d ? 'success' : 'secondary'}">${d ? 'Active' : 'Inactive'}</span>` },
  ];

  if (canManage) {
    columns.push({ data: 'id', orderable: false, searchable: false, render: d => `
      <div class="d-flex gap-2">
        <button onclick="editCode(${d})" class="btn btn-sm btn-icon btn-label-warning"><i class="icon-base ti tabler-edit"></i></button>
        <button onclick="deleteCode(${d})" class="btn btn-sm btn-icon btn-label-danger"><i class="icon-base ti tabler-trash"></i></button>
      </div>
    `});
  }

  codesTable = $('#codesTable').DataTable({
    processing: true,
    ajax: { url: '{{ route('icd10.data') }}', type: 'GET' },
    columns,
  });
});

function editCode(id) {
  const row = codesTable.rows().data().toArray().find(r => r.id === id);
  if (!row) return;
  const f = document.getElementById('editCodeForm');
  f.action = `/icd10/${id}`;
  document.getElementById('edit_code').value = row.code;
  document.getElementById('edit_description').value = row.description;
  document.getElementById('edit_category').value = row.category || '';
  document.getElementById('edit_chapter').value = row.chapter || '';
  document.getElementById('edit_is_active').checked = !!row.is_active;
  new bootstrap.Offcanvas(document.getElementById('offcanvasCodeEdit')).show();
}

function deleteCode(id) {
  confirmDelete(`/icd10/${id}`, 'ICD-10 Code', function() {
    codesTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasCodeAdd')).show();
});
@endif
</script>
@endsection

@endsection
