@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Departments - HMS')

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
            <span class="text-heading">Total Departments</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $departments->count() }}</h4>
            </div>
            <small>All departments</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-building icon-26px"></i>
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
            <span class="text-heading">Active</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $departments->where('status','active')->count() }}</h4>
            </div>
            <small>Active departments</small>
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
</div>

{{-- Departments Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Departments</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDeptAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Add Department
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-hover">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Code</th>
          <th>Head Doctor</th>
          <th>Phone</th>
          <th>Location</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($departments as $dept)
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-primary">
                  {{ strtoupper(substr($dept->name, 0, 1)) }}
                </span>
              </div>
              <div>
                <strong>{{ $dept->name }}</strong>
                @if($dept->description)
                <small class="d-block text-muted">{{ Str::limit($dept->description, 40) }}</small>
                @endif
              </div>
            </div>
          </td>
          <td>{{ $dept->code ?? '—' }}</td>
          <td>{{ $dept->head_doctor ?? '—' }}</td>
          <td>{{ $dept->phone ?? '—' }}</td>
          <td>{{ $dept->location ?? '—' }}</td>
          <td>
            <span class="badge bg-label-{{ $dept->status === 'active' ? 'success' : 'secondary' }} text-capitalize">
              {{ $dept->status }}
            </span>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button onclick="editDept({{ $dept->id }}, '{{ $dept->name }}', '{{ $dept->code }}', '{{ $dept->description }}', '{{ $dept->head_doctor }}', '{{ $dept->phone }}', '{{ $dept->location }}', '{{ $dept->status }}')"
                class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                <i class="icon-base ti tabler-edit"></i>
              </button>
              <button onclick="deleteDept({{ $dept->id }})"
                class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                <i class="icon-base ti tabler-trash"></i>
              </button>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="text-center py-4 text-muted">No departments found.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Add Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDeptAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Department</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('settings.departments.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Department Name *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Code</label>
          <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" placeholder="e.g. CARD, NEURO" value="{{ old('code') }}" />
          @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Head Doctor</label>
          <input type="text" class="form-control @error('head_doctor') is-invalid @enderror" name="head_doctor" placeholder="Dr. Name" value="{{ old('head_doctor') }}" />
          @error('head_doctor') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
          <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Location</label>
          <input type="text" class="form-control @error('location') is-invalid @enderror" name="location" placeholder="e.g. Block A, Floor 2" value="{{ old('location') }}" />
          @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Edit Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDeptEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Department</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editDeptForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_dept_id" id="edit_dept_id" value="{{ old('_edit_dept_id') }}" />
        <div class="mb-4">
          <label class="form-label">Department Name *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="edit_name" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Code</label>
          <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" id="edit_code" value="{{ old('code') }}" />
          @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Head Doctor</label>
          <input type="text" class="form-control @error('head_doctor') is-invalid @enderror" name="head_doctor" id="edit_head_doctor" value="{{ old('head_doctor') }}" />
          @error('head_doctor') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" id="edit_phone" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
          <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Location</label>
          <input type="text" class="form-control @error('location') is-invalid @enderror" name="location" id="edit_location" value="{{ old('location') }}" />
          @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

<script>
function editDept(id, name, code, description, headDoctor, phone, location, status) {
  const f = document.getElementById('editDeptForm');
  f.action = `/settings/departments/${id}`;
  document.getElementById('edit_dept_id').value     = id;
  document.getElementById('edit_name').value        = name || '';
  document.getElementById('edit_code').value        = code || '';
  document.getElementById('edit_description').value = description || '';
  document.getElementById('edit_head_doctor').value = headDoctor || '';
  document.getElementById('edit_phone').value       = phone || '';
  document.getElementById('edit_location').value    = location || '';
  document.getElementById('edit_status').value      = status || 'active';
  new bootstrap.Offcanvas(document.getElementById('offcanvasDeptEdit')).show();
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_edit_dept_id'))
    document.getElementById('editDeptForm').action = '/settings/departments/{{ old('_edit_dept_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasDeptEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasDeptAdd')).show();
  @endif
});
@endif

function deleteDept(id) {
  confirmDelete(`/settings/departments/${id}`, 'Department');
}
</script>

@endsection