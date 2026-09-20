@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'User Management - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
            <span class="text-heading">Total Users</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\User::count() }}</h4>
            </div>
            <small>All system users</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-shield-lock icon-26px"></i>
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
            <span class="text-heading">Super Admins</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\User::role('super_admin')->count() }}</h4>
            </div>
            <small>Full access</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-crown icon-26px"></i>
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
            <span class="text-heading">Admins</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\User::role('admin')->count() }}</h4>
            </div>
            <small>Admin access</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-user-shield icon-26px"></i>
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
            <span class="text-heading">Current User</span>
            <div class="d-flex align-items-center my-1">
              <h6 class="mb-0 me-2">{{ auth()->user()->name }}</h6>
            </div>
            <small class="text-capitalize">{{ str_replace('_',' ', auth()->user()->roles->first()?->name ?? '') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-user-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-plan-usage-card label="Plan Usage" :used="$userUsage['used']" :limit="$userUsage['limit']" :percent="$userUsage['percent']" :status="$userUsage['status']" icon="tabler-gauge" color="info" />
  </div>
</div>

{{-- Users Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">System Users</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasUserAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Add User
    </button>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="usersTable">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add User Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasUserAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add User</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Full Name *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Email *</label>
          <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required />
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+94 77 123 4567" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
          <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
       <div class="mb-4">
  <label class="form-label">Role *</label>
  <select class="form-select @error('role') is-invalid @enderror" name="role" required>
    <option value="">Select Role</option>
    <option value="super_admin" @selected(old('role')=='super_admin')>Super Admin</option>
    <option value="admin" @selected(old('role')=='admin')>Admin</option>
    <option value="doctor" @selected(old('role')=='doctor')>Doctor</option>
    <option value="nurse" @selected(old('role')=='nurse')>Nurse</option>
    <option value="receptionist" @selected(old('role')=='receptionist')>Receptionist</option>
    <option value="lab_assistant" @selected(old('role')=='lab_assistant')>Lab Assistant</option>
    <option value="pharmacist" @selected(old('role')=='pharmacist')>Pharmacist</option>
    <option value="patient" @selected(old('role')=='patient')>Patient</option>
  </select>
  @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
        <div class="mb-4">
          <label class="form-label">Password *</label>
          <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required />
          @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Confirm Password *</label>
          <input type="password" class="form-control" name="password_confirmation" required />
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save User
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit User Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasUserEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit User</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editUserForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_user_id" id="edit_user_id" value="{{ old('_edit_user_id') }}" />
        <div class="mb-4">
          <label class="form-label">Full Name *</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="edit_name" value="{{ old('name') }}" required />
          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Email *</label>
          <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="edit_email" value="{{ old('email') }}" required />
          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Phone</label>
          <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" id="edit_phone" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
          <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
          @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
       <select class="form-select @error('role') is-invalid @enderror" name="role" id="edit_role" required>
  <option value="">Select Role</option>
  <option value="super_admin" @selected(old('role')=='super_admin')>Super Admin</option>
  <option value="admin" @selected(old('role')=='admin')>Admin</option>
  <option value="doctor" @selected(old('role')=='doctor')>Doctor</option>
  <option value="nurse" @selected(old('role')=='nurse')>Nurse</option>
  <option value="receptionist" @selected(old('role')=='receptionist')>Receptionist</option>
  <option value="lab_assistant" @selected(old('role')=='lab_assistant')>Lab Assistant</option>
  <option value="pharmacist" @selected(old('role')=='pharmacist')>Pharmacist</option>
  <option value="staff" @selected(old('role')=='staff')>Support Staff</option>
</select>
@error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Update
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Reset Password Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasResetPassword">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Reset Password</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="resetPasswordForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_reset_user_id" id="reset_user_id" value="{{ old('_reset_user_id') }}" />
        <p class="text-muted small mb-4" id="resetPasswordName"></p>
        <div class="mb-4">
          <label class="form-label">New Password *</label>
          <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required />
          @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Confirm Password *</label>
          <input type="password" class="form-control" name="password_confirmation" required />
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-warning">
            <i class="icon-base ti tabler-lock me-1"></i> Reset Password
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const roleColors = {
  super_admin: 'danger', admin: 'warning',
  doctor: 'primary', nurse: 'info',
  receptionist: 'success', lab_assistant: 'warning',
  pharmacist: 'danger', staff: 'secondary',
};

  $('#usersTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('users.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'name', name: 'name', render: (d, t, row) => `
        <div class="d-flex align-items-center gap-2">
          <div class="avatar">
            <span class="avatar-initial rounded-circle bg-label-${roleColors[row.role] || 'secondary'}"
              style="width:36px;height:36px;font-size:0.85rem;">
              ${row.name.charAt(0).toUpperCase()}
            </span>
          </div>
          <div>
            <p class="mb-0 fw-medium">${row.name}</p>
          </div>
        </div>` },
      { data: 'email', name: 'email' },
      { data: 'phone', name: 'phone' },
      { data: 'role', name: 'role', orderable: false, render: d => `<span class="badge bg-label-${roleColors[d] || 'secondary'} text-capitalize">${d.replace('_',' ')}</span>` },
      { data: 'created_at', name: 'created_at' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[1, 'asc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

});

function editUser(id) {
  fetch(`/users/${id}/json`)
    .then(res => res.json())
    .then(u => {
      const f = document.getElementById('editUserForm');
      f.action = `/users/${id}`;
      document.getElementById('edit_user_id').value = id;
      document.getElementById('edit_name').value  = u.name || '';
      document.getElementById('edit_email').value = u.email || '';
      document.getElementById('edit_phone').value = u.phone || '';
      document.getElementById('edit_role').value  = u.role || '';
      new bootstrap.Offcanvas(document.getElementById('offcanvasUserEdit')).show();
    });
}

function resetPassword(id) {
  fetch(`/users/${id}/json`)
    .then(res => res.json())
    .then(u => {
      const f = document.getElementById('resetPasswordForm');
      f.action = `/users/${id}/reset-password`;
      document.getElementById('reset_user_id').value = id;
      document.getElementById('resetPasswordName').textContent = 'Resetting password for: ' + u.name;
      f.reset();
      new bootstrap.Offcanvas(document.getElementById('offcanvasResetPassword')).show();
    });
}

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_reset_user_id'))
    document.getElementById('resetPasswordForm').action = '/users/{{ old('_reset_user_id') }}/reset-password';
    new bootstrap.Offcanvas(document.getElementById('offcanvasResetPassword')).show();
  @elseif(old('_edit_user_id'))
    document.getElementById('editUserForm').action = '/users/{{ old('_edit_user_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasUserEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasUserAdd')).show();
  @endif
});
</script>
@endif

function deleteUser(id) {
  confirmDelete(`/users/${id}`, 'User', function() {
    $('#usersTable').DataTable().ajax.reload(null, false);
  });
}
</script>

@endsection