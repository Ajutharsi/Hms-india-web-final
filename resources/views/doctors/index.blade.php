@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Doctors - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('page-style')
<style>
/* ===== Clickable table rows ===== */
#doctorsTable tbody tr.doctor-row-clickable {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
#doctorsTable tbody tr.doctor-row-clickable:hover {
  background-color: var(--bs-secondary-bg, rgba(105,108,255,0.06));
}

/* ===== Table avatar ===== */
.table-avatar {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid var(--bs-border-color);
  flex-shrink: 0;
  background-color: var(--bs-body-bg);
}

/* ===== Doctor tiles - 4 per row ===== */
.doctor-tile-card {
  border: 1px solid var(--bs-border-color);
  border-radius: 12px;
  padding: 1.1rem;
  height: 100%;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  background: var(--bs-card-bg);
  position: relative;
  overflow: hidden;
}

.doctor-tile-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--bs-primary), var(--bs-info));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.doctor-tile-card:hover::before {
  opacity: 1;
}

.doctor-tile-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.12);
  border-color: rgba(105,108,255,0.3);
}

.doctor-tile-avatar {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 50%;
  flex-shrink: 0;
  border: 3px solid var(--bs-border-color);
  transition: border-color 0.3s ease, transform 0.3s ease;
  background-color: var(--bs-body-bg);
}

.doctor-tile-card:hover .doctor-tile-avatar {
  border-color: var(--bs-primary);
  transform: scale(1.08);
}

.doctor-tile-name {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 0;
  color: var(--bs-heading-color);
}

.doctor-tile-specialization {
  font-size: 0.78rem;
  color: var(--bs-primary);
  font-weight: 500;
}

.doctor-tile-meta {
  font-size: 0.78rem;
}

.doctor-tile-fee {
  font-weight: 600;
  color: var(--bs-success);
  font-size: 0.85rem;
}

