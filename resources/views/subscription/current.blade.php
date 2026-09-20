@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Subscription Status - HMS')

@section('content')

@php
$sub = $subscription ?? \App\Models\Subscription::resolveCurrent();
$daysLeft = $sub ? $sub->daysRemaining() : 0;
$totalDays = $sub ? $sub->trial_started_at?->diffInDays($sub->expires_at) : 14;
$totalDays = max($totalDays, 1);
$progress = $sub ? min(100, round(($daysLeft / $totalDays) * 100)) : 0;
$planColors = [
  'free_trial' => 'info',
  'basic'      => 'secondary',
  'pro'        => 'primary',
  'custom'     => 'warning',
];
$planColor = $planColors[$sub?->plan ?? 'free_trial'] ?? 'primary';
$modules = [
  ['key'=>'lab',           'name'=>'Laboratory',     'icon'=>'tabler-flask'],
  ['key'=>'pharmacy',      'name'=>'Pharmacy',       'icon'=>'tabler-pill'],
  ['key'=>'billing',       'name'=>'Billing',        'icon'=>'tabler-file-invoice'],
  ['key'=>'ward',          'name'=>'Ward',           'icon'=>'tabler-building-hospital'],
  ['key'=>'food_intake',   'name'=>'Food Intake',    'icon'=>'tabler-meat'],
  ['key'=>'fluid_intake',  'name'=>'Fluid Intake',   'icon'=>'tabler-droplet'],
  ['key'=>'staff_rounds',  'name'=>'Staff Rounds',   'icon'=>'tabler-clock-check'],
  ['key'=>'consent_forms', 'name'=>'Consent Forms',  'icon'=>'tabler-writing'],
  ['key'=>'referrals',     'name'=>'Referrals',      'icon'=>'tabler-transfer'],
  ['key'=>'reports',       'name'=>'Reports',        'icon'=>'tabler-chart-bar'],
  ['key'=>'patient_portal','name'=>'Patient Portal', 'icon'=>'tabler-user-circle'],
];
$allowedModules = ($sub && $sub->isActive()) ? $sub->getAllowedModules() : [];
@endphp

{{-- Page Header --}}
<div class="d-flex align-items-center gap-3 mb-6">
  <div class="avatar">
    <span class="avatar-initial rounded bg-label-{{ $planColor }}">
      <i class="icon-base ti tabler-crown icon-26px"></i>
    </span>
  </div>
  <div>
    <h4 class="mb-0">Subscription Status</h4>
    <small class="text-muted">Current HMS subscription plan & module access</small>
  </div>
</div>

{{-- Expiry Warning --}}
@if($sub && $daysLeft <= 7 && $daysLeft > 0)
<div class="alert alert-warning alert-dismissible mb-6 d-flex align-items-center gap-2">
  <i class="icon-base ti tabler-alert-triangle" style="font-size:1.25rem;"></i>
  <div>
    <strong>Subscription expiring soon!</strong>
    Your subscription expires in <strong>{{ $daysLeft }} days</strong> ({{ $sub->expires_at->format('d M Y') }}).
    Contact your administrator to renew.
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@elseif($sub && $sub->isExpired())
<div class="alert alert-danger mb-6 d-flex align-items-center gap-2">
  <i class="icon-base ti tabler-calendar-off" style="font-size:1.25rem;"></i>
  <div>
    <strong>Subscription Expired!</strong>
    Your subscription expired on {{ $sub->expires_at->format('d M Y') }}.
    Please contact your administrator to renew.
  </div>
</div>
@endif

