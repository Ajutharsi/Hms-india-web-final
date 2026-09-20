@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Hospital Settings - HMS')

@section('page-style')
<style>
  .settings-nav .nav-link {
    color: var(--bs-body-color);
    border-radius: 6px;
    padding: 0.6rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
  }
  .settings-nav .nav-link.active {
    background: var(--bs-primary);
    color: #fff;
  }
  .settings-nav .nav-link:hover:not(.active) {
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
  }
  .settings-nav .nav-link i {
    width: 20px;
  }
  .module-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 1rem;
    transition: border-color 0.2s;
  }
  .module-card:hover {
    border-color: var(--bs-primary);
  }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-6">
  <i class="icon-base ti tabler-circle-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-6">
  <strong><i class="icon-base ti tabler-alert-circle me-2"></i>Please fix the following:</strong>
  <ul class="mb-0 mt-2">
    @foreach($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$features = $settings->features_enabled ?: \App\Models\HospitalSetting::DEFAULT_FEATURES;
@endphp

{{-- Outer Nav --}}
<ul class="nav nav-pills flex-column flex-md-row mb-6">
  <li class="nav-item">
    <a class="nav-link active" href="{{ route('settings.index') }}">
      <i class="icon-base ti tabler-building-hospital icon-sm me-1_5"></i> Hospital
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="{{ route('settings.activity-log') }}">
      <i class="icon-base ti tabler-history icon-sm me-1_5"></i> Activity Log
    </a>
  </li>
</ul>

@if($currentSubscription)
<div class="card mb-6">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="avatar">
        <span class="avatar-initial rounded bg-label-primary">
          <i class="icon-base ti tabler-crown icon-26px"></i>
        </span>
      </div>
      <div>
        <h6 class="mb-0 text-capitalize">{{ str_replace('_', ' ', $currentSubscription->plan) }} Plan</h6>
        <small class="text-muted text-capitalize">Status: {{ $currentSubscription->status }} &middot; {{ $subscriptionDaysLeft }} day(s) remaining</small>
      </div>
    </div>
    <a href="{{ route('subscription.current') }}" class="btn btn-label-primary btn-sm">
      View Subscription
    </a>
  </div>
</div>
@endif

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="row">

  {{-- Left Sidebar Nav --}}
  <div class="col-xl-3 col-lg-4 mb-6 mb-lg-0">
    <div class="card">
      <div class="card-body p-0">

        {{-- Hospital Card --}}
        <div class="p-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-lg">
              <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:1.5rem;">
                <i class="icon-base ti tabler-building-hospital"></i>
              </span>
            </div>
            <div>
              <h6 class="mb-0">{{ $settings->hospital_name ?? 'Hospital Name' }}</h6>
              <small class="text-muted">Hospital Settings</small>
            </div>
          </div>
        </div>

        {{-- Nav Links --}}
        <div class="p-3 settings-nav">
          <nav class="nav flex-column gap-1">
            <button type="button" class="nav-link active d-flex align-items-center gap-2"
              data-bs-toggle="pill" data-bs-target="#settingHospital">
              <i class="icon-base ti tabler-building icon-sm"></i> Hospital Info
            </button>
            <button type="button" class="nav-link d-flex align-items-center gap-2"
              data-bs-toggle="pill" data-bs-target="#settingSystem">
              <i class="icon-base ti tabler-adjustments icon-sm"></i> System Settings
            </button>
            <button type="button" class="nav-link d-flex align-items-center gap-2"
              data-bs-toggle="pill" data-bs-target="#settingModules">
              <i class="icon-base ti tabler-apps icon-sm"></i> Module Features
            </button>
            <button type="button" class="nav-link d-flex align-items-center gap-2"
              data-bs-toggle="pill" data-bs-target="#settingAlerts">
              <i class="icon-base ti tabler-bell icon-sm"></i> Notifications & Alerts
            </button>
          </nav>
        </div>

      </div>
    </div>
  </div>

  {{-- Right Content --}}
  <div class="col-xl-9 col-lg-8">
    <div class="tab-content">

      {{-- ===== Hospital Info ===== --}}
      <div class="tab-pane fade show active" id="settingHospital">

        {{-- Logo Card --}}
        <div class="card mb-6">
          <div class="card-body">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-4">
              <div class="flex-shrink-0">
                @if($settings->logo_url)
                <img id="logoPreview"
                  src="{{ $settings->logo_url }}"
                  alt="Logo" class="rounded"
                  style="width:88px;height:88px;object-fit:contain;background:var(--bs-body-bg);border:1px solid var(--bs-border-color);" />
                @else
                <div id="logoPreview"
                  class="avatar-initial rounded bg-label-primary"
                  style="width:88px;height:88px;font-size:2rem;display:flex;align-items:center;justify-content:center;">
                  <i class="icon-base ti tabler-building-hospital"></i>
                </div>
                @endif
              </div>
              <div>
                <h5 class="mb-1">Hospital Logo</h5>
                <div class="d-flex flex-wrap gap-2 mb-2">
                  <label class="btn btn-sm btn-primary mb-0">
                    <i class="icon-base ti tabler-upload me-1"></i> Upload Logo
                    <input type="file" name="logo" id="logoInput" class="d-none" accept="image/png,image/jpeg" />
                  </label>
                </div>
                <p class="text-muted small mb-0">Appears on invoices, lab reports & prescriptions. Allowed JPG or PNG. Max size 2MB.</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Hospital Details --}}
        <div class="card">
          <h5 class="card-header">Basic Information</h5>
          <div class="card-body">
            <div class="row gy-4">
              <div class="col-md-6">
                <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('hospital_name') is-invalid @enderror" name="hospital_name"
                  value="{{ old('hospital_name', $settings->hospital_name) }}" required />
                @error('hospital_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                  value="{{ old('email', $settings->email) }}" />
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone"
                  value="{{ old('phone', $settings->phone) }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
                <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" class="form-control @error('city') is-invalid @enderror" name="city"
                  value="{{ old('city', $settings->city) }}" />
                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-12">
                <label class="form-label">Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2">{{ old('address', $settings->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-12">
                <label class="form-label">Footer Note <span class="text-muted small">(appears on print pages)</span></label>
                <textarea class="form-control @error('footer_note') is-invalid @enderror" name="footer_note" rows="2">{{ old('footer_note', $settings->footer_note) }}</textarea>
                @error('footer_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                </button>
                <a href="{{ route('settings.index') }}" class="btn btn-label-secondary">Reset</a>
              </div>
            </div>
          </div>
        </div>

      </div>

      {{-- ===== System Settings ===== --}}
      <div class="tab-pane fade" id="settingSystem">

        <div class="card mb-6">
          <h5 class="card-header">Regional Settings</h5>
          <div class="card-body">
            <div class="row gy-4">
              <div class="col-md-4">
                <label class="form-label">Currency</label>
                <select class="form-select" name="currency">
                  @foreach(['LKR'=>'LKR — Sri Lankan Rupee','USD'=>'USD — US Dollar','INR'=>'INR — Indian Rupee','EUR'=>'EUR — Euro'] as $val=>$label)
                  <option value="{{ $val }}" {{ ($settings->currency ?? 'LKR') === $val ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date Format</label>
                <select class="form-select" name="date_format">
                  @foreach(['d M Y'=>'01 Jan 2026','d/m/Y'=>'01/01/2026','m/d/Y'=>'01/01/2026 (US)','Y-m-d'=>'2026-01-01'] as $val=>$label)
                  <option value="{{ $val }}" {{ ($settings->date_format ?? 'd M Y') === $val ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Timezone</label>
                <select class="form-select" name="timezone">
                  @foreach(['Asia/Colombo','Asia/Kolkata','UTC'] as $tz)
                  <option value="{{ $tz }}" {{ ($settings->timezone ?? 'Asia/Colombo') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <h5 class="card-header">ID Number Prefixes</h5>
          <div class="card-body">
            <div class="row gy-4">
              <div class="col-md-4">
                <label class="form-label">Patient MRN Prefix</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="mrn_prefix"
                    value="{{ old('mrn_prefix', $settings->mrn_prefix ?? 'HMS') }}" placeholder="HMS" />
                  <span class="input-group-text text-muted" style="font-size:0.78rem;">-2026-0001</span>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Doctor ID Prefix</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="doctor_id_prefix"
                    value="{{ old('doctor_id_prefix', $settings->doctor_id_prefix ?? 'DR') }}" placeholder="DR" />
                  <span class="input-group-text text-muted" style="font-size:0.78rem;">-0001</span>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Employee ID Prefix</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="employee_id_prefix"
                    value="{{ old('employee_id_prefix', $settings->employee_id_prefix ?? 'EMP') }}" placeholder="EMP" />
                  <span class="input-group-text text-muted" style="font-size:0.78rem;">-0001</span>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                </button>
                <a href="{{ route('settings.index') }}" class="btn btn-label-secondary">Reset</a>
              </div>
            </div>
          </div>
        </div>

      </div>

      {{-- ===== Module Features ===== --}}
      <div class="tab-pane fade" id="settingModules">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Module Features</h5>
            <small class="text-muted">Disabled modules are hidden from the sidebar</small>
          </div>
          <div class="card-body">
            @php
            $modules = [
              ['key'=>'lab',           'icon'=>'tabler-flask',             'color'=>'warning',   'name'=>'Laboratory',     'desc'=>'Lab tests & orders management'],
              ['key'=>'pharmacy',      'icon'=>'tabler-pill',              'color'=>'danger',    'name'=>'Pharmacy',       'desc'=>'Drug master & dispensing'],
              ['key'=>'billing',       'icon'=>'tabler-file-invoice',      'color'=>'success',   'name'=>'Billing',        'desc'=>'Services, invoices & payments'],
              ['key'=>'ward',          'icon'=>'tabler-building-hospital', 'color'=>'info',      'name'=>'Ward',           'desc'=>'Ward management & admissions'],
              ['key'=>'food_intake',   'icon'=>'tabler-meat',              'color'=>'warning',   'name'=>'Food Intake',    'desc'=>'Patient meal & diet tracking'],
              ['key'=>'fluid_intake',  'icon'=>'tabler-droplet',           'color'=>'info',      'name'=>'Fluid Intake',   'desc'=>'Fluid intake & output balance'],
              ['key'=>'staff_rounds',  'icon'=>'tabler-clock-check',       'color'=>'primary',   'name'=>'Staff Rounds',   'desc'=>'Ward round assignments & alerts'],
              ['key'=>'consent_forms', 'icon'=>'tabler-writing',           'color'=>'secondary', 'name'=>'Consent Forms',  'desc'=>'Digital consent & signatures'],
              ['key'=>'referrals',     'icon'=>'tabler-transfer',          'color'=>'success',   'name'=>'Referrals',      'desc'=>'Doctor-to-doctor referrals'],
              ['key'=>'reports',       'icon'=>'tabler-chart-bar',         'color'=>'primary',   'name'=>'Reports',        'desc'=>'Analytics, KPIs & reports'],
              ['key'=>'patient_portal','icon'=>'tabler-user-circle',       'color'=>'info',      'name'=>'Patient Portal', 'desc'=>'Patient login & self-service dashboard'],
            ];
            @endphp
            <div class="row g-4">
              @foreach($modules as $m)
              <div class="col-md-6">
                <div class="module-card d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-{{ $m['color'] }}">
                        <i class="icon-base ti {{ $m['icon'] }}"></i>
                      </span>
                    </div>
                    <div>
                      <p class="fw-semibold mb-0" style="font-size:0.875rem;">{{ $m['name'] }}</p>
                      <small class="text-muted">{{ $m['desc'] }}</small>
                    </div>
                  </div>
                  <div class="form-check form-switch mb-0 ms-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                      name="features[{{ $m['key'] }}]" value="1"
                      {{ ($features[$m['key']] ?? true) ? 'checked' : '' }} />
                  </div>
                </div>
              </div>
              @endforeach
            </div>
            <div class="mt-6">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
              </button>
              <a href="{{ route('settings.index') }}" class="btn btn-label-secondary">Reset</a>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Notifications & Alerts ===== --}}
      <div class="tab-pane fade" id="settingAlerts">

        <div class="card mb-6">
          <h5 class="card-header">Alert Thresholds</h5>
          <div class="card-body">
            <div class="row gy-4">
              <div class="col-md-4">
                <label class="form-label">
                  <i class="icon-base ti tabler-alert-triangle text-danger me-1"></i>
                  Low Stock Threshold
                </label>
                <input type="number" class="form-control @error('low_stock_threshold') is-invalid @enderror" name="low_stock_threshold" min="1"
                  value="{{ old('low_stock_threshold', $settings->low_stock_threshold ?? 10) }}" />
                @error('low_stock_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Drugs at or below this quantity flagged as low stock</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">
                  <i class="icon-base ti tabler-calendar-x text-warning me-1"></i>
                  Near Expiry Alert (days)
                </label>
                <input type="number" class="form-control @error('near_expiry_days') is-invalid @enderror" name="near_expiry_days" min="1"
                  value="{{ old('near_expiry_days', $settings->near_expiry_days ?? 30) }}" />
                @error('near_expiry_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Drugs expiring within this many days are flagged</div>
              </div>
              <div class="col-md-4">
                <label class="form-label">
                  <i class="icon-base ti tabler-clock-check text-info me-1"></i>
                  Staff Rounds Interval (hrs)
                </label>
                <input type="number" class="form-control @error('round_alert_hours') is-invalid @enderror" name="round_alert_hours" min="1" max="24"
                  value="{{ old('round_alert_hours', $settings->round_alert_hours ?? 1) }}" />
                @error('round_alert_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Default frequency between ward round checks</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <h5 class="card-header">Account Automation</h5>
          <div class="card-body">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="module-card d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-primary">
                        <i class="icon-base ti tabler-user-plus"></i>
                      </span>
                    </div>
                    <div>
                      <p class="fw-semibold mb-0" style="font-size:0.875rem;">Auto-create Patient Login</p>
                      <small class="text-muted">Create login when patient added with email</small>
                    </div>
                  </div>
                  <div class="form-check form-switch mb-0 ms-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                      name="auto_patient_login" value="1"
                      {{ old('auto_patient_login', $settings->auto_patient_login ?? true) ? 'checked' : '' }} />
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="module-card d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar">
                      <span class="avatar-initial rounded bg-label-info">
                        <i class="icon-base ti tabler-stethoscope"></i>
                      </span>
                    </div>
                    <div>
                      <p class="fw-semibold mb-0" style="font-size:0.875rem;">Auto-create Doctor Login</p>
                      <small class="text-muted">Create login when doctor is added</small>
                    </div>
                  </div>
                  <div class="form-check form-switch mb-0 ms-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                      name="auto_doctor_login" value="1"
                      {{ old('auto_doctor_login', $settings->auto_doctor_login ?? true) ? 'checked' : '' }} />
                  </div>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Save Changes
                </button>
                <a href="{{ route('settings.index') }}" class="btn btn-label-secondary">Reset</a>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

</div>
</form>

{{-- System Information --}}
<div class="mt-6">
  <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">System Information</p>
  <div class="row g-4">
    @foreach([
      ['Laravel', app()->version(), 'tabler-brand-laravel', 'danger'],
      ['PHP', phpversion(), 'tabler-brand-php', 'secondary'],
      ['Database', 'MySQL', 'tabler-database', 'warning'],
      ['Server Time', now()->format('d M Y · h:i A'), 'tabler-clock', 'info'],
    ] as [$label, $value, $icon, $color])
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <p class="text-heading mb-1">{{ $label }}</p>
              <h5 class="mb-0">{{ $value }}</h5>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-{{ $color }}">
                <i class="icon-base ti {{ $icon }} icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const logoInput   = document.getElementById('logoInput');
  const logoPreview = document.getElementById('logoPreview');

  if (logoInput) {
    logoInput.addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function (ev) {
        if (logoPreview.tagName === 'IMG') {
          logoPreview.src = ev.target.result;
        } else {
          const img = document.createElement('img');
          img.src = ev.target.result;
          img.className = 'rounded';
          img.style.cssText = 'width:88px;height:88px;object-fit:contain;background:var(--bs-body-bg);border:1px solid var(--bs-border-color);';
          img.id = 'logoPreview';
          logoPreview.replaceWith(img);
        }
      };
      reader.readAsDataURL(file);
    });
  }
});
</script>
@endsection