.doctor-tile-doc-badges {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.doctor-tile-actions {
  border-top: 1px dashed var(--bs-border-color);
  padding-top: 0.6rem;
  margin-top: auto;
}

/* Status badge within tile */
.tile-status-badge {
  font-size: 0.7rem;
  padding: 0.25rem 0.6rem;
  border-radius: 20px;
}

/* View toggle buttons */
.btn-group .btn {
  padding: 0.375rem 0.75rem;
}

/* Responsive tile adjustments */
@media (max-width: 1199.98px) {
  .doctor-tile-avatar {
    width: 64px;
    height: 64px;
  }
}

@media (max-width: 991.98px) {
  .doctor-tile-avatar {
    width: 56px;
    height: 56px;
  }
}

@media (max-width: 575.98px) {
  .doctor-tile-avatar {
    width: 48px;
    height: 48px;
  }
  .doctor-tile-name {
    font-size: 0.85rem;
  }
}
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('user_created'))
<div class="alert alert-info alert-dismissible mb-4">
  <i class="icon-base ti tabler-info-circle me-2"></i>{{ session('user_created') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <x-plan-usage-card label="Total Doctors" :used="$doctorUsage['used']" :limit="$doctorUsage['limit']" :percent="$doctorUsage['percent']" :status="$doctorUsage['status']" icon="tabler-stethoscope" color="primary" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Active Doctors</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Doctor::where('status','active')->count() }}</h4>
            </div>
            <small class="mb-0">Currently active</small>
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
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Today's Appointments</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Appointment::whereDate('appointment_date', today())->count() }}</h4>
            </div>
            <small class="mb-0">{{ now()->format('d M Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-calendar icon-26px"></i>
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
            <span class="text-heading">Inactive Doctors</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\Doctor::where('status','inactive')->count() }}</h4>
            </div>
            <small class="mb-0">Currently inactive</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-user-off icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-4">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="searchInput" placeholder="Search by name, specialization, or ID..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterSpecialization">
          <option value="">All Specializations</option>
          @foreach (['Cardiology','Neurology','Orthopedics','Pediatrics','Ophthalmology','Dentistry','Dermatology','Gynecology','General Medicine','Surgery','ENT','Psychiatry','Radiology','Oncology','Urology'] as $spec)
            <option value="{{ $spec }}">{{ $spec }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterDepartment">
          <option value="">All Departments</option>
          @foreach (\App\Models\Doctor::select('department')->distinct()->whereNotNull('department')->pluck('department') as $dept)
            <option value="{{ $dept }}">{{ $dept }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button onclick="applyFilters()" class="btn btn-primary w-100">
          <i class="icon-base ti tabler-filter me-1"></i> Filter
        </button>
        @can('create doctors')
        <button class="btn btn-success" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDoctorAdd" title="Add Doctor">
          <i class="icon-base ti tabler-plus"></i>
        </button>
        @endcan
      </div>
    </div>
  </div>
</div>

{{-- Doctors Table / Tile --}}
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center py-2">
    <h5 class="mb-0">Doctors Directory</h5>
    <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
      <button type="button" class="btn btn-label-primary" id="viewModeTable" onclick="setViewMode('table')" title="Table view">
        <i class="icon-base ti tabler-table"></i>
      </button>
      <button type="button" class="btn btn-label-secondary" id="viewModeTiles" onclick="setViewMode('tiles')" title="Tile view">
        <i class="icon-base ti tabler-layout-grid"></i>
      </button>
    </div>
  </div>

  {{-- Table view --}}
  <div id="tableViewWrap" class="card-datatable table-responsive">
    <table class="table border-top" id="doctorsTable">
      <thead>
        <tr>
          <th></th>
          <th>Doctor ID</th>
          <th>Name</th>
          <th>Specialization</th>
          <th>Qualification</th>
          <th>Phone</th>
          <th>Fee (LKR)</th>
          <th>Documents</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Tile view --}}
  <div id="tileViewWrap" class="card-body d-none">
    <div class="row g-3" id="tileGrid"></div>

    <div class="text-center py-4 d-none" id="tileLoading">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div class="text-center py-4 d-none" id="tileEmpty">
      <i class="icon-base ti tabler-users-group text-muted" style="font-size:2rem;"></i>
      <p class="text-muted mb-0 mt-2">No doctors found</p>
    </div>

    {{-- infinite-scroll sentinel --}}
    <div id="tileSentinel" style="height:1px;"></div>

    {{-- pagination appears after the auto-load window is exhausted --}}
    <div class="d-flex justify-content-between align-items-center mt-4 d-none" id="tilePagination">
      <small class="text-muted" id="tilePageInfo"></small>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-label-secondary" id="tilePrevBtn" onclick="tilePage(-1)">
          <i class="icon-base ti tabler-chevron-left"></i> Prev
        </button>
        <button type="button" class="btn btn-label-secondary" id="tileNextBtn" onclick="tilePage(1)">
          Next <i class="icon-base ti tabler-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Add Doctor Offcanvas --}}
@can('create doctors')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDoctorAdd" style="width:500px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Add New Doctor</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('doctors.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="mb-4 text-center">
        <img id="add_photo_preview" src="{{ asset('images/defaults/doctor.png') }}" alt="Photo preview"
          class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:2px solid var(--bs-border-color);" />
        <label class="form-label d-block">Doctor Photo</label>
        <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept=".jpg,.jpeg,.png,.webp"
          onchange="previewDoctorPhoto(this, 'add_photo_preview')" />
        <small class="text-muted">JPG, PNG or WEBP. Max 2MB.</small>
        @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Full Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Dr. John Smith" value="{{ old('name') }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Specialization *</label>
        <select class="form-select @error('specialization') is-invalid @enderror" name="specialization" required>
          <option value="">Select</option>
          @foreach (['Cardiology','Neurology','Orthopedics','Pediatrics','Ophthalmology','Dentistry','Dermatology','Gynecology','General Medicine','Surgery','ENT','Psychiatry','Radiology','Oncology','Urology'] as $spec)
            <option @selected(old('specialization')==$spec)>{{ $spec }}</option>
          @endforeach
        </select>
        @error('specialization') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Department</label>
        <input type="text" class="form-control @error('department') is-invalid @enderror" name="department" placeholder="e.g. Cardiology Dept" value="{{ old('department') }}" />
        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Qualification</label>
        <input type="text" class="form-control @error('qualification') is-invalid @enderror" name="qualification" placeholder="MBBS, MD" value="{{ old('qualification') }}" />
        @error('qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Registration Number</label>
        <input type="text" class="form-control @error('registration_number') is-invalid @enderror" name="registration_number" placeholder="REG-12345" value="{{ old('registration_number') }}" />
        @error('registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+94 77 123 4567" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Email *</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="doctor@hospital.com" value="{{ old('email') }}" required />
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Consultation Fee (LKR)</label>
        <input type="number" class="form-control @error('consultation_fee') is-invalid @enderror" name="consultation_fee" placeholder="1500" step="0.01" min="0" value="{{ old('consultation_fee') }}" />
        @error('consultation_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status">
          <option value="active" @selected(old('status', 'active')=='active')>Active</option>
          <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Documents Section --}}
      <div class="border rounded p-4 mb-4">
        <h6 class="mb-3">
          <i class="icon-base ti tabler-file-text me-2 text-primary"></i>Documents
        </h6>

        <div class="mb-4">
          <label class="form-label">
            <i class="icon-base ti tabler-license me-1 text-success"></i>
            Medical License
            <small class="text-muted">(PDF, JPG, PNG — Max 10MB)</small>
          </label>
          <input type="file" class="form-control" name="license_file"
            accept=".pdf,.jpg,.jpeg,.png" />
        </div>

        <div class="mb-4">
          <label class="form-label">
            <i class="icon-base ti tabler-certificate me-1 text-warning"></i>
            Certificate
            <small class="text-muted">(PDF, JPG, PNG — Max 10MB)</small>
          </label>
          <input type="file" class="form-control" name="certificate_file"
            accept=".pdf,.jpg,.jpeg,.png" />
        </div>

        <div class="mb-4">
          <label class="form-label">
            <i class="icon-base ti tabler-id me-1 text-info"></i>
            ID Document
            <small class="text-muted">(PDF, JPG, PNG — Max 10MB)</small>
          </label>
          <input type="file" class="form-control" name="id_file"
            accept=".pdf,.jpg,.jpeg,.png" />
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
@endcan

{{-- Edit Doctor Offcanvas --}}
@can('edit doctors')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDoctorEdit" style="width:500px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Doctor</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form id="editDoctorForm" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <input type="hidden" name="doctor_id" id="edit_doctor_id" value="{{ old('doctor_id') }}" />

      <div class="mb-4 text-center">
        <img id="edit_photo_preview" src="{{ asset('images/defaults/doctor.png') }}" alt="Photo preview"
          class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:2px solid var(--bs-border-color);" />
        <label class="form-label d-block">Doctor Photo</label>
        <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" id="edit_photo" accept=".jpg,.jpeg,.png,.webp"
          onchange="previewDoctorPhoto(this, 'edit_photo_preview')" />
        <small class="text-muted">Leave empty to keep the current photo. JPG, PNG or WEBP. Max 2MB.</small>
        @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Full Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="edit_name" value="{{ old('name') }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Specialization *</label>
        <select class="form-select @error('specialization') is-invalid @enderror" name="specialization" id="edit_specialization" required>
          <option value="">Select</option>
          @foreach (['Cardiology','Neurology','Orthopedics','Pediatrics','Ophthalmology','Dentistry','Dermatology','Gynecology','General Medicine','Surgery','ENT','Psychiatry','Radiology','Oncology','Urology'] as $spec)
            <option @selected(old('specialization')==$spec)>{{ $spec }}</option>
          @endforeach
        </select>
        @error('specialization') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Department</label>
        <input type="text" class="form-control @error('department') is-invalid @enderror" name="department" id="edit_department" value="{{ old('department') }}" />
        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Qualification</label>
        <input type="text" class="form-control @error('qualification') is-invalid @enderror" name="qualification" id="edit_qualification" value="{{ old('qualification') }}" />
        @error('qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Registration Number</label>
        <input type="text" class="form-control @error('registration_number') is-invalid @enderror" name="registration_number" id="edit_registration_number" value="{{ old('registration_number') }}" />
        @error('registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" id="edit_phone" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Email *</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="edit_email" value="{{ old('email') }}" required />
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Consultation Fee (LKR)</label>
        <input type="number" class="form-control @error('consultation_fee') is-invalid @enderror" name="consultation_fee" id="edit_consultation_fee" step="0.01" min="0" value="{{ old('consultation_fee') }}" />
        @error('consultation_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status" id="edit_status">
          <option value="active" @selected(old('status')=='active')>Active</option>
          <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Documents Section --}}
      <div class="border rounded p-4 mb-4">
        <h6 class="mb-3">
          <i class="icon-base ti tabler-file-text me-2 text-primary"></i>Documents
        </h6>

        {{-- License --}}
        <div class="mb-4">
          <label class="form-label">
            <i class="icon-base ti tabler-license me-1 text-success"></i>
            Medical License
          </label>
          <div id="edit_license_current" class="mb-2 d-none">
            <div class="d-flex align-items-center gap-2 p-2 border rounded">
              <i class="icon-base ti tabler-file text-success"></i>
              <span class="small" id="edit_license_name"></span>
              <a id="edit_license_download" href="#" class="btn btn-sm btn-label-success ms-auto py-0 px-2">
                <i class="icon-base ti tabler-download icon-sm"></i>
              </a>
            </div>
          </div>
          <input type="file" class="form-control" name="license_file"
            accept=".pdf,.jpg,.jpeg,.png" />
          <small class="text-muted">Upload new file to replace existing</small>
        </div>

        {{-- Certificate --}}
        <div class="mb-4">
          <label class="form-label">
            <i class="icon-base ti tabler-certificate me-1 text-warning"></i>
            Certificate
          </label>
          <div id="edit_certificate_current" class="mb-2 d-none">
            <div class="d-flex align-items-center gap-2 p-2 border rounded">
              <i class="icon-base ti tabler-file text-warning"></i>
              <span class="small" id="edit_certificate_name"></span>
              <a id="edit_certificate_download" href="#" class="btn btn-sm btn-label-warning ms-auto py-0 px-2">
                <i class="icon-base ti tabler-download icon-sm"></i>
              </a>
            </div>
          </div>
          <input type="file" class="form-control" name="certificate_file"
            accept=".pdf,.jpg,.jpeg,.png" />
          <small class="text-muted">Upload new file to replace existing</small>
        </div>

        {{-- ID --}}
        <div class="mb-4">
          <label class="form-label">
            <i class="icon-base ti tabler-id me-1 text-info"></i>
            ID Document
          </label>
          <div id="edit_id_current" class="mb-2 d-none">
            <div class="d-flex align-items-center gap-2 p-2 border rounded">
              <i class="icon-base ti tabler-file text-info"></i>
              <span class="small" id="edit_id_name"></span>
              <a id="edit_id_download" href="#" class="btn btn-sm btn-label-info ms-auto py-0 px-2">
                <i class="icon-base ti tabler-download icon-sm"></i>
              </a>
            </div>
          </div>
          <input type="file" class="form-control" name="id_file"
            accept=".pdf,.jpg,.jpeg,.png" />
          <small class="text-muted">Upload new file to replace existing</small>
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
@endcan

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('doctor_id'))
    document.getElementById('editDoctorForm').action = '/doctors/{{ old('doctor_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasDoctorEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasDoctorAdd')).show();
  @endif
});
</script>
@endif

<script>
let doctorsTable;
let currentView = 'table';

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function doctorShowUrl(id) {
  return `/doctors/${id}`;
}

// Helper function to safely get photo URL with fallback
function getPhotoUrl(row) {
  if (row.photo_url && row.photo_url !== '' && row.photo_url !== 'null' && row.photo_url !== 'undefined') {
    return row.photo_url;
  }
  return '{{ asset('images/defaults/doctor.png') }}';
}

document.addEventListener('DOMContentLoaded', function () {
  // Initialize DataTable
  doctorsTable = $('#doctorsTable').DataTable({
    processing: true,
    serverSide: true,
    rowId: 'id',
    ajax: {
      url: '{{ route('doctors.data') }}',
      type: 'GET',
      data: function (d) {
        d.specialization = document.getElementById('filterSpecialization').value;
        d.status = document.getElementById('filterStatus').value;
        d.department = document.getElementById('filterDepartment').value;
      }
    },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'doctor_id', name: 'doctor_id' },
      { 
        data: 'name', 
        name: 'name',
        render: function (d, type, row) {
          if (type !== 'display') return d;
          // Get photo URL with fallback
          var photoUrl = '{{ asset('images/defaults/doctor.png') }}';
          if (row.photo_url && row.photo_url !== '' && row.photo_url !== 'null') {
            photoUrl = row.photo_url;
          }
          return `<div class="d-flex align-items-center gap-2">
            <img src="${photoUrl}" class="table-avatar" alt="${escapeHtml(d)}"
                 onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}'">
            <span class="fw-medium">${escapeHtml(d)}</span>
          </div>`;
        }
      },
      { data: 'specialization', name: 'specialization', defaultContent: '—' },
      { data: 'qualification', name: 'qualification', defaultContent: 'N/A' },
      { data: 'phone', name: 'phone', defaultContent: 'N/A' },
      { 
        data: 'consultation_fee', 
        name: 'consultation_fee',
        render: function(d) {
          return d ? 'LKR ' + parseFloat(d).toLocaleString() : '—';
        }
      },
      { data: 'doc_badges', name: 'doc_badges', orderable: false, searchable: false },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[2, 'asc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    },
    createdRow: function (row, data) {
      row.classList.add('doctor-row-clickable');
      row.dataset.doctorId = data.id;
    }
  });

  // Delegated click handler for table rows
  $('#doctorsTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('a, button, select, input, .no-row-click').length) return;
    const id = this.dataset.doctorId;
    if (id) window.location.href = doctorShowUrl(id);
  });

  // Search with debounce
  let searchDebounce;
  document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      if (currentView === 'tiles') { tiles.reset(); } else { doctorsTable.search(this.value).draw(); }
    }, 300);
  });

  // Initialize tile view
  initTileView();
  setViewMode(localStorage.getItem('doctors.viewMode') || 'table', true);

  // Reset photo preview on add offcanvas open
  const addOffcanvas = document.getElementById('offcanvasDoctorAdd');
  if (addOffcanvas) {
    addOffcanvas.addEventListener('show.bs.offcanvas', function () {
      document.getElementById('add_photo_preview').src = '{{ asset('images/defaults/doctor.png') }}';
    });
  }
});

