@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Staff Profile - HMS')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$roleColors = [
  'doctor' => 'primary', 'nurse' => 'info', 'receptionist' => 'success',
  'lab_assistant' => 'warning', 'pharmacist' => 'danger', 'staff' => 'secondary',
];
$role = $user->roles->first()?->name ?? 'N/A';
$roleColor = $roleColors[$role] ?? 'secondary';
$statusColors = [
  'active' => 'success', 'on_leave' => 'warning',
  'resigned' => 'secondary', 'terminated' => 'danger',
];
$totalPay = ($user->pay_rate ?? 0) + ($user->allowance_transport ?? 0) +
            ($user->allowance_meal ?? 0) + ($user->allowance_housing ?? 0);
@endphp

<div class="row">

  {{-- Left Sidebar --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Profile Card --}}
    <div class="card mb-4">
      <div class="card-body pt-8">
        <div class="d-flex flex-column align-items-center text-center mb-4">
          @if($user->profile_photo_url)
          <div class="avatar avatar-xl mb-3">
            <img src="{{ $user->profile_photo_url }}"
              alt="{{ $user->name }}"
              class="rounded-circle"
              style="width:72px;height:72px;object-fit:cover;" />
          </div>
          @else
          <div class="avatar avatar-xl mb-3">
            <span class="avatar-initial rounded-circle bg-label-{{ $roleColor }}"
              style="font-size:2rem;width:72px;height:72px;">
              {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
          </div>
          @endif
          <h5 class="mb-1">{{ $user->name }}</h5>
          <small class="text-muted mb-2">{{ $user->employee_id ?? 'No ID' }}</small>
          <div class="d-flex gap-1 flex-wrap justify-content-center">
            <span class="badge bg-label-{{ $roleColor }} text-capitalize">{{ str_replace('_',' ', $role) }}</span>
            <span class="badge bg-label-{{ $statusColors[$user->employment_status] ?? 'secondary' }} text-capitalize">
              {{ str_replace('_',' ', $user->employment_status ?? 'active') }}
            </span>
          </div>
        </div>

        {{-- Personal Details --}}
        <div class="mb-4">
          <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Personal</p>
          @foreach([
            ['DOB', $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d M Y') : 'N/A'],
            ['Gender', ucfirst($user->gender ?? 'N/A')],
            ['Blood Group', $user->blood_group ?? 'N/A'],
            ['National ID', $user->national_id ?? 'N/A'],
            ['Phone', $user->phone ?? 'N/A'],
            ['Email', $user->email],
            ['Address', $user->address ?? 'N/A'],
          ] as [$label, $value])
          <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span class="text-muted" style="font-size:0.8rem;">{{ $label }}</span>
            <span class="fw-medium text-end" style="font-size:0.82rem;max-width:60%;">{{ $value }}</span>
          </div>
          @endforeach
        </div>

        {{-- NOK --}}
        <div class="mb-4">
          <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Emergency Contact (NOK)</p>
          @foreach([
            ['Name', $user->emergency_contact_name ?? 'N/A'],
            ['Relationship', $user->nok_relationship ?? 'N/A'],
            ['Phone', $user->emergency_contact_phone ?? 'N/A'],
          ] as [$label, $value])
          <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span class="text-muted" style="font-size:0.8rem;">{{ $label }}</span>
            <span class="fw-medium" style="font-size:0.82rem;">{{ $value }}</span>
          </div>
          @endforeach
        </div>

        <a href="{{ route('staff.index') }}" class="btn btn-label-secondary btn-sm w-100">
          <i class="icon-base ti tabler-arrow-left me-1"></i> Back to Staff
        </a>
      </div>
    </div>

  </div>

  {{-- Right Content --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    {{-- Professional Info --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-briefcase me-2 text-primary"></i>Professional Information</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @foreach([
            ['Department', $user->department?->name ?? 'N/A', 'tabler-building'],
            ['Joining Date', $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('d M Y') : 'N/A', 'tabler-calendar'],
            ['Employment Type', str_replace('_',' ', $user->employment_type ?? 'N/A'), 'tabler-user-check'],
            ['Shift', ucfirst($user->shift ?? 'N/A'), 'tabler-clock'],
            ['Qualification', $user->qualification ?? 'N/A', 'tabler-certificate'],
            ['License No', $user->license_no ?? 'N/A', 'tabler-id'],
            ['Experience', ($user->experience_years ?? 0) . ' years', 'tabler-star'],
            ['Leave Balance', ($user->leave_balance ?? 0) . ' days', 'tabler-calendar-off'],
          ] as [$label, $value, $icon])
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-3 p-3 border rounded">
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-secondary">
                  <i class="icon-base ti {{ $icon }}"></i>
                </span>
              </div>
              <div>
                <small class="text-muted d-block">{{ $label }}</small>
                <span class="fw-medium text-capitalize">{{ $value }}</span>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Pay Information --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-currency-dollar me-2 text-success"></i>Pay Information</h6>
      </div>
      <div class="card-body">
        <div class="row g-3 mb-4">
          <div class="col-md-3 text-center">
            <div class="p-3 border rounded">
              <small class="text-muted d-block">Basic Pay</small>
              <h5 class="mb-0 text-primary">{{ $user->currency ?? $hospitalSettings->currency }} {{ number_format($user->pay_rate ?? 0) }}</h5>
              <small class="text-muted text-capitalize">/{{ $user->pay_type ?? 'monthly' }}</small>
            </div>
          </div>
          <div class="col-md-3 text-center">
            <div class="p-3 border rounded">
              <small class="text-muted d-block">Transport</small>
              <h5 class="mb-0 text-info">{{ $user->currency ?? $hospitalSettings->currency }} {{ number_format($user->allowance_transport ?? 0) }}</h5>
            </div>
          </div>
          <div class="col-md-3 text-center">
            <div class="p-3 border rounded">
              <small class="text-muted d-block">Meal</small>
              <h5 class="mb-0 text-warning">{{ $user->currency ?? $hospitalSettings->currency }} {{ number_format($user->allowance_meal ?? 0) }}</h5>
            </div>
          </div>
          <div class="col-md-3 text-center">
            <div class="p-3 border rounded">
              <small class="text-muted d-block">Housing</small>
              <h5 class="mb-0 text-secondary">{{ $user->currency ?? $hospitalSettings->currency }} {{ number_format($user->allowance_housing ?? 0) }}</h5>
            </div>
          </div>
        </div>
        <div class="p-3 rounded text-center" style="background:rgba(var(--bs-success-rgb),0.08);">
          <small class="text-muted d-block">Total Pay</small>
          <h4 class="mb-0 text-success">{{ $user->currency ?? $hospitalSettings->currency }} {{ number_format($totalPay) }}</h4>
          <small class="text-muted text-capitalize">/{{ $user->pay_type ?? 'monthly' }}</small>
        </div>
      </div>
    </div>

    {{-- Bank Information --}}
    @if($user->bank_account_no || $user->bank_name)
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-building-bank me-2 text-info"></i>Bank / Payment</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @foreach([
            ['Account Holder', $user->bank_account_name ?? 'N/A'],
            ['Account Number', $user->bank_account_no ?? 'N/A'],
            ['Bank Name', $user->bank_name ?? 'N/A'],
            ['Payment Method', str_replace('_',' ', $user->payment_method ?? 'N/A')],
          ] as [$label, $value])
          <div class="col-md-6">
            <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px dashed var(--bs-border-color);">
              <span class="text-muted" style="font-size:0.82rem;">{{ $label }}</span>
              <span class="fw-medium text-capitalize" style="font-size:0.82rem;">{{ $value }}</span>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
    @endif

    {{-- Documents --}}
    @php
    $docs = [
      ['License', $user->doc_license, 'tabler-certificate', 'primary'],
      ['Certificate', $user->doc_certificate, 'tabler-award', 'success'],
      ['National ID', $user->doc_national_id, 'tabler-id', 'info'],
      ['Other', $user->doc_other, 'tabler-file', 'secondary'],
    ];
    $hasDocs = collect($docs)->filter(fn($d) => $d[1])->count() > 0;
    @endphp

    @if($hasDocs)
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-files me-2 text-warning"></i>Documents</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @foreach($docs as [$label, $path, $icon, $color])
          @if($path)
          <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                  <span class="avatar-initial rounded bg-label-{{ $color }}">
                    <i class="icon-base ti {{ $icon }}"></i>
                  </span>
                </div>
                <div>
                  <p class="mb-0 fw-medium" style="font-size:0.85rem;">{{ $label }}</p>
                  <small class="text-muted">{{ basename($path) }}</small>
                </div>
              </div>
              <a href="{{ asset('storage/' . $path) }}" target="_blank"
                class="btn btn-sm btn-icon btn-label-primary" title="View / Download">
                <i class="icon-base ti tabler-download"></i>
              </a>
            </div>
          </div>
          @endif
          @endforeach
        </div>
      </div>
    </div>
    @endif

    {{-- Attendance --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-clock-check me-2 text-success"></i>Recent Attendance</h6>
        @role('super_admin|admin')
        <a href="{{ route('hr.attendance.index') }}" class="text-primary small">View All</a>
        @endrole
      </div>
      <div class="card-body py-3">
        @forelse($recentAttendance as $a)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small">{{ \Carbon\Carbon::parse($a->attendance_date)->format('d M Y') }}</span>
          <span class="badge bg-label-{{ ['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info','leave'=>'secondary'][$a->status] ?? 'secondary' }} text-capitalize">
            {{ str_replace('_', ' ', $a->status) }}
          </span>
        </div>
        @empty
        <p class="text-muted text-center mb-0 small py-2">No attendance records.</p>
        @endforelse
      </div>
    </div>

    {{-- Leave History --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-calendar-off me-2 text-warning"></i>Leave History</h6>
        @role('super_admin|admin')
        <a href="{{ route('hr.leaves.index') }}" class="text-primary small">View All</a>
        @endrole
      </div>
      <div class="card-body py-3">
        @forelse($recentLeaves as $l)
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
        <p class="text-muted text-center mb-0 small py-2">No leave requests.</p>
        @endforelse
      </div>
    </div>

  </div>

</div>

@endsection