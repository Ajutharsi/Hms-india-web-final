@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Patients - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('page-style')
<style>
/* ===== Clickable table rows ===== */
#patientsTable tbody tr.patient-row-clickable {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
#patientsTable tbody tr.patient-row-clickable:hover {
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

/* ===== Patient tiles - 4 per row ===== */
.patient-tile-card {
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

.patient-tile-card::before {
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

.patient-tile-card:hover::before {
  opacity: 1;
}

.patient-tile-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.12);
  border-color: rgba(105,108,255,0.3);
}

.patient-tile-avatar {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 50%;
  flex-shrink: 0;
  border: 3px solid var(--bs-border-color);
  transition: border-color 0.3s ease, transform 0.3s ease;
  background-color: var(--bs-body-bg);
}

.patient-tile-card:hover .patient-tile-avatar {
  border-color: var(--bs-primary);
  transform: scale(1.08);
}

.patient-tile-name {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 0;
  color: var(--bs-heading-color);
}

.patient-tile-mrn {
  font-size: 0.75rem;
  color: var(--bs-text-muted);
}

.patient-tile-meta {
  font-size: 0.78rem;
}

.patient-tile-actions {
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
  .patient-tile-avatar {
    width: 64px;
    height: 64px;
  }
}

@media (max-width: 991.98px) {
  .patient-tile-avatar {
    width: 56px;
    height: 56px;
  }
}

@media (max-width: 575.98px) {
  .patient-tile-avatar {
    width: 48px;
    height: 48px;
  }
  .patient-tile-name {
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
    <x-plan-usage-card label="Total Patients" :used="$patientUsage['used']" :limit="$patientUsage['limit']" :percent="$patientUsage['percent']" :status="$patientUsage['status']" icon="tabler-users" color="primary" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Active</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $activePatients }}</h4>
            </div>
            <small>Currently active</small>
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
            <span class="text-heading">In Patients</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $ipPatients }}</h4>
            </div>
            <small>IP patients</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
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
            <span class="text-heading">Out Patients</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $opPatients }}</h4>
            </div>
            <small>OP patients</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-user icon-26px"></i>
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
          <input type="text" class="form-control" id="searchInput" placeholder="Search by name or MRN..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="discharged">Discharged</option>
          <option value="deceased">Deceased</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterType">
          <option value="">All Types</option>
          <option value="op">Out Patient</option>
          <option value="ip">In Patient</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterGender">
          <option value="">All Gender</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button onclick="applyFilters()" class="btn btn-primary w-100">
          <i class="icon-base ti tabler-filter me-1"></i> Filter
        </button>
        @can('create patients')
        <button class="btn btn-success" data-bs-toggle="offcanvas" data-bs-target="#offcanvasPatientAdd" title="Add Patient">
          <i class="icon-base ti tabler-plus"></i>
        </button>
        @endcan
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center py-2">
    <h5 class="mb-0">Patients</h5>
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
    <table class="table border-top" id="patientsTable">
      <thead>
        <tr>
          <th></th>
          <th>MRN</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Gender</th>
          <th>Type</th>
          <th>Ward</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  <div id="tileViewWrap" class="card-body d-none">
    <div class="row g-3" id="tileGrid"></div>

    <div class="text-center py-4 d-none" id="tileLoading">
      <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div class="text-center py-4 d-none" id="tileEmpty">
      <i class="icon-base ti tabler-users-group text-muted" style="font-size:2rem;"></i>
      <p class="text-muted mb-0 mt-2">No patients found</p>
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

{{-- Add Patient Offcanvas --}}
@can('create patients')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPatientAdd">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Add New Patient</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('patients.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="mb-4 text-center">
        <img id="add_photo_preview" src="{{ asset('images/defaults/patient.png') }}" alt="Photo preview"
          class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:2px solid var(--bs-border-color);" />
        <label class="form-label d-block">Patient Photo</label>
        <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept=".jpg,.jpeg,.png,.webp"
          onchange="previewPatientPhoto(this, 'add_photo_preview')" />
        <small class="text-muted">JPG, PNG or WEBP. Max 2MB.</small>
        @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Full Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="John Doe" value="{{ old('name') }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" value="{{ old('dob') }}" />
        @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Gender *</label>
        <select class="form-select @error('gender') is-invalid @enderror" name="gender" required>
          <option value="">Select Gender</option>
          <option value="male" @selected(old('gender')=='male')>Male</option>
          <option value="female" @selected(old('gender')=='female')>Female</option>
          <option value="other" @selected(old('gender')=='other')>Other</option>
        </select>
        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Blood Group</label>
        <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group">
          <option value="">Select</option>
          @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
            <option value="{{ $bg }}" @selected(old('blood_group')==$bg)>{{ $bg }}</option>
          @endforeach
        </select>
        @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+94 77 123 4567" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" />
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Address</label>
        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2">{{ old('address') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Emergency Contact Name</label>
        <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" />
        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Emergency Contact Number</label>
        <input type="tel" class="form-control @error('emergency_contact') is-invalid @enderror" name="emergency_contact" value="{{ old('emergency_contact') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('emergency_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Nationality</label>
        <input type="text" class="form-control @error('nationality') is-invalid @enderror" name="nationality" value="{{ old('nationality', 'Sri Lankan') }}" />
        @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Marital Status</label>
        <select class="form-select @error('marital_status') is-invalid @enderror" name="marital_status">
          <option value="">Select</option>
          <option value="single" @selected(old('marital_status')=='single')>Single</option>
          <option value="married" @selected(old('marital_status')=='married')>Married</option>
          <option value="divorced" @selected(old('marital_status')=='divorced')>Divorced</option>
          <option value="widowed" @selected(old('marital_status')=='widowed')>Widowed</option>
        </select>
        @error('marital_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Occupation</label>
        <input type="text" class="form-control @error('occupation') is-invalid @enderror" name="occupation" value="{{ old('occupation') }}" />
        @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Patient Type *</label>
        <select class="form-select @error('patient_type') is-invalid @enderror" name="patient_type" required>
          <option value="op" @selected(old('patient_type', 'op')=='op')>Out Patient (OP)</option>
          <option value="ip" @selected(old('patient_type')=='ip')>In Patient (IP)</option>
        </select>
        @error('patient_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-6">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status">
          <option value="active" @selected(old('status', 'active')=='active')>Active</option>
          <option value="discharged" @selected(old('status')=='discharged')>Discharged</option>
          <option value="deceased" @selected(old('status')=='deceased')>Deceased</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="icon-base ti tabler-device-floppy me-1"></i> Save Patient
        </button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endcan

{{-- Edit Patient Offcanvas --}}
@can('edit patients')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPatientEdit">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Patient</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form id="editPatientForm" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <input type="hidden" name="patient_id" id="edit_patient_id" value="{{ old('patient_id') }}" />
      <div class="mb-4 text-center">
        <img id="edit_photo_preview" src="{{ asset('images/defaults/patient.png') }}" alt="Photo preview"
          class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:2px solid var(--bs-border-color);" />
        <label class="form-label d-block">Patient Photo</label>
        <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" id="edit_photo" accept=".jpg,.jpeg,.png,.webp"
          onchange="previewPatientPhoto(this, 'edit_photo_preview')" />
        <small class="text-muted">Leave empty to keep the current photo. JPG, PNG or WEBP. Max 2MB.</small>
        @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Full Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="edit_name" value="{{ old('name') }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" id="edit_dob" value="{{ old('dob') }}" />
        @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Gender *</label>
        <select class="form-select @error('gender') is-invalid @enderror" name="gender" id="edit_gender" required>
          <option value="male" @selected(old('gender')=='male')>Male</option>
          <option value="female" @selected(old('gender')=='female')>Female</option>
          <option value="other" @selected(old('gender')=='other')>Other</option>
        </select>
        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Blood Group</label>
        <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group" id="edit_blood_group">
          <option value="">Select</option>
          @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
            <option value="{{ $bg }}" @selected(old('blood_group')==$bg)>{{ $bg }}</option>
          @endforeach
        </select>
        @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" id="edit_phone" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="edit_email" value="{{ old('email') }}" />
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Address</label>
        <textarea class="form-control @error('address') is-invalid @enderror" name="address" id="edit_address" rows="2">{{ old('address') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Emergency Contact Name</label>
        <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" name="emergency_contact_name" id="edit_emergency_contact_name" value="{{ old('emergency_contact_name') }}" />
        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Emergency Contact Number</label>
        <input type="tel" class="form-control @error('emergency_contact') is-invalid @enderror" name="emergency_contact" id="edit_emergency_contact" value="{{ old('emergency_contact') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('emergency_contact') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Nationality</label>
        <input type="text" class="form-control @error('nationality') is-invalid @enderror" name="nationality" id="edit_nationality" value="{{ old('nationality') }}" />
        @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Marital Status</label>
        <select class="form-select @error('marital_status') is-invalid @enderror" name="marital_status" id="edit_marital_status">
          <option value="">Select</option>
          <option value="single" @selected(old('marital_status')=='single')>Single</option>
          <option value="married" @selected(old('marital_status')=='married')>Married</option>
          <option value="divorced" @selected(old('marital_status')=='divorced')>Divorced</option>
          <option value="widowed" @selected(old('marital_status')=='widowed')>Widowed</option>
        </select>
        @error('marital_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Occupation</label>
        <input type="text" class="form-control @error('occupation') is-invalid @enderror" name="occupation" id="edit_occupation" value="{{ old('occupation') }}" />
        @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Patient Type *</label>
        <select class="form-select @error('patient_type') is-invalid @enderror" name="patient_type" id="edit_patient_type" required>
          <option value="op" @selected(old('patient_type')=='op')>Out Patient (OP)</option>
          <option value="ip" @selected(old('patient_type')=='ip')>In Patient (IP)</option>
        </select>
        @error('patient_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-6">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status" id="edit_status">
          <option value="active" @selected(old('status')=='active')>Active</option>
          <option value="discharged" @selected(old('status')=='discharged')>Discharged</option>
          <option value="deceased" @selected(old('status')=='deceased')>Deceased</option>
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
@endcan

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('patient_id'))
    document.getElementById('editPatientForm').action = '/patients/{{ old('patient_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasPatientEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasPatientAdd')).show();
  @endif
});
</script>
@endif

<script>
let patientsTable;
let currentView = 'table';

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function patientShowUrl(id) {
  return `/patients/${id}`;
}

// Helper function to safely get photo URL with fallback
function getPhotoUrl(row) {
  if (row.photo_url && row.photo_url !== '' && row.photo_url !== 'null') {
    return row.photo_url;
  }
  return '{{ asset('images/defaults/patient.png') }}';
}

document.addEventListener('DOMContentLoaded', function () {
  patientsTable = $('#patientsTable').DataTable({
    processing: true,
    serverSide: true,
    rowId: 'id',
    ajax: {
      url: '{{ route('patients.data') }}',
      type: 'GET',
      data: function (d) {
        d.status       = document.getElementById('filterStatus').value;
        d.patient_type = document.getElementById('filterType').value;
        d.gender       = document.getElementById('filterGender').value;
      }
    },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'mrn', name: 'mrn' },
      { 
        data: 'name', 
        name: 'name', 
        orderable: false, 
        render: function (d, type, row) {
          if (type !== 'display') return d;
          const photoUrl = getPhotoUrl(row);
          return `<div class="d-flex align-items-center gap-2">
            <img src="${photoUrl}" class="table-avatar" alt="${escapeHtml(d)}" 
                 onerror="this.onerror=null; this.src='{{ asset('images/defaults/patient.png') }}'">
            <span class="fw-medium">${escapeHtml(d)}</span>
          </div>`;
        }
      },
      { data: 'phone', name: 'phone', defaultContent: '—', orderable: false },
      { data: 'gender', name: 'gender', render: d => d ? d.charAt(0).toUpperCase() + d.slice(1) : '—' },
      { data: 'type_badge', name: 'patient_type', orderable: false },
      { data: 'ward_name', name: 'ward_name', orderable: false, searchable: false },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    pageLength: 25,
    order: [[1, 'asc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    },
    createdRow: function (row, data) {
      row.classList.add('patient-row-clickable');
      row.dataset.patientId = data.id;
    }
  });

  // Delegated click handler
  $('#patientsTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('a, button, select, input, .no-row-click').length) return;
    const id = this.dataset.patientId;
    if (id) window.location.href = patientShowUrl(id);
  });

  let searchDebounce;
  document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      if (currentView === 'tiles') { tiles.reset(); } else { patientsTable.search(this.value).draw(); }
    }, 300);
  });

  initTileView();
  setViewMode(localStorage.getItem('patients.viewMode') || 'table', true);

  const addOffcanvas = document.getElementById('offcanvasPatientAdd');
  if (addOffcanvas) {
    addOffcanvas.addEventListener('show.bs.offcanvas', function () {
      document.getElementById('add_photo_preview').src = '{{ asset('images/defaults/patient.png') }}';
    });
  }
});

