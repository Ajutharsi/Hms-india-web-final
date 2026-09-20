@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Global Settings - HMS')

@section('page-style')
<style>
  .settings-nav .nav-link { color: var(--bs-body-color); border-radius: 6px; padding: 0.6rem 1rem; font-size: 0.875rem; font-weight: 500; width: 100%; text-align: left; border: none; background: transparent; }
  .settings-nav .nav-link.active { background: var(--bs-primary); color: #fff; }
  .settings-nav .nav-link:hover:not(.active) { background: var(--bs-primary-bg-subtle); color: var(--bs-primary); }
  .settings-nav .nav-link i { width: 20px; }
  .info-row { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid var(--bs-border-color); }
  .info-row:last-child { border-bottom: none; }
  .not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Global Settings']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Global Settings</h4>
    <p class="mb-0 text-body-secondary">Platform-wide configuration for the entire installation.</p>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-6">
  <i class="icon-base ti tabler-circle-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-6">
  <i class="icon-base ti tabler-alert-circle me-2"></i>{{ session('error') }}
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
@if($isDownForMaintenance)
<div class="alert alert-warning mb-6">
  <i class="icon-base ti tabler-tools me-2"></i><strong>Maintenance mode is currently ON.</strong> Only you (via the bypass link) can browse the site normally.
</div>
@endif

<div class="row">

  {{-- Left Sidebar Nav --}}
  <div class="col-xl-3 col-lg-4 mb-6 mb-lg-0">
    <div class="card">
      <div class="card-body p-0">
        <div class="p-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-lg">
              <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:1.5rem;">
                <i class="icon-base ti tabler-settings-cog"></i>
              </span>
            </div>
            <div>
              <h6 class="mb-0">{{ $settings->platform_name ?: config('platform.name') }}</h6>
              <small class="text-muted">Global Settings</small>
            </div>
          </div>
        </div>
        <div class="p-3 settings-nav">
          <nav class="nav flex-column gap-1">
            <button type="button" class="nav-link active d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabPlatform"><i class="icon-base ti tabler-building-skyscraper icon-sm"></i> Platform</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabRegional"><i class="icon-base ti tabler-world icon-sm"></i> Regional</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabSecurity"><i class="icon-base ti tabler-shield-lock icon-sm"></i> Auth & Security</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabEmail"><i class="icon-base ti tabler-mail icon-sm"></i> Email</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabStorage"><i class="icon-base ti tabler-database icon-sm"></i> File & Storage</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabFeatures"><i class="icon-base ti tabler-apps icon-sm"></i> Platform Features</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabMaintenance"><i class="icon-base ti tabler-tools icon-sm"></i> Maintenance</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabSystem"><i class="icon-base ti tabler-info-circle icon-sm"></i> System Information</button>
          </nav>
        </div>
      </div>
    </div>
  </div>

  {{-- Right Content --}}
  <div class="col-xl-9 col-lg-8">
    <div class="tab-content">

      {{-- ===== Platform ===== --}}
      <div class="tab-pane fade show active" id="tabPlatform">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Branding</h6></div>
          <div class="card-body">
            <form action="{{ route('super-admin.settings.platform') }}" method="POST" enctype="multipart/form-data">
              @csrf @method('PUT')
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Logo</label>
                  <input type="file" name="logo" class="form-control" accept="image/*">
                  @if($settings->logo_url)<img src="{{ $settings->logo_url }}" style="height:40px;" class="mt-2 d-block">@endif
                </div>
                <div class="col-md-6">
                  <label class="form-label">Favicon</label>
                  <input type="file" name="favicon" class="form-control" accept="image/*">
                  @if($settings->favicon_url)<img src="{{ $settings->favicon_url }}" style="height:24px;" class="mt-2 d-block">@endif
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Platform Name *</label>
                  <input type="text" name="platform_name" class="form-control" value="{{ old('platform_name', $settings->platform_name) }}" required maxlength="255">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Company Name</label>
                  <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $settings->company_name) }}" maxlength="255">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Platform Description</label>
                <textarea name="platform_description" class="form-control" rows="2" maxlength="1000">{{ old('platform_description', $settings->platform_description) }}</textarea>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Support Email</label>
                  <input type="email" name="support_email" class="form-control" value="{{ old('support_email', $settings->support_email) }}" maxlength="255">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Support Phone</label>
                  <input type="text" name="support_phone" class="form-control" value="{{ old('support_phone', $settings->support_phone) }}" maxlength="20">
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Website</label>
                  <input type="text" name="website" class="form-control" value="{{ old('website', $settings->website) }}" maxlength="255">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Copyright Text</label>
                  <input type="text" name="copyright_text" class="form-control" value="{{ old('copyright_text', $settings->copyright_text) }}" maxlength="255" placeholder="© {{ date('Y') }} My Company">
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Platform Settings</button>
            </form>
          </div>
        </div>
      </div>

      {{-- ===== Regional ===== --}}
      <div class="tab-pane fade" id="tabRegional">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Regional Settings</h6></div>
          <div class="card-body">
            <form action="{{ route('super-admin.settings.regional') }}" method="POST">
              @csrf @method('PUT')
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Default Country</label>
                  <input type="text" name="default_country" class="form-control" value="{{ old('default_country', $settings->default_country) }}" maxlength="255">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Default Currency</label>
                  <select name="default_currency" class="form-select">
                    @foreach(['LKR','USD','INR','EUR','GBP'] as $c)
                    <option value="{{ $c }}" @selected(old('default_currency', $settings->default_currency) === $c)>{{ $c }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Timezone</label>
                  <select name="default_timezone" class="form-select">
                    @foreach($timezones as $tz)
                    <option value="{{ $tz }}" @selected(old('default_timezone', $settings->default_timezone) === $tz)>{{ $tz }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Language</label>
                  <select name="default_language" class="form-select">
                    @foreach(['en'=>'English','si'=>'Sinhala','ta'=>'Tamil'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('default_language', $settings->default_language) === $code)>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Date Format</label>
                  <select name="default_date_format" class="form-select">
                    @foreach(['d M Y','Y-m-d','m/d/Y','d/m/Y'] as $f)
                    <option value="{{ $f }}" @selected(old('default_date_format', $settings->default_date_format) === $f)>{{ $f }} ({{ now()->format($f) }})</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Time Format</label>
                  <select name="default_time_format" class="form-select">
                    <option value="24h" @selected(old('default_time_format', $settings->default_time_format) === '24h')>24-hour</option>
                    <option value="12h" @selected(old('default_time_format', $settings->default_time_format) === '12h')>12-hour</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Regional Settings</button>
            </form>
          </div>
        </div>
      </div>

      {{-- ===== Auth & Security ===== --}}
      <div class="tab-pane fade" id="tabSecurity">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Authentication & Security</h6></div>
          <div class="card-body">
            <form action="{{ route('super-admin.settings.security') }}" method="POST">
              @csrf @method('PUT')
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Password Minimum Length</label>
                  <input type="number" name="password_min_length" class="form-control" value="{{ old('password_min_length', $settings->password_min_length) }}" min="6" max="32" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label d-block">Password Complexity</label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="password_require_uppercase" value="1" id="pwUpper" @checked(old('password_require_uppercase', $settings->password_require_uppercase))>
                    <label class="form-check-label" for="pwUpper">Uppercase</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="password_require_number" value="1" id="pwNumber" @checked(old('password_require_number', $settings->password_require_number))>
                    <label class="form-check-label" for="pwNumber">Number</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="password_require_symbol" value="1" id="pwSymbol" @checked(old('password_require_symbol', $settings->password_require_symbol))>
                    <label class="form-check-label" for="pwSymbol">Symbol</label>
                  </div>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Session Timeout (minutes)</label>
                  <input type="number" name="session_timeout_minutes" class="form-control" value="{{ old('session_timeout_minutes', $settings->session_timeout_minutes) }}" min="5" max="1440" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Remember Me Duration (days)</label>
                  <input type="number" name="remember_me_days" class="form-control" value="{{ old('remember_me_days', $settings->remember_me_days) }}" min="1" max="3650" required>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Maximum Login Attempts</label>
                  <input type="number" name="max_login_attempts" class="form-control" value="{{ old('max_login_attempts', $settings->max_login_attempts) }}" min="3" max="20" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Account Lockout Duration (minutes)</label>
                  <input type="number" name="lockout_duration_minutes" class="form-control" value="{{ old('lockout_duration_minutes', $settings->lockout_duration_minutes) }}" min="1" max="1440" required>
                </div>
              </div>
              <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" name="two_factor_enabled" value="1" id="twoFactor" @checked(old('two_factor_enabled', $settings->two_factor_enabled))>
                <label class="form-check-label" for="twoFactor">Two-Factor Authentication</label>
                <div><small class="text-muted">Enables Fortify's two-factor authentication feature platform-wide. Requires users to set up 2FA from their profile.</small></div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Security Settings</button>
            </form>
          </div>
        </div>

        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Force Password Change</h6></div>
          <div class="card-body">
            <p class="text-body-secondary">Require every user account to change their password before they can use the application again.</p>
            <form action="{{ route('super-admin.settings.force-password-change') }}" method="POST" onsubmit="return confirm('This will require every user to change their password on next login. Continue?');">
              @csrf
              <button type="submit" class="btn btn-label-danger"><i class="icon-base ti tabler-key me-1"></i> Force Password Change for All Users</button>
            </form>
          </div>
        </div>
      </div>

      {{-- ===== Email ===== --}}
      <div class="tab-pane fade" id="tabEmail">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Email (SMTP) Settings</h6></div>
          <div class="card-body">
            <form action="{{ route('super-admin.settings.email') }}" method="POST">
              @csrf @method('PUT')
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">Mail Driver</label>
                  <select name="mail_mailer" class="form-select">
                    <option value="">Use default ({{ config('mail.default') }})</option>
                    @foreach(['smtp','log','array','sendmail'] as $m)
                    <option value="{{ $m }}" @selected(old('mail_mailer', $settings->mail_mailer) === $m)>{{ ucfirst($m) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">SMTP Host</label>
                  <input type="text" name="mail_host" class="form-control" value="{{ old('mail_host', $settings->mail_host) }}" placeholder="{{ config('mail.mailers.smtp.host') }}" maxlength="255">
                </div>
                <div class="col-md-4">
                  <label class="form-label">SMTP Port</label>
                  <input type="number" name="mail_port" class="form-control" value="{{ old('mail_port', $settings->mail_port) }}" placeholder="{{ config('mail.mailers.smtp.port') }}" min="1" max="65535">
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">Encryption</label>
                  <select name="mail_encryption" class="form-select">
                    <option value="">None / default</option>
                    <option value="tls" @selected(old('mail_encryption', $settings->mail_encryption) === 'tls')>TLS</option>
                    <option value="ssl" @selected(old('mail_encryption', $settings->mail_encryption) === 'ssl')>SSL</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Username</label>
                  <input type="text" name="mail_username" class="form-control" value="{{ old('mail_username', $settings->mail_username) }}" maxlength="255" autocomplete="off">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Password</label>
                  <input type="password" name="mail_password" class="form-control" placeholder="{{ $settings->mail_password ? '•••••••• (unchanged if left blank)' : 'Not set' }}" autocomplete="new-password" maxlength="255">
                </div>
              </div>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label">Sender Name</label>
                  <input type="text" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $settings->mail_from_name) }}" placeholder="{{ config('mail.from.name') }}" maxlength="255">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Sender Email</label>
                  <input type="email" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $settings->mail_from_address) }}" placeholder="{{ config('mail.from.address') }}" maxlength="255">
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Email Settings</button>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Send Test Email</h6></div>
          <div class="card-body">
            <form action="{{ route('super-admin.settings.email.test') }}" method="POST" class="d-flex gap-2 flex-wrap">
              @csrf
              <input type="email" name="test_email" class="form-control" style="max-width:320px;" placeholder="you@example.com" required>
              <button type="submit" class="btn btn-label-primary"><i class="icon-base ti tabler-send me-1"></i> Send Test Email</button>
            </form>
            <small class="text-muted d-block mt-2">Sends using the current mailer (<strong>{{ config('mail.default') }}</strong>). With the <code>log</code> driver, check <code>storage/logs/laravel.log</code> instead of an inbox.</small>
          </div>
        </div>
      </div>

      {{-- ===== File & Storage ===== --}}
      <div class="tab-pane fade" id="tabStorage">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Upload Limits</h6></div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Max Upload Size (MB)</label>
                <input type="number" class="form-control" value="{{ $settings->max_upload_size_mb }}" disabled>
                <small class="text-muted">Configurable via the API/tinker today; not yet wired into every upload form across the app (out of scope for this module — see report).</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Allowed File Types</label>
                <input type="text" class="form-control" value="{{ $settings->allowed_file_types }}" disabled>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Storage</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Storage Driver</span><strong>{{ config('filesystems.default') }}</strong></div>
            <div class="info-row"><span>Storage Used</span><strong>{{ $systemInfo['storage']['usedMb'] !== null ? number_format($systemInfo['storage']['usedMb'], 1) . ' MB' : 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Storage Available</span><strong>{{ $systemInfo['storage']['availableMb'] !== null ? number_format($systemInfo['storage']['availableMb'], 1) . ' MB' : 'Not tracked yet' }}</strong></div>
          </div>
        </div>
      </div>

      {{-- ===== Platform Features ===== --}}
      <div class="tab-pane fade" id="tabFeatures">
        <div class="card">
          <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Platform Features</h6>
            <small class="text-muted">Reuses the same feature toggles as Hospital Settings</small>
          </div>
          <div class="card-body">
            <form action="{{ route('super-admin.settings.features') }}" method="POST">
              @csrf @method('PUT')
              <div class="row g-3 mb-4">
                @foreach($featureModules as $key => $default)
                <div class="col-md-6 col-xl-4">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="features[{{ $key }}]" value="1" id="feat_{{ $key }}" @checked($features[$key] ?? $default)>
                    <label class="form-check-label text-capitalize" for="feat_{{ $key }}">{{ str_replace('_', ' ', $key) }}</label>
                  </div>
                </div>
                @endforeach
              </div>
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Feature Toggles</button>
            </form>
          </div>
        </div>
      </div>

      {{-- ===== Maintenance ===== --}}
      <div class="tab-pane fade" id="tabMaintenance">
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Maintenance Mode</h6></div>
          <div class="card-body">
            <p>Status:
              @if($isDownForMaintenance)
              <span class="badge bg-label-danger">Down for maintenance</span>
              @else
              <span class="badge bg-label-success">Live</span>
              @endif
            </p>
            @if(!$isDownForMaintenance)
            <form action="{{ route('super-admin.settings.maintenance.enable') }}" method="POST">
              @csrf
              <div class="mb-3">
                <label class="form-label">Maintenance Message</label>
                <textarea name="message" class="form-control" rows="2" maxlength="500" placeholder="We're performing scheduled maintenance. Back shortly.">{{ old('message', $settings->maintenance_message) }}</textarea>
              </div>
              <button type="submit" class="btn btn-label-danger"><i class="icon-base ti tabler-tools me-1"></i> Enable Maintenance Mode</button>
            </form>
            @else
            <form action="{{ route('super-admin.settings.maintenance.disable') }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-circle-check me-1"></i> Disable Maintenance Mode</button>
            </form>
            @endif
            <small class="text-muted d-block mt-3">Uses Laravel's own <code>artisan down</code>/<code>up</code> mechanism — no custom framework. While enabled, your browser bypasses the maintenance page automatically; every other visitor sees the message above.</small>
          </div>
        </div>
      </div>

      {{-- ===== System Information ===== --}}
      <div class="tab-pane fade" id="tabSystem">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">System Information</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Laravel Version</span><strong>{{ $systemInfo['application']['laravelVersion'] }}</strong></div>
            <div class="info-row"><span>PHP Version</span><strong>{{ $systemInfo['application']['phpVersion'] }}</strong></div>
            <div class="info-row"><span>Database Driver</span><strong>{{ $systemInfo['database']['connectionName'] }}</strong></div>
            <div class="info-row"><span>Cache Driver</span><strong>{{ $systemInfo['cache']['driver'] }}</strong></div>
            <div class="info-row"><span>Queue Driver</span><strong>{{ $systemInfo['queue']['driver'] }}</strong></div>
            <div class="info-row"><span>Storage Driver</span><strong>{{ config('filesystems.default') }}</strong></div>
            <div class="info-row"><span>Application Environment</span><strong class="text-capitalize">{{ $systemInfo['application']['environment'] }}</strong></div>
            <div class="info-row"><span>Debug Status</span><strong>
              @if($systemInfo['application']['debugMode'])
              <span class="badge bg-label-warning">Enabled</span>
              @else
              <span class="badge bg-label-success">Disabled</span>
              @endif
            </strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Reset</h6></div>
          <div class="card-body">
            <p class="text-body-secondary">Resets Platform, Regional, Auth & Security settings to their defaults. Email credentials and the maintenance message are left untouched.</p>
            <form action="{{ route('super-admin.settings.reset') }}" method="POST" onsubmit="return confirm('Reset Global Settings to defaults? This cannot be undone.');">
              @csrf
              <button type="submit" class="btn btn-label-secondary"><i class="icon-base ti tabler-restore me-1"></i> Reset to Defaults</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection
