@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Notification Templates - HMS')

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
      <h5 class="card-title mb-0">Notification Templates</h5>
      <small class="text-muted">Manage SMS / WhatsApp / Email templates used across the system</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('notifications.index') }}" class="btn btn-label-secondary">
        <i class="icon-base ti tabler-arrow-left me-1"></i> Back to Logs
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasTemplateAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Template
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table border-top">
      <thead>
        <tr>
          <th>Name</th>
          <th>Title</th>
          <th>SMS Preview</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($templates as $t)
        <tr>
          <td><code>{{ $t->name }}</code></td>
          <td>{{ $t->title }}</td>
          <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($t->sms_template, 60) }}</small></td>
          <td>
            <span class="badge bg-label-{{ $t->is_active ? 'success' : 'secondary' }}">{{ $t->is_active ? 'Active' : 'Inactive' }}</span>
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-icon btn-label-warning"
              onclick='editTemplate(@json($t))'>
              <i class="icon-base ti tabler-edit"></i>
            </button>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No templates found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Add Template Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTemplateAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Template</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('notifications.templates.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Name (key) *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="e.g. appointment_reminder" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Title *</label>
          <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required />
          @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">SMS Template</label>
          <textarea class="form-control @error('sms_template') is-invalid @enderror" name="sms_template" rows="3">{{ old('sms_template') }}</textarea>
          @error('sms_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">WhatsApp Template</label>
          <textarea class="form-control @error('whatsapp_template') is-invalid @enderror" name="whatsapp_template" rows="3">{{ old('whatsapp_template') }}</textarea>
          @error('whatsapp_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Email Template</label>
          <textarea class="form-control @error('email_template') is-invalid @enderror" name="email_template" rows="3">{{ old('email_template') }}</textarea>
          @error('email_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Variables</label>
          <input type="text" class="form-control @error('variables') is-invalid @enderror" name="variables" placeholder="@{{patient_name}}, @{{date}}" value="{{ old('variables') }}" />
          @error('variables') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', true)) />
            <label class="form-check-label" for="is_active">Active</label>
          </div>
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

  {{-- Edit Template Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTemplateEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Template</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editTemplateForm" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
          <label class="form-label">Name (key)</label>
          <input type="text" class="form-control" id="edit_name" disabled />
        </div>
        <div class="mb-4">
          <label class="form-label">Title *</label>
          <input type="text" class="form-control" name="title" id="edit_title" required />
        </div>
        <div class="mb-4">
          <label class="form-label">SMS Template</label>
          <textarea class="form-control" name="sms_template" id="edit_sms_template" rows="3"></textarea>
        </div>
        <div class="mb-4">
          <label class="form-label">WhatsApp Template</label>
          <textarea class="form-control" name="whatsapp_template" id="edit_whatsapp_template" rows="3"></textarea>
        </div>
        <div class="mb-4">
          <label class="form-label">Email Template</label>
          <textarea class="form-control" name="email_template" id="edit_email_template" rows="3"></textarea>
        </div>
        <div class="mb-4">
          <label class="form-label">Variables</label>
          <input type="text" class="form-control" name="variables" id="edit_variables" />
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
</div>

@section('page-script')
<script>
function editTemplate(t) {
  const f = document.getElementById('editTemplateForm');
  f.action = `/notifications/templates/${t.id}`;
  document.getElementById('edit_name').value = t.name;
  document.getElementById('edit_title').value = t.title;
  document.getElementById('edit_sms_template').value = t.sms_template || '';
  document.getElementById('edit_whatsapp_template').value = t.whatsapp_template || '';
  document.getElementById('edit_email_template').value = t.email_template || '';
  document.getElementById('edit_variables').value = t.variables || '';
  document.getElementById('edit_is_active').checked = !!t.is_active;
  new bootstrap.Offcanvas(document.getElementById('offcanvasTemplateEdit')).show();
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasTemplateAdd')).show();
});
@endif
</script>
@endsection

@endsection