function applyFilters() {
  if (currentView === 'tiles') { tiles.reset(); } else { patientsTable.draw(); }
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
      'order[0][column]': 1, 'order[0][dir]': 'asc',
      'columns[1][data]': 'mrn', 'columns[1][name]': 'mrn',
      'columns[1][searchable]': 'true', 'columns[1][orderable]': 'true',
      status: document.getElementById('filterStatus').value,
      patient_type: document.getElementById('filterType').value,
      gender: document.getElementById('filterGender').value,
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
      const res   = await fetch('{{ route('patients.data') }}?' + this.params(start), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json  = await res.json();

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

    const statusClass = row.status === 'active' ? 'bg-label-success' : 
                        row.status === 'discharged' ? 'bg-label-warning' : 'bg-label-danger';
    const statusText = row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : 'Unknown';
    const photoUrl = getPhotoUrl(row);
    const genderIcon = row.gender === 'male' ? 'tabler-gender-male' : 
                       row.gender === 'female' ? 'tabler-gender-female' : 'tabler-gender-neutrois';
    const typeClass = row.patient_type === 'ip' ? 'bg-label-info' : 'bg-label-primary';

    col.innerHTML = `
      <div class="patient-tile-card" data-patient-id="${row.id}">
        <div class="d-flex align-items-center gap-3">
          <img class="patient-tile-avatar" src="${photoUrl}" alt="${escapeHtml(row.name || 'Patient')}"
               onerror="this.onerror=null; this.src='{{ asset('images/defaults/patient.png') }}'">
          <div style="min-width:0; flex:1;">
            <p class="patient-tile-name text-truncate">${escapeHtml(row.name || 'N/A')}</p>
            <small class="text-muted patient-tile-mrn">MRN: ${escapeHtml(row.mrn || '—')}</small>
          </div>
          <span class="tile-status-badge ${statusClass}">${statusText}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center patient-tile-meta text-muted">
          <span><i class="icon-base ti tabler-phone me-1"></i><span class="tile-phone">${escapeHtml(row.phone || '—')}</span></span>
          <span class="text-capitalize"><i class="icon-base ti ${genderIcon} me-1"></i><span class="tile-gender">${escapeHtml(row.gender || '—')}</span></span>
        </div>
        <div class="d-flex justify-content-between align-items-center patient-tile-meta">
          <span><i class="icon-base ti tabler-bed me-1"></i><span class="tile-ward">${escapeHtml(row.ward_name || '—')}</span></span>
          <span class="badge ${typeClass}">${row.type_badge || '—'}</span>
        </div>
        <div class="patient-tile-actions no-row-click">${row.actions || ''}</div>
      </div>`;

    const card = col.querySelector('.patient-tile-card');

    card.addEventListener('click', function (e) {
      if (e.target.closest('a, button, select, input, .no-row-click')) return;
      window.location.href = patientShowUrl(row.id);
    });

    document.getElementById('tileGrid').appendChild(col);
  },

  showPagination() {
    const totalPages = Math.max(1, Math.ceil(this.total / this.windowSize()));
    const page       = Math.floor(this.windowStart / this.windowSize()) + 1;
    if (totalPages <= 1 && this.done && this.windowStart === 0) return;

    document.getElementById('tilePagination').classList.remove('d-none');
    document.getElementById('tilePageInfo').textContent =
      `Page ${page} of ${totalPages} · ${this.total} patients`;
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
  localStorage.setItem('patients.viewMode', mode);

  document.getElementById('tableViewWrap').classList.toggle('d-none', mode === 'tiles');
  document.getElementById('tileViewWrap').classList.toggle('d-none', mode !== 'tiles');
  document.getElementById('viewModeTable').className = 'btn ' + (mode === 'table' ? 'btn-label-primary' : 'btn-label-secondary');
  document.getElementById('viewModeTiles').className = 'btn ' + (mode === 'tiles' ? 'btn-label-primary' : 'btn-label-secondary');

  if (mode === 'tiles' && document.getElementById('tileGrid').children.length === 0) {
    tiles.reset();
  } else if (mode === 'table' && !skipLoad) {
    patientsTable.search(document.getElementById('searchInput').value).draw();
  }
}

function previewPatientPhoto(input, previewId) {
  const img = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; };
    reader.readAsDataURL(input.files[0]);
  } else {
    img.src = '{{ asset('images/defaults/patient.png') }}';
  }
}