<div class="row g-6">

  {{-- Left: Plan Details --}}
  <div class="col-xl-4 col-lg-5">

    {{-- Plan Card --}}
    <div class="card mb-6">
      <div class="card-body pt-6 text-center">
        <div class="avatar avatar-xl mb-3 mx-auto">
          <span class="avatar-initial rounded-circle bg-label-{{ $planColor }}"
            style="font-size:2rem;width:80px;height:80px;">
            <i class="icon-base ti tabler-crown"></i>
          </span>
        </div>
        <h5 class="mb-1 text-capitalize">{{ str_replace('_', ' ', $sub?->plan ?? 'Free Trial') }}</h5>
        <span class="badge bg-label-{{ $sub?->status === 'active' ? 'success' : 'danger' }} mb-3">
          {{ ucfirst($sub?->status ?? 'inactive') }}
        </span>

        <hr class="my-4" />

        {{-- Details --}}
        <div class="d-flex flex-column gap-3 text-start">
          @foreach([
            ['tabler-calendar',      'Started',         $sub?->trial_started_at?->format('d M Y') ?? 'N/A'],
            ['tabler-calendar-event','Expires On',      $sub?->expires_at?->format('d M Y') ?? 'N/A'],
            ['tabler-clock',         'Days Remaining',  $daysLeft . ' days'],
            ['tabler-building',      'Hospital',        $sub?->hospital_name ?? config('app.name')],
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

        <hr class="my-4" />

        {{-- Progress Bar --}}
        <div class="text-start mb-2">
          <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Subscription Usage</small>
            <small class="fw-medium">{{ $daysLeft }} days left</small>
          </div>
          <div class="progress" style="height:8px;border-radius:4px;">
            <div class="progress-bar bg-{{ $daysLeft > 7 ? 'success' : ($daysLeft > 3 ? 'warning' : 'danger') }}"
              style="width:{{ $progress }}%;border-radius:4px;"></div>
          </div>
        </div>

        @role('super_admin')
        <a href="{{ route('subscription.index') }}" class="btn btn-primary w-100 mt-4">
          <i class="icon-base ti tabler-settings me-1"></i> Manage Subscriptions
        </a>
        @endrole

      </div>
    </div>

    {{-- Plan Comparison --}}
    <div class="card">
      <div class="card-header border-bottom py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-list me-2 text-primary"></i>Available Plans</h6>
      </div>
      <div class="card-body p-0">
        @foreach([
          ['free_trial', 'Free Trial', '14 days, all features', 'info'],
          ['basic',      'Basic',      'Billing & Patient Portal', 'secondary'],
          ['pro',        'Pro',        'All modules included', 'primary'],
          ['custom',     'Custom',     'Configured by admin', 'warning'],
        ] as [$key, $name, $desc, $color])
        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom {{ $sub?->plan === $key ? 'bg-label-'.$color.' bg-opacity-25' : '' }}">
          <span class="badge bg-label-{{ $color }}" style="min-width:80px;">{{ $name }}</span>
          <small class="text-muted">{{ $desc }}</small>
          @if($sub?->plan === $key)
          <i class="icon-base ti tabler-check text-success ms-auto"></i>
          @endif
        </div>
        @endforeach
      </div>
    </div>

  </div>

  {{-- Right: Module Access --}}
  <div class="col-xl-8 col-lg-7">

    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="mb-0"><i class="icon-base ti tabler-apps me-2 text-primary"></i>Module Access</h5>
        <small class="text-muted">Features available on your current plan</small>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @foreach($modules as $module)
          @php $allowed = in_array($module['key'], $allowedModules); @endphp
          <div class="col-md-6">
            <div class="d-flex align-items-center justify-content-between p-3 border rounded
              {{ $allowed ? '' : 'opacity-50' }}">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                  <span class="avatar-initial rounded bg-label-{{ $allowed ? 'primary' : 'secondary' }}">
                    <i class="icon-base ti {{ $module['icon'] }}"></i>
                  </span>
                </div>
                <span class="fw-medium" style="font-size:0.875rem;">{{ $module['name'] }}</span>
              </div>
              @if($allowed)
              <span class="badge bg-label-success">
                <i class="icon-base ti tabler-check me-1"></i> Active
              </span>
              @else
              <span class="badge bg-label-secondary">
                <i class="icon-base ti tabler-lock me-1"></i> Locked
              </span>
              @endif
            </div>
          </div>
          @endforeach
        </div>

        @if(!$sub || $sub->isExpired())
        <div class="alert alert-warning mt-4 mb-0 d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-info-circle"></i>
          <span>All modules are locked because your subscription has expired. Contact your administrator to renew.</span>
        </div>
        @endif
      </div>
    </div>

  </div>

</div>

@endsection