function applyFilters() {
  if (currentView === 'tiles') { tiles.reset(); } else { doctorsTable.draw(); }
}

// ===== TILE VIEW =====
const tiles = {
  BATCH: 12, // 4 columns × 3 rows
  MAX_AUTO_BATCHES: 5,
  windowStart: 0,
  loaded: 0,
  total: 0,
  busy: false,
  done: false,
  draw: 0,

  windowSize() { return this.BATCH * this.MAX_AUTO_BATCHES; },

  params(start) {
    const p = new URLSearchParams({
      draw: ++this.draw,
      start: start,
      length: this.BATCH,
      'search[value]': document.getElementById('searchInput').value,
      'order[0][column]': 2,
      'order[0][dir]': 'asc',
      'columns[2][data]': 'name',
      'columns[2][name]': 'name',
      'columns[2][searchable]': 'true',
      'columns[2][orderable]': 'true',
      specialization: document.getElementById('filterSpecialization').value,
      status: document.getElementById('filterStatus').value,
      department: document.getElementById('filterDepartment').value,
    });
    return p.toString();
  },

  reset() {
    this.windowStart = 0;
    this.newWindow();
  },

  newWindow() {
    this.loaded = 0;
    this.done = false;
    document.getElementById('tileGrid').innerHTML = '';
    document.getElementById('tilePagination').classList.add('d-none');
    document.getElementById('tileEmpty').classList.add('d-none');
    this.loadNext();
  },

  async loadNext() {
    if (this.busy || this.done || this.loaded >= this.windowSize()) return;
    this.busy = true;
    document.getElementById('tileLoading').classList.remove('d-none');

    try {
      const start = this.windowStart + this.loaded;
      const res = await fetch('{{ route('doctors.data') }}?' + this.params(start), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json();

      this.total = json.recordsFiltered ?? 0;
      (json.data || []).forEach(row => this.renderTile(row));
      this.loaded += (json.data || []).length;

      if (this.windowStart + this.loaded >= this.total || (json.data || []).length < this.BATCH) {
        this.done = true;
      }
      if (this.loaded === 0 && this.windowStart === 0) {
        document.getElementById('tileEmpty').classList.remove('d-none');
      }

      if (this.done || this.loaded >= this.windowSize()) {
        this.showPagination();
      }
    } catch (error) {
      console.error('Error loading tiles:', error);
    } finally {
      this.busy = false;
      document.getElementById('tileLoading').classList.add('d-none');
    }
  },

  renderTile(row) {
    const col = document.createElement('div');
    col.className = 'col-6 col-md-4 col-xl-3';

    const statusClass = row.status === 'active' ? 'bg-label-success' : 'bg-label-danger';
    const statusText = row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : 'Unknown';
    
    // Get photo URL with fallback
    var photoUrl = '{{ asset('images/defaults/doctor.png') }}';
    if (row.photo_url && row.photo_url !== '' && row.photo_url !== 'null' && row.photo_url !== 'undefined') {
      photoUrl = row.photo_url;
    }

    col.innerHTML = `
      <div class="doctor-tile-card" data-doctor-id="${row.id}">
        <div class="d-flex align-items-center gap-3">
          <img class="doctor-tile-avatar" src="${photoUrl}" alt="${escapeHtml(row.name || 'Doctor')}"
               onerror="this.onerror=null; this.src='{{ asset('images/defaults/doctor.png') }}'">
          <div style="min-width:0; flex:1;">
            <p class="doctor-tile-name text-truncate">${escapeHtml(row.name || 'N/A')}</p>
            <span class="doctor-tile-specialization">${escapeHtml(row.specialization || '—')}</span>
          </div>
          <span class="tile-status-badge ${statusClass}">${statusText}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center doctor-tile-meta text-muted">
          <span><i class="icon-base ti tabler-phone me-1"></i><span class="tile-phone">${escapeHtml(row.phone || '—')}</span></span>
          <span><i class="icon-base ti tabler-id me-1"></i><span class="tile-doctor-id">${escapeHtml(row.doctor_id || '—')}</span></span>
        </div>
        <div class="d-flex justify-content-between align-items-center doctor-tile-meta">
          <span><i class="icon-base ti tabler-building me-1"></i><span class="tile-department">${escapeHtml(row.department || '—')}</span></span>
          <span class="tile-fee">${row.consultation_fee ? `<span class="doctor-tile-fee">LKR ${parseFloat(row.consultation_fee).toLocaleString()}</span>` : '—'}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <div class="doctor-tile-doc-badges tile-docs">${row.doc_badges || ''}</div>
          <span class="tile-qualification text-muted small">${escapeHtml(row.qualification || '')}</span>
        </div>
        <div class="doctor-tile-actions no-row-click">${row.actions || ''}</div>
      </div>`;

    const card = col.querySelector('.doctor-tile-card');

    // Click handler
    card.addEventListener('click', function (e) {
      if (e.target.closest('a, button, select, input, .no-row-click')) return;
      window.location.href = doctorShowUrl(row.id);
    });

    document.getElementById('tileGrid').appendChild(col);
  },

  showPagination() {
    const totalPages = Math.max(1, Math.ceil(this.total / this.windowSize()));
    const page = Math.floor(this.windowStart / this.windowSize()) + 1;
    if (totalPages <= 1 && this.done && this.windowStart === 0) return;

    document.getElementById('tilePagination').classList.remove('d-none');
    document.getElementById('tilePageInfo').textContent =
      `Page ${page} of ${totalPages} · ${this.total} doctors`;
    document.getElementById('tilePrevBtn').disabled = page <= 1;
    document.getElementById('tileNextBtn').disabled = page >= totalPages;
  },
};

function tilePage(dir) {
  const next = tiles.windowStart + dir * tiles.windowSize();
  if (next < 0 || next >= tiles.total) return;
  tiles.windowStart = next;
  tiles.newWindow();
  document.getElementById('tileViewWrap').scrollIntoView({ behavior: 'smooth' });
}

function initTileView() {
  new IntersectionObserver(entries => {
    if (entries[0].isIntersecting && currentView === 'tiles') tiles.loadNext();
  }, { rootMargin: '200px' }).observe(document.getElementById('tileSentinel'));
}

function setViewMode(mode, skipLoad = false) {
  currentView = mode;
  localStorage.setItem('doctors.viewMode', mode);

  document.getElementById('tableViewWrap').classList.toggle('d-none', mode === 'tiles');
  document.getElementById('tileViewWrap').classList.toggle('d-none', mode !== 'tiles');
  document.getElementById('viewModeTable').className = 'btn ' + (mode === 'table' ? 'btn-label-primary' : 'btn-label-secondary');
  document.getElementById('viewModeTiles').className = 'btn ' + (mode === 'tiles' ? 'btn-label-primary' : 'btn-label-secondary');

  if (mode === 'tiles' && document.getElementById('tileGrid').children.length === 0) {
    tiles.reset();
  } else if (mode === 'table' && !skipLoad) {
    doctorsTable.search(document.getElementById('searchInput').value).draw();
  }
}

function previewDoctorPhoto(input, previewId) {
  const img = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) { 
      img.src = e.target.result; 
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    img.src = '{{ asset('images/defaults/doctor.png') }}';
  }
}

