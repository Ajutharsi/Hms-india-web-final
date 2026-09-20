@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'My Profile - HMS')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4">
  <ul class="mb-0">
    @foreach($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$role = $user->roles->first()?->name ?? 'N/A';
$roleColors = [
  'super_admin' => 'danger', 'admin' => 'warning', 'doctor' => 'primary',
  'nurse' => 'info', 'receptionist' => 'success', 'lab_assistant' => 'warning',
  'pharmacist' => 'danger', 'staff' => 'secondary', 'patient' => 'success',
];
$roleColor = $roleColors[$role] ?? 'secondary';

// Role-specific data
$doctorRecord  = null;
$patientRecord = null;
$staffRecord   = null;

if ($role === 'doctor') {
  $doctorRecord = \App\Models\Doctor::where('email', $user->email)->first();
}
if ($role === 'patient') {
  $patientRecord = \App\Models\Patient::where('user_id', $user->id)->first();
}
if (in_array($role, ['nurse', 'staff', 'receptionist', 'lab_assistant', 'pharmacist'])) {
  $staffRecord = $user;
}

// Recent activity
$recentActivity = \App\Models\ActivityLog::where('user_id', $user->id)
  ->latest()->take(5)->get();
@endphp

<div class="row">

  {{-- Left: Profile Card --}}
  <div class="col-xl-4 col-lg-5 mb-6 mb-lg-0">

    {{-- Main Profile Card --}}
    <div class="card mb-6">
      <div class="card-body pt-6">

      {{-- Avatar + Photo Upload --}}
<div class="d-flex flex-column align-items-center text-center mb-4">
  <div class="position-relative mb-3">
    @if($user->profile_photo_url)
    <img src="{{ $user->profile_photo_url }}"
      alt="{{ $user->name }}"
      class="rounded-circle"
      style="width:88px;height:88px;object-fit:cover;border:3px solid var(--bs-border-color);" />
    @else
    <div class="avatar-initial rounded-circle bg-label-{{ $roleColor }}"
      style="width:88px;height:88px;font-size:2.25rem;display:flex;align-items:center;justify-content:center;">
      {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
    @endif

    {{-- Camera Icon --}}
    <label for="profilePhotoInput"
      class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle p-1"
      style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;"
      title="Change Photo">
      <i class="icon-base ti tabler-camera" style="font-size:0.8rem;"></i>
    </label>
  </div>

  <h5 class="mb-1">{{ $user->name }}</h5>
  <span class="badge bg-label-{{ $roleColor }} text-capitalize mb-1">
    {{ str_replace('_', ' ', $role) }}
  </span>
  <small class="text-muted">{{ $user->email }}</small>

  @if($user->last_login)
  <small class="text-muted mt-1">
    <i class="icon-base ti tabler-clock me-1"></i>
   Last login: {{ $user->last_login ? \Carbon\Carbon::parse($user->last_login)->diffForHumans() : 'Never' }}
  </small>
  @endif
</div>



        <hr class="my-4" />

        {{-- Basic Details --}}
        <div class="d-flex flex-column gap-3 mb-4">
          @foreach([
            ['tabler-phone',       'Phone',        $user->phone ?? 'Not set'],
            ['tabler-calendar',    'Member Since', $user->created_at->format('d M Y')],
            ['tabler-map-pin',     'Department',   $user->department?->name ?? 'N/A'],
          ] as [$icon, $label, $value])
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-sm flex-shrink-0">
              <span class="avatar-initial rounded bg-label-secondary">
                <i class="icon-base ti {{ $icon }} icon-sm"></i>
              </span>
            </div>
            <div>
              <small class="text-muted d-block">{{ $label }}</small>
              <span class="fw-medium" style="font-size:0.875rem;">{{ $value }}</span>
            </div>
          </div>
          @endforeach
        </div>

        {{-- Role-specific Info --}}
        @if($doctorRecord)
        <hr class="my-4" />
        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">
          Doctor Information
        </p>
        <div class="d-flex flex-column gap-3">
          @foreach([
            ['tabler-stethoscope',  'Doctor ID',       $doctorRecord->doctor_id ?? 'N/A'],
            ['tabler-heart',        'Specialization',  $doctorRecord->specialization ?? 'N/A'],
            ['tabler-certificate',  'Qualification',   $doctorRecord->qualification ?? 'N/A'],
            ['tabler-id',           'License No',      $doctorRecord->license_no ?? 'N/A'],
            ['tabler-star',         'Experience',      ($doctorRecord->experience ?? 0) . ' years'],
            ['tabler-currency-dollar','Consult. Fee',  'LKR ' . number_format($doctorRecord->consultation_fee ?? 0)],
          ] as [$icon, $label, $value])
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-sm flex-shrink-0">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti {{ $icon }} icon-sm"></i>
              </span>
            </div>
            <div>
              <small class="text-muted d-block">{{ $label }}</small>
              <span class="fw-medium" style="font-size:0.875rem;">{{ $value }}</span>
            </div>
          </div>
          @endforeach
        </div>
        @endif

        @if($patientRecord)
        <hr class="my-4" />
        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">
          Patient Information
        </p>
        <div class="d-flex flex-column gap-3">
          @foreach([
            ['tabler-id',         'MRN',           $patientRecord->mrn ?? 'N/A'],
            ['tabler-droplet',    'Blood Group',    $patientRecord->blood_group ?? 'N/A'],
            ['tabler-user',       'Gender',         ucfirst($patientRecord->gender ?? 'N/A')],
            ['tabler-bed',        'Patient Type',   $patientRecord->patient_type === 'ip' ? 'In Patient' : 'Out Patient'],
            ['tabler-phone-call', 'Emergency Contact', $patientRecord->emergency_contact ?? 'N/A'],
          ] as [$icon, $label, $value])
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-sm flex-shrink-0">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti {{ $icon }} icon-sm"></i>
              </span>
            </div>
            <div>
              <small class="text-muted d-block">{{ $label }}</small>
              <span class="fw-medium" style="font-size:0.875rem;">{{ $value }}</span>
            </div>
          </div>
          @endforeach
        </div>
        @endif

        @if($staffRecord && in_array($role, ['nurse','staff','receptionist','lab_assistant','pharmacist']))
        <hr class="my-4" />
        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">
          Staff Information
        </p>
        <div class="d-flex flex-column gap-3">
          @foreach([
            ['tabler-id-badge',    'Employee ID',      $user->employee_id ?? 'N/A'],
            ['tabler-clock',       'Shift',            ucfirst($user->shift ?? 'N/A')],
            ['tabler-briefcase',   'Employment Type',  str_replace('_',' ', $user->employment_type ?? 'N/A')],
            ['tabler-calendar-off','Leave Balance',    ($user->leave_balance ?? 0) . ' days'],
          ] as [$icon, $label, $value])
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-sm flex-shrink-0">
              <span class="avatar-initial rounded bg-label-info">
                <i class="icon-base ti {{ $icon }} icon-sm"></i>
              </span>
            </div>
            <div>
              <small class="text-muted d-block">{{ $label }}</small>
              <span class="fw-medium text-capitalize" style="font-size:0.875rem;">{{ $value }}</span>
            </div>
          </div>
          @endforeach
        </div>
        @endif

      </div>
    </div>

    {{-- Account Stats Card --}}
    <div class="card">
      <div class="card-header border-bottom py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-chart-bar me-2 text-primary"></i>Account Stats</h6>
      </div>
      <div class="card-body p-0">
        @php
        $stats = [
          ['Today\'s Appts', \App\Models\Appointment::whereDate('appointment_date', today())->count(), 'primary', 'tabler-calendar'],
        ];
        if ($role === 'doctor' && $doctorRecord) {
          $stats = [
            ['My Appointments', \App\Models\Appointment::where('doctor_id', $doctorRecord->id)->count(), 'primary', 'tabler-calendar'],
            ['Today\'s Appts', \App\Models\Appointment::where('doctor_id', $doctorRecord->id)->whereDate('appointment_date', today())->count(), 'success', 'tabler-calendar-check'],
          ];
        } elseif ($role === 'patient' && $patientRecord) {
          $stats = [
            ['My Appointments', \App\Models\Appointment::where('patient_id', $patientRecord->id)->count(), 'primary', 'tabler-calendar'],
            ['Lab Orders', \App\Models\LabOrder::where('patient_id', $patientRecord->id)->count(), 'info', 'tabler-flask'],
          ];
        } elseif (in_array($role, ['nurse','staff'])) {
          $stats = [
            ['My Rounds Today', \App\Models\StaffRound::whereDate('round_time', today())->where('staff_id', $user->id)->count(), 'success', 'tabler-clock-check'],
            ['Overdue Rounds', \App\Models\RoundAssignment::where('staff_id', $user->id)->where('status','active')->where('next_round_at','<=',now())->count(), 'danger', 'tabler-alert-triangle'],
          ];
        }
        @endphp
        @foreach($stats as [$label, $count, $color, $icon])
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti {{ $icon }} text-{{ $color }}"></i>
            <span style="font-size:0.875rem;">{{ $label }}</span>
          </div>
          <span class="badge bg-label-{{ $color }}">{{ $count }}</span>
        </div>
        @endforeach
        <div class="d-flex justify-content-between align-items-center px-4 py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-calendar text-secondary"></i>
            <span style="font-size:0.875rem;">Member Since</span>
          </div>
          <span class="text-muted" style="font-size:0.82rem;">{{ $user->created_at->format('d M Y') }}</span>
        </div>
      </div>
    </div>

  </div>

  {{-- Right: Forms --}}
  <div class="col-xl-8 col-lg-7">

    {{-- Profile Info Form --}}
    <div class="card mb-6">
      <div class="card-header border-bottom">
        <h5 class="mb-0">Profile Information</h5>
        <small class="text-muted">Update your name, email and phone number</small>
      </div>
      <div class="card-body pt-6">
        <form action="{{ route('profile.update') }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user->name) }}" required />
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Address *</label>
              <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $user->email) }}" required />
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                value="{{ old('phone', $user->phone) }}" placeholder="+94 77 123 4567"
                maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
              <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
              @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <input type="text" class="form-control"
                value="{{ ucwords(str_replace('_', ' ', $role)) }}" disabled />
              <div class="form-text">Role can only be changed by an administrator</div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
              </button>
              <a href="{{ route('profile.show') }}" class="btn btn-label-secondary">Reset</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Change Password --}}
    <div class="card mb-6">
      <div class="card-header border-bottom">
        <h5 class="mb-0">Change Password</h5>
        <small class="text-muted">Ensure your account is using a strong password</small>
      </div>
      <div class="card-body pt-6">
        <form action="{{ route('profile.update') }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row g-4">
            <div class="col-md-12">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password"
                class="form-control @error('current_password') is-invalid @enderror" />
              @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" name="password"
                class="form-control @error('password') is-invalid @enderror" />
              @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="password_confirmation" class="form-control" />
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ti tabler-lock me-1"></i> Update Password
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Recent Activity --}}
    @if($recentActivity->count() > 0)
    <div class="card">
      <div class="card-header border-bottom py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-history me-2 text-info"></i>Recent Activity</h6>
      </div>
      <div class="card-body p-0">
        @foreach($recentActivity as $log)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="avatar avatar-sm flex-shrink-0">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-activity icon-sm"></i>
            </span>
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium" style="font-size:0.82rem;">{{ $log->description }}</p>
            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif

    @if($staffRecord && in_array($role, ['nurse','staff','receptionist','lab_assistant','pharmacist','doctor']))
    {{-- My Attendance & Leave --}}
    <div class="card mb-6">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Attendance & Leave</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLeaveRequest">
          <i class="icon-base ti tabler-plus me-1"></i> Apply for Leave
        </button>
      </div>
      <div class="card-body pt-6">
        <div class="row">
          <div class="col-md-6">
            <p class="text-muted small fw-semibold text-uppercase mb-3">Recent Attendance</p>
            @forelse($myAttendance as $a)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <span class="small">{{ \Carbon\Carbon::parse($a->attendance_date)->format('d M Y') }}</span>
              <span class="badge bg-label-{{ ['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info','leave'=>'secondary'][$a->status] ?? 'secondary' }} text-capitalize">
                {{ str_replace('_', ' ', $a->status) }}
              </span>
            </div>
            @empty
            <p class="text-muted small">No attendance records.</p>
            @endforelse
          </div>
          <div class="col-md-6">
            <p class="text-muted small fw-semibold text-uppercase mb-3">My Leave Requests</p>
            @forelse($myLeaves as $l)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div>
                <span class="small fw-medium text-capitalize">{{ str_replace('_', ' ', $l->leave_type) }}</span>
                <div class="text-muted" style="font-size:0.75rem;">
                  {{ \Carbon\Carbon::parse($l->from_date)->format('d M') }} – {{ \Carbon\Carbon::parse($l->to_date)->format('d M Y') }}
                </div>
              </div>
              <span class="badge bg-label-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$l->status] ?? 'secondary' }} text-capitalize">
                {{ $l->status }}
              </span>
            </div>
            @empty
            <p class="text-muted small">No leave requests.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    {{-- Apply for Leave Offcanvas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLeaveRequest">
      <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Apply for Leave</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body p-6">
        <form action="{{ route('profile.leave-request') }}" method="POST">
          @csrf
          <div class="mb-4">
            <label class="form-label">Leave Type *</label>
            <select class="form-select @error('leave_type') is-invalid @enderror" name="leave_type" required>
              <option value="sick" @selected(old('leave_type')=='sick')>Sick</option>
              <option value="casual" @selected(old('leave_type')=='casual')>Casual</option>
              <option value="annual" @selected(old('leave_type')=='annual')>Annual</option>
              <option value="emergency" @selected(old('leave_type')=='emergency')>Emergency</option>
              <option value="maternity" @selected(old('leave_type')=='maternity')>Maternity</option>
              <option value="paternity" @selected(old('leave_type')=='paternity')>Paternity</option>
            </select>
            @error('leave_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">From *</label>
            <input type="date" class="form-control @error('from_date') is-invalid @enderror" name="from_date" value="{{ old('from_date') }}" required />
            @error('from_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">To *</label>
            <input type="date" class="form-control @error('to_date') is-invalid @enderror" name="to_date" value="{{ old('to_date') }}" required />
            @error('to_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-6">
            <label class="form-label">Reason *</label>
            <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="3" required>{{ old('reason') }}</textarea>
            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base ti tabler-device-floppy me-1"></i> Submit
            </button>
            <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
          </div>
        </form>
      </div>
    </div>
    @endif

  </div>

</div>
</div>

{{-- Hidden Photo Upload Form --}}
<form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="photoForm">
  @csrf
  @method('PUT')
  <input type="hidden" name="name" value="{{ $user->name }}" />
  <input type="hidden" name="email" value="{{ $user->email }}" />
  <input type="hidden" name="phone" value="{{ $user->phone }}" />
  <input type="file" id="profilePhotoInput" name="profile_photo" class="d-none" accept="image/*" />
</form>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const profilePhotoInput = document.getElementById('profilePhotoInput');
  if (profilePhotoInput) {
    profilePhotoInput.addEventListener('change', function() {
      if (this.files.length > 0) {
        document.getElementById('photoForm').submit();
      }
    });
  }

  @if ($errors->any() && old('leave_type'))
  new bootstrap.Offcanvas(document.getElementById('offcanvasLeaveRequest')).show();
  @endif
});
</script>
@endsection