function editPatient(id) {
  fetch(`/patients/${id}/json`)
    .then(res => res.json())
    .then(p => {
      const f = document.getElementById('editPatientForm');
      f.action = `/patients/${id}`;
      f.querySelector('[name="patient_id"]').value              = id;
      f.querySelector('[name="name"]').value                   = p.name || '';
      f.querySelector('[name="dob"]').value                    = p.dob || '';
      f.querySelector('[name="gender"]').value                 = p.gender || '';
      f.querySelector('[name="blood_group"]').value            = p.blood_group || '';
      f.querySelector('[name="phone"]').value                  = p.phone || '';
      f.querySelector('[name="email"]').value                  = p.email || '';
      f.querySelector('[name="address"]').value                = p.address || '';
      f.querySelector('[name="emergency_contact_name"]').value = p.emergency_contact_name || '';
      f.querySelector('[name="emergency_contact"]').value      = p.emergency_contact || '';
      f.querySelector('[name="nationality"]').value            = p.nationality || '';
      f.querySelector('[name="marital_status"]').value         = p.marital_status || '';
      f.querySelector('[name="occupation"]').value             = p.occupation || '';
      f.querySelector('[name="patient_type"]').value           = p.patient_type || '';
      f.querySelector('[name="status"]').value                 = p.status || '';
      f.querySelector('[name="photo"]').value                  = '';
      
      const photoUrl = getPhotoUrl(p);
      document.getElementById('edit_photo_preview').src = photoUrl;
      
      new bootstrap.Offcanvas(document.getElementById('offcanvasPatientEdit')).show();
    })
    .catch(error => {
      console.error('Error loading patient data:', error);
    });
}

function deletePatient(id) {
  if (confirm('Are you sure you want to delete this patient?')) {
    fetch(`/patients/${id}`, {
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
          patientsTable.ajax.reload(null, false); 
        }
        // Show success message
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
      console.error('Error deleting patient:', error);
    });
  }
}
</script>

@endsection