function editDoctor(id) {
  fetch(`/doctors/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editDoctorForm');
      f.action = `/doctors/${id}`;
      f.querySelector('[name="doctor_id"]').value = id;
      f.querySelector('[name="name"]').value = d.name || '';
      f.querySelector('[name="specialization"]').value = d.specialization || '';
      f.querySelector('[name="department"]').value = d.department || '';
      f.querySelector('[name="qualification"]').value = d.qualification || '';
      f.querySelector('[name="registration_number"]').value = d.registration_number || '';
      f.querySelector('[name="phone"]').value = d.phone || '';
      f.querySelector('[name="email"]').value = d.email || '';
      f.querySelector('[name="consultation_fee"]').value = d.consultation_fee || '';
      f.querySelector('[name="status"]').value = d.status || 'active';
      f.querySelector('[name="photo"]').value = '';

      // Photo preview with fallback
      var photoUrl = '{{ asset('images/defaults/doctor.png') }}';
      if (d.photo_url && d.photo_url !== '' && d.photo_url !== 'null') {
        photoUrl = d.photo_url;
      }
      document.getElementById('edit_photo_preview').src = photoUrl;

      // License
      if (d.license_file_name) {
        document.getElementById('edit_license_current').classList.remove('d-none');
        document.getElementById('edit_license_name').textContent = d.license_file_name;
        document.getElementById('edit_license_download').href = `/doctors/${id}/download/license`;
      } else {
        document.getElementById('edit_license_current').classList.add('d-none');
      }

      // Certificate
      if (d.certificate_file_name) {
        document.getElementById('edit_certificate_current').classList.remove('d-none');
        document.getElementById('edit_certificate_name').textContent = d.certificate_file_name;
        document.getElementById('edit_certificate_download').href = `/doctors/${id}/download/certificate`;
      } else {
        document.getElementById('edit_certificate_current').classList.add('d-none');
      }

      // ID
      if (d.id_file_name) {
        document.getElementById('edit_id_current').classList.remove('d-none');
        document.getElementById('edit_id_name').textContent = d.id_file_name;
        document.getElementById('edit_id_download').href = `/doctors/${id}/download/id`;
      } else {
        document.getElementById('edit_id_current').classList.add('d-none');
      }

      new bootstrap.Offcanvas(document.getElementById('offcanvasDoctorEdit')).show();
    })
    .catch(error => {
      console.error('Error loading doctor data:', error);
    });
}

function deleteDoctor(id) {
  if (confirm('Are you sure you want to delete this doctor?')) {
    fetch(`/doctors/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (currentView === 'tiles') { 
          tiles.reset(); 
        } else { 
          doctorsTable.ajax.reload(null, false); 
        }
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible mb-4';
        alert.innerHTML = `
          <i class="icon-base ti tabler-check me-2"></i>${data.message}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.content-wrapper').prepend(alert);
        setTimeout(() => alert.remove(), 5000);
      }
    })
    .catch(error => {
      console.error('Error deleting doctor:', error);
    });
  }
}
</script>

@endsection