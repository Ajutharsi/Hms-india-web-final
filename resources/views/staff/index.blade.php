@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Staff Management - HMS')

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
    <x-plan-usage-card label="Total Staff" :used="$userUsage['used']" :limit="$userUsage['limit']" :percent="$userUsage['percent']" :status="$userUsage['status']" icon="tabler-users" color="primary" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Doctors</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\User::role('doctor')->count() }}</h4>
            </div>
            <small>Active doctors</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-stethoscope icon-26px"></i>
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
            <span class="text-heading">Nurses</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\User::role('nurse')->count() }}</h4>
            </div>
            <small>Active nurses</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-heart icon-26px"></i>
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
            <span class="text-heading">Support Staff</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\User::role(['staff','receptionist','lab_assistant','pharmacist'])->count() }}</h4>
            </div>
            <small>Other roles</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-user-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Staff Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Staff List</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasStaffAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Add Staff
    </button>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="staffTable">
      <thead>
        <tr>
          <th></th>
          <th>Employee ID</th>
          <th>Name</th>
          <th>Role</th>
          <th>Department</th>
          <th>Shift</th>
          <th>Total Pay</th>
          <th>Leave</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Staff Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasStaffAdd" style="width:560px;max-width:92vw;">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Staff</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($errors->any() && !old('_staff_edit_id'))
        <div class="alert alert-danger">
          <strong>Please fix the following:</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
        @endif

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Personal Information</p>
        <div class="row g-3 mb-4">
          <div class="col-12">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required />
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required />
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+94 77 123 4567" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
            <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" value="{{ old('dob') }}" />
            @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Gender</label>
            <select class="form-select @error('gender') is-invalid @enderror" name="gender">
              <option value="">Select</option>
              <option value="male" @selected(old('gender')=='male')>Male</option>
              <option value="female" @selected(old('gender')=='female')>Female</option>
              <option value="other" @selected(old('gender')=='other')>Other</option>
            </select>
            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Blood Group</label>
            <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group">
              <option value="">Select</option>
              @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
              <option value="{{ $bg }}" @selected(old('blood_group')==$bg)>{{ $bg }}</option>
              @endforeach
            </select>
            @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">National ID / Passport</label>
            <input type="text" class="form-control @error('national_id') is-invalid @enderror" name="national_id" value="{{ old('national_id') }}" />
            @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2">{{ old('address') }}</textarea>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Emergency Contact (NOK)</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Contact Name</label>
            <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" />
            @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Relationship</label>
            <input type="text" class="form-control @error('nok_relationship') is-invalid @enderror" name="nok_relationship" placeholder="e.g. Spouse, Parent" value="{{ old('nok_relationship') }}" />
            @error('nok_relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Contact Phone</label>
            <input type="tel" class="form-control @error('emergency_contact_phone') is-invalid @enderror" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
            <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
            @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Professional Information</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Role *</label>
            <select class="form-select @error('role') is-invalid @enderror" name="role" required>
              <option value="">Select Role</option>
              <option value="doctor" @selected(old('role')=='doctor')>Doctor</option>
              <option value="nurse" @selected(old('role')=='nurse')>Nurse</option>
              <option value="receptionist" @selected(old('role')=='receptionist')>Receptionist</option>
              <option value="lab_assistant" @selected(old('role')=='lab_assistant')>Lab Assistant</option>
              <option value="pharmacist" @selected(old('role')=='pharmacist')>Pharmacist</option>
              <option value="staff" @selected(old('role')=='staff')>Support Staff</option>
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Department</label>
            <select class="form-select @error('department_id') is-invalid @enderror" name="department_id">
              <option value="">Select</option>
              @foreach($departments as $dept)
              <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>{{ $dept->name }}</option>
              @endforeach
            </select>
            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Joining Date</label>
            <input type="date" class="form-control @error('joining_date') is-invalid @enderror" name="joining_date" value="{{ old('joining_date', now()->toDateString()) }}" />
            @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Employment Type</label>
            <select class="form-select @error('employment_type') is-invalid @enderror" name="employment_type">
              <option value="full_time" @selected(old('employment_type', 'full_time')=='full_time')>Full Time</option>
              <option value="part_time" @selected(old('employment_type')=='part_time')>Part Time</option>
              <option value="contract" @selected(old('employment_type')=='contract')>Contract</option>
            </select>
            @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Shift</label>
            <select class="form-select @error('shift') is-invalid @enderror" name="shift">
              <option value="">Select</option>
              <option value="morning" @selected(old('shift')=='morning')>Morning</option>
              <option value="evening" @selected(old('shift')=='evening')>Evening</option>
              <option value="night" @selected(old('shift')=='night')>Night</option>
            </select>
            @error('shift') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Experience (Years)</label>
            <input type="number" class="form-control @error('experience_years') is-invalid @enderror" name="experience_years" min="0" value="{{ old('experience_years', 0) }}" />
            @error('experience_years') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Qualification</label>
            <input type="text" class="form-control @error('qualification') is-invalid @enderror" name="qualification" placeholder="e.g. MBBS, BSc Nursing" value="{{ old('qualification') }}" />
            @error('qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">License No</label>
            <input type="text" class="form-control @error('license_no') is-invalid @enderror" name="license_no" value="{{ old('license_no') }}" />
            @error('license_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Leave Balance (days)</label>
            <input type="number" class="form-control @error('leave_balance') is-invalid @enderror" name="leave_balance" value="{{ old('leave_balance', 14) }}" min="0" />
            @error('leave_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Pay Information</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Pay Type</label>
            <select class="form-select @error('pay_type') is-invalid @enderror" name="pay_type">
              <option value="monthly" @selected(old('pay_type', 'monthly')=='monthly')>Monthly</option>
              <option value="daily" @selected(old('pay_type')=='daily')>Daily</option>
              <option value="hourly" @selected(old('pay_type')=='hourly')>Hourly</option>
            </select>
            @error('pay_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Currency</label>
            <select class="form-select @error('currency') is-invalid @enderror" name="currency">
              <option value="LKR" @selected(old('currency', 'LKR')=='LKR')>LKR</option>
              <option value="USD" @selected(old('currency')=='USD')>USD</option>
              <option value="INR" @selected(old('currency')=='INR')>INR</option>
            </select>
            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Basic Pay</label>
            <input type="number" class="form-control @error('pay_rate') is-invalid @enderror" name="pay_rate" placeholder="0.00" step="0.01" min="0" value="{{ old('pay_rate') }}" />
            @error('pay_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Transport Allowance</label>
            <input type="number" class="form-control @error('allowance_transport') is-invalid @enderror" name="allowance_transport" placeholder="0.00" step="0.01" min="0" value="{{ old('allowance_transport') }}" />
            @error('allowance_transport') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Meal Allowance</label>
            <input type="number" class="form-control @error('allowance_meal') is-invalid @enderror" name="allowance_meal" placeholder="0.00" step="0.01" min="0" value="{{ old('allowance_meal') }}" />
            @error('allowance_meal') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Housing Allowance</label>
            <input type="number" class="form-control @error('allowance_housing') is-invalid @enderror" name="allowance_housing" placeholder="0.00" step="0.01" min="0" value="{{ old('allowance_housing') }}" />
            @error('allowance_housing') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Bank / Payment</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Account Holder Name</label>
            <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror" name="bank_account_name" value="{{ old('bank_account_name') }}" />
            @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Account Number</label>
            <input type="text" class="form-control @error('bank_account_no') is-invalid @enderror" name="bank_account_no" value="{{ old('bank_account_no') }}" />
            @error('bank_account_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Bank Name</label>
            <input type="text" class="form-control @error('bank_name') is-invalid @enderror" name="bank_name" placeholder="e.g. BOC, HNB, Sampath" value="{{ old('bank_name') }}" />
            @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Payment Method</label>
            <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method">
              <option value="">Select</option>
              <option value="bank_transfer" @selected(old('payment_method')=='bank_transfer')>Bank Transfer</option>
              <option value="cash" @selected(old('payment_method')=='cash')>Cash</option>
              <option value="cheque" @selected(old('payment_method')=='cheque')>Cheque</option>
            </select>
            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Documents</p>
        <div class="row g-3 mb-6">
          <div class="col-6">
            <label class="form-label">Profile Photo</label>
            <input type="file" class="form-control" name="profile_photo" accept="image/*" />
          </div>
          <div class="col-6">
            <label class="form-label">License</label>
            <input type="file" class="form-control" name="doc_license" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
          </div>
          <div class="col-6">
            <label class="form-label">Certificate</label>
            <input type="file" class="form-control" name="doc_certificate" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
          </div>
          <div class="col-6">
            <label class="form-label">National ID Copy</label>
            <input type="file" class="form-control" name="doc_national_id" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
          </div>
          <div class="col-6">
            <label class="form-label">Other Document</label>
            <input type="file" class="form-control" name="doc_other" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Staff
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit Staff Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasStaffEdit" style="width:560px;max-width:92vw;">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Staff</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editStaffForm" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="_staff_edit_id" id="edit_staff_id" value="{{ old('_staff_edit_id') }}" />
        @if ($errors->any() && old('_staff_edit_id'))
        <div class="alert alert-danger">
          <strong>Please fix the following:</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
        @endif

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Personal Information</p>
        <div class="row g-3 mb-4">
          <div class="col-12">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="edit_name" value="{{ old('name') }}" required />
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="edit_email" value="{{ old('email') }}" required />
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Phone</label>
            <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" id="edit_phone" value="{{ old('phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
            <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control @error('dob') is-invalid @enderror" name="dob" id="edit_dob" value="{{ old('dob') }}" />
            @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Gender</label>
            <select class="form-select @error('gender') is-invalid @enderror" name="gender" id="edit_gender">
              <option value="">Select</option>
              <option value="male" @selected(old('gender')=='male')>Male</option>
              <option value="female" @selected(old('gender')=='female')>Female</option>
              <option value="other" @selected(old('gender')=='other')>Other</option>
            </select>
            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Blood Group</label>
            <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group" id="edit_blood_group">
              <option value="">Select</option>
              @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
              <option value="{{ $bg }}" @selected(old('blood_group')==$bg)>{{ $bg }}</option>
              @endforeach
            </select>
            @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">National ID / Passport</label>
            <input type="text" class="form-control @error('national_id') is-invalid @enderror" name="national_id" id="edit_national_id" value="{{ old('national_id') }}" />
            @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" name="address" id="edit_address" rows="2">{{ old('address') }}</textarea>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Emergency Contact (NOK)</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Contact Name</label>
            <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" name="emergency_contact_name" id="edit_emergency_contact_name" value="{{ old('emergency_contact_name') }}" />
            @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Relationship</label>
            <input type="text" class="form-control @error('nok_relationship') is-invalid @enderror" name="nok_relationship" id="edit_nok_relationship" value="{{ old('nok_relationship') }}" />
            @error('nok_relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Contact Phone</label>
            <input type="tel" class="form-control @error('emergency_contact_phone') is-invalid @enderror" name="emergency_contact_phone" id="edit_emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
            <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
            @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Professional Information</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Role *</label>
            <select class="form-select @error('role') is-invalid @enderror" name="role" id="edit_role" required>
              <option value="">Select Role</option>
              <option value="doctor" @selected(old('role')=='doctor')>Doctor</option>
              <option value="nurse" @selected(old('role')=='nurse')>Nurse</option>
              <option value="receptionist" @selected(old('role')=='receptionist')>Receptionist</option>
              <option value="lab_assistant" @selected(old('role')=='lab_assistant')>Lab Assistant</option>
              <option value="pharmacist" @selected(old('role')=='pharmacist')>Pharmacist</option>
              <option value="staff" @selected(old('role')=='staff')>Support Staff</option>
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Department</label>
            <select class="form-select @error('department_id') is-invalid @enderror" name="department_id" id="edit_department_id">
              <option value="">Select</option>
              @foreach($departments as $dept)
              <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>{{ $dept->name }}</option>
              @endforeach
            </select>
            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Joining Date</label>
            <input type="date" class="form-control @error('joining_date') is-invalid @enderror" name="joining_date" id="edit_joining_date" value="{{ old('joining_date') }}" />
            @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Employment Type</label>
            <select class="form-select @error('employment_type') is-invalid @enderror" name="employment_type" id="edit_employment_type">
              <option value="full_time" @selected(old('employment_type')=='full_time')>Full Time</option>
              <option value="part_time" @selected(old('employment_type')=='part_time')>Part Time</option>
              <option value="contract" @selected(old('employment_type')=='contract')>Contract</option>
            </select>
            @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Shift</label>
            <select class="form-select @error('shift') is-invalid @enderror" name="shift" id="edit_shift">
              <option value="">Select</option>
              <option value="morning" @selected(old('shift')=='morning')>Morning</option>
              <option value="evening" @selected(old('shift')=='evening')>Evening</option>
              <option value="night" @selected(old('shift')=='night')>Night</option>
            </select>
            @error('shift') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Experience (Years)</label>
            <input type="number" class="form-control @error('experience_years') is-invalid @enderror" name="experience_years" id="edit_experience_years" min="0" value="{{ old('experience_years') }}" />
            @error('experience_years') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Qualification</label>
            <input type="text" class="form-control @error('qualification') is-invalid @enderror" name="qualification" id="edit_qualification" value="{{ old('qualification') }}" />
            @error('qualification') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">License No</label>
            <input type="text" class="form-control @error('license_no') is-invalid @enderror" name="license_no" id="edit_license_no" value="{{ old('license_no') }}" />
            @error('license_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Employment Status</label>
            <select class="form-select @error('employment_status') is-invalid @enderror" name="employment_status" id="edit_employment_status">
              <option value="active" @selected(old('employment_status')=='active')>Active</option>
              <option value="on_leave" @selected(old('employment_status')=='on_leave')>On Leave</option>
              <option value="resigned" @selected(old('employment_status')=='resigned')>Resigned</option>
              <option value="terminated" @selected(old('employment_status')=='terminated')>Terminated</option>
            </select>
            @error('employment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Leave Balance (days)</label>
            <input type="number" class="form-control @error('leave_balance') is-invalid @enderror" name="leave_balance" id="edit_leave_balance" min="0" value="{{ old('leave_balance') }}" />
            @error('leave_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Pay Information</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Pay Type</label>
            <select class="form-select @error('pay_type') is-invalid @enderror" name="pay_type" id="edit_pay_type">
              <option value="monthly" @selected(old('pay_type')=='monthly')>Monthly</option>
              <option value="daily" @selected(old('pay_type')=='daily')>Daily</option>
              <option value="hourly" @selected(old('pay_type')=='hourly')>Hourly</option>
            </select>
            @error('pay_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Currency</label>
            <select class="form-select @error('currency') is-invalid @enderror" name="currency" id="edit_currency">
              <option value="LKR" @selected(old('currency')=='LKR')>LKR</option>
              <option value="USD" @selected(old('currency')=='USD')>USD</option>
              <option value="INR" @selected(old('currency')=='INR')>INR</option>
            </select>
            @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Basic Pay</label>
            <input type="number" class="form-control @error('pay_rate') is-invalid @enderror" name="pay_rate" id="edit_pay_rate" step="0.01" min="0" value="{{ old('pay_rate') }}" />
            @error('pay_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Transport Allowance</label>
            <input type="number" class="form-control @error('allowance_transport') is-invalid @enderror" name="allowance_transport" id="edit_allowance_transport" step="0.01" min="0" value="{{ old('allowance_transport') }}" />
            @error('allowance_transport') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Meal Allowance</label>
            <input type="number" class="form-control @error('allowance_meal') is-invalid @enderror" name="allowance_meal" id="edit_allowance_meal" step="0.01" min="0" value="{{ old('allowance_meal') }}" />
            @error('allowance_meal') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Housing Allowance</label>
            <input type="number" class="form-control @error('allowance_housing') is-invalid @enderror" name="allowance_housing" id="edit_allowance_housing" step="0.01" min="0" value="{{ old('allowance_housing') }}" />
            @error('allowance_housing') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Bank / Payment</p>
        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Account Holder Name</label>
            <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror" name="bank_account_name" id="edit_bank_account_name" value="{{ old('bank_account_name') }}" />
            @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Account Number</label>
            <input type="text" class="form-control @error('bank_account_no') is-invalid @enderror" name="bank_account_no" id="edit_bank_account_no" value="{{ old('bank_account_no') }}" />
            @error('bank_account_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Bank Name</label>
            <input type="text" class="form-control @error('bank_name') is-invalid @enderror" name="bank_name" id="edit_bank_name" value="{{ old('bank_name') }}" />
            @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6">
            <label class="form-label">Payment Method</label>
            <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" id="edit_payment_method">
              <option value="">Select</option>
              <option value="bank_transfer" @selected(old('payment_method')=='bank_transfer')>Bank Transfer</option>
              <option value="cash" @selected(old('payment_method')=='cash')>Cash</option>
              <option value="cheque" @selected(old('payment_method')=='cheque')>Cheque</option>
            </select>
            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Documents (leave blank to keep existing)</p>
        <div class="row g-3 mb-6">
          <div class="col-6">
            <label class="form-label">Profile Photo</label>
            <input type="file" class="form-control" name="profile_photo" accept="image/*" />
            <small id="current_profile_photo" class="text-muted"></small>
          </div>
          <div class="col-6">
            <label class="form-label">License</label>
            <input type="file" class="form-control" name="doc_license" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
            <small id="current_doc_license" class="text-muted"></small>
          </div>
          <div class="col-6">
            <label class="form-label">Certificate</label>
            <input type="file" class="form-control" name="doc_certificate" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
            <small id="current_doc_certificate" class="text-muted"></small>
          </div>
          <div class="col-6">
            <label class="form-label">National ID Copy</label>
            <input type="file" class="form-control" name="doc_national_id" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
            <small id="current_doc_national_id" class="text-muted"></small>
          </div>
          <div class="col-6">
            <label class="form-label">Other Document</label>
            <input type="file" class="form-control" name="doc_other" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
            <small id="current_doc_other" class="text-muted"></small>
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

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_staff_edit_id'))
    document.getElementById('editStaffForm').action = '/staff/{{ old('_staff_edit_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasStaffEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasStaffAdd')).show();
  @endif
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {

  const roleColors = {
    doctor: 'primary', nurse: 'info', receptionist: 'success',
    lab_assistant: 'warning', pharmacist: 'danger', staff: 'secondary',
  };

  const statusColors = {
    active: 'success', on_leave: 'warning',
    resigned: 'secondary', terminated: 'danger',
  };

  const shiftColors = {
    morning: 'warning', evening: 'info', night: 'secondary',
  };

  $('#staffTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('staff.data') }}', type: 'GET' },

    createdRow: function(row, data, dataIndex) {
      $(row).css('cursor', 'pointer');
      $(row).on('click', function(e) {
        if ($(e.target).closest('.d-flex').length) return;
        window.location.href = '/staff/' + data.id;
      });
    },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'employee_id', name: 'employee_id', render: d => `<span class="badge bg-label-secondary">${d}</span>` },
      { data: 'name', name: 'name', render: (d, t, row) => `
        <div class="d-flex align-items-center gap-2">
          <div class="avatar">
            <span class="avatar-initial rounded-circle bg-label-${roleColors[row.role] || 'secondary'}"
              style="width:36px;height:36px;font-size:0.85rem;">
              ${row.name.charAt(0).toUpperCase()}
            </span>
          </div>
          <div>
            <a href="/staff/${row.id}" class="fw-medium text-body">${row.name}</a>
            <small class="d-block text-muted">${row.email}</small>
          </div>
        </div>` },
      { data: 'role', name: 'role', orderable: false, render: d => `<span class="badge bg-label-${roleColors[d] || 'secondary'} text-capitalize">${d.replace('_',' ')}</span>` },
      { data: 'department_name', name: 'department_name', orderable: false },
      { data: 'shift', name: 'shift', render: d => d && d !== 'N/A' ? `<span class="badge bg-label-${shiftColors[d] || 'secondary'} text-capitalize">${d}</span>` : '—' },
      { data: 'total_pay', name: 'total_pay', render: (d, t, row) => `
        <div>
          <span class="fw-medium">${row.currency} ${Number(d ?? 0).toLocaleString()}</span>
          <small class="text-muted d-block text-capitalize">/${row.pay_type}</small>
        </div>` },
      { data: 'leave_balance', name: 'leave_balance', render: d => `<span class="badge bg-label-info">${d} days</span>` },
      { data: 'employment_status', name: 'employment_status', render: d => `<span class="badge bg-label-${statusColors[d] || 'secondary'} text-capitalize">${d.replace('_',' ')}</span>` },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

});

function editStaff(id) {
  fetch(`/staff/${id}/json`)
    .then(res => res.json())
    .then(u => {
      const f = document.getElementById('editStaffForm');
      f.action = `/staff/${id}`;
      document.getElementById('edit_staff_id').value                = id;
      document.getElementById('edit_name').value                    = u.name || '';
      document.getElementById('edit_email').value                   = u.email || '';
      document.getElementById('edit_phone').value                   = u.phone || '';
      document.getElementById('edit_dob').value                     = u.dob || '';
      document.getElementById('edit_gender').value                  = u.gender || '';
      document.getElementById('edit_blood_group').value             = u.blood_group || '';
      document.getElementById('edit_national_id').value             = u.national_id || '';
      document.getElementById('edit_address').value                 = u.address || '';
      document.getElementById('edit_emergency_contact_name').value  = u.emergency_contact_name || '';
      document.getElementById('edit_nok_relationship').value        = u.nok_relationship || '';
      document.getElementById('edit_emergency_contact_phone').value = u.emergency_contact_phone || '';
      document.getElementById('edit_role').value                    = u.role || '';
      document.getElementById('edit_department_id').value           = u.department_id || '';
      document.getElementById('edit_joining_date').value            = u.joining_date || '';
      document.getElementById('edit_employment_type').value         = u.employment_type || 'full_time';
      document.getElementById('edit_shift').value                   = u.shift || '';
      document.getElementById('edit_experience_years').value        = u.experience_years || 0;
      document.getElementById('edit_qualification').value           = u.qualification || '';
      document.getElementById('edit_license_no').value              = u.license_no || '';
      document.getElementById('edit_employment_status').value       = u.employment_status || 'active';
      document.getElementById('edit_leave_balance').value           = u.leave_balance || 14;
      document.getElementById('edit_pay_type').value                = u.pay_type || 'monthly';
      document.getElementById('edit_currency').value                = u.currency || 'LKR';
      document.getElementById('edit_pay_rate').value                = u.pay_rate || '';
      document.getElementById('edit_allowance_transport').value     = u.allowance_transport || '';
      document.getElementById('edit_allowance_meal').value          = u.allowance_meal || '';
      document.getElementById('edit_allowance_housing').value       = u.allowance_housing || '';
      document.getElementById('edit_bank_account_name').value       = u.bank_account_name || '';
      document.getElementById('edit_bank_account_no').value         = u.bank_account_no || '';
      document.getElementById('edit_bank_name').value               = u.bank_name || '';
      document.getElementById('edit_payment_method').value          = u.payment_method || '';

      // Show existing docs
      document.getElementById('current_profile_photo').textContent   = u.profile_photo   ? 'Current: ' + u.profile_photo.split('/').pop()   : '';
      document.getElementById('current_doc_license').textContent     = u.doc_license     ? 'Current: ' + u.doc_license.split('/').pop()     : '';
      document.getElementById('current_doc_certificate').textContent = u.doc_certificate ? 'Current: ' + u.doc_certificate.split('/').pop() : '';
      document.getElementById('current_doc_national_id').textContent = u.doc_national_id ? 'Current: ' + u.doc_national_id.split('/').pop() : '';
      document.getElementById('current_doc_other').textContent       = u.doc_other       ? 'Current: ' + u.doc_other.split('/').pop()       : '';

      new bootstrap.Offcanvas(document.getElementById('offcanvasStaffEdit')).show();
    });
}

function deleteStaff(id) {
  confirmDelete(`/staff/${id}`, 'Staff Member', function() {
    $('#staffTable').DataTable().ajax.reload(null, false);
  });
}
</script>

@endsection