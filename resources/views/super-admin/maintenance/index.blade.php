@php
$configData = Helper::appClasses();
$platformSettings = \App\Models\PlatformSetting::current();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Platform Maintenance - HMS')

@section('page-style')
<style>
  .settings-nav .nav-link { color: var(--bs-body-color); border-radius: 6px; padding: 0.6rem 1rem; font-size: 0.875rem; font-weight: 500; width: 100%; text-align: left; border: none; background: transparent; }
  .settings-nav .nav-link.active { background: var(--bs-primary); color: #fff; }
  .settings-nav .nav-link:hover:not(.active) { background: var(--bs-primary-bg-subtle); color: var(--bs-primary); }
  .settings-nav .nav-link i { width: 20px; }
  .info-row { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid var(--bs-border-color); }
  .info-row:last-child { border-bottom: none; }
  .not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
  .status-card { padding: 1.1rem; border: 1px solid var(--bs-border-color); border-radius: 10px; height: 100%; text-decoration: none; color: inherit; display: block; transition: transform 0.15s ease, box-shadow 0.15s ease; }
  .status-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); color: inherit; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Platform Maintenance']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Platform Maintenance</h4>
    <p class="mb-0 text-body-secondary">Monitor system health, perform safe maintenance operations, and understand platform status.</p>
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
@if($status['isDownForMaintenance'])
<div class="alert alert-warning mb-6"><i class="icon-base ti tabler-tools me-2"></i><strong>Maintenance mode is currently ON.</strong></div>
@endif

{{-- Status cards --}}
<div class="row g-4 mb-6 row-cols-1 row-cols-sm-2 row-cols-xl-4">
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabHealth" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Application</small><strong>{{ $status['application']['environment'] }}</strong></div>
        <span class="badge bg-label-{{ $status['application']['debugMode'] ? 'warning' : 'success' }}">{{ $status['application']['debugMode'] ? 'Debug ON' : 'OK' }}</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabHealth" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Server</small><strong>PHP {{ $status['application']['phpVersion'] }}</strong></div>
        <span class="badge bg-label-success">Live</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabHealth" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Database</small><strong>{{ $status['database']['connectionName'] }}</strong></div>
        <span class="badge bg-label-{{ $status['database']['connected'] ? 'success' : 'danger' }}">{{ $status['database']['connected'] ? 'Connected' : 'Down' }}</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabHealth" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Cache</small><strong>{{ ucfirst($status['cache']['driver']) }}</strong></div>
        <span class="badge bg-label-{{ $status['cache']['healthy'] ? 'success' : 'danger' }}">{{ $status['cache']['healthy'] ? 'Healthy' : 'Unhealthy' }}</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabQueue" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Queue</small><strong>{{ ucfirst($status['queue']['driver']) }}</strong></div>
        <span class="badge bg-label-{{ ($status['queue']['failed'] ?? 0) > 0 ? 'danger' : 'success' }}">{{ $status['queue']['failed'] ?? 0 }} failed</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabHealth" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Storage</small><strong>{{ config('filesystems.default') }}</strong></div>
        <span class="badge bg-label-secondary">{{ $status['storage']['usedMb'] !== null ? number_format($status['storage']['usedMb'],0) . ' MB' : 'N/A' }}</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabMaintenanceMode" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Maintenance Mode</small><strong>{{ $status['isDownForMaintenance'] ? 'Down' : 'Live' }}</strong></div>
        <span class="badge bg-label-{{ $status['isDownForMaintenance'] ? 'danger' : 'success' }}">{{ $status['isDownForMaintenance'] ? 'OFFLINE' : 'ONLINE' }}</span>
      </div>
    </a>
  </div>
  <div class="col">
    <a href="#" data-bs-toggle="pill" data-bs-target="#tabBackup" class="status-card">
      <div class="d-flex justify-content-between align-items-start">
        <div><small class="text-body-secondary d-block mb-1">Last Backup</small><strong>{{ $status['backups']['latest'] ?? 'Not tracked yet' }}</strong></div>
        <span class="badge bg-label-secondary">{{ $status['backups']['configured'] ? 'Configured' : 'Not configured' }}</span>
      </div>
    </a>
  </div>
</div>

@if(count($status['alerts']))
<div class="alert alert-warning mb-6">
  <strong><i class="icon-base ti tabler-alert-triangle me-1"></i> System Alerts:</strong>
  <ul class="mb-0 mt-1">
    @foreach($status['alerts'] as $alert)
    <li>{{ $alert['message'] ?? $alert }}</li>
    @endforeach
  </ul>
</div>
@endif

<div class="row">
  {{-- Left Sidebar Nav --}}
  <div class="col-xl-3 col-lg-4 mb-6 mb-lg-0">
    <div class="card">
      <div class="card-body p-0">
        <div class="p-3 settings-nav">
          <nav class="nav flex-column gap-1">
            <button type="button" class="nav-link active d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabHealth"><i class="icon-base ti tabler-heartbeat icon-sm"></i> System Health</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabMaintenanceMode"><i class="icon-base ti tabler-tools icon-sm"></i> Maintenance Mode</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabCache"><i class="icon-base ti tabler-database-cog icon-sm"></i> Cache Management</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabQueue"><i class="icon-base ti tabler-list-check icon-sm"></i> Queue Management</button>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabDatabase"><i class="icon-base ti tabler-database icon-sm"></i> Database</button>
            <a href="{{ route('super-admin.maintenance.logs') }}" class="nav-link d-flex align-items-center gap-2"><i class="icon-base ti tabler-file-text icon-sm"></i> Log Viewer</a>
            <button type="button" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tabBackup"><i class="icon-base ti tabler-cloud-upload icon-sm"></i> Backup</button>
          </nav>
        </div>
      </div>
    </div>
  </div>

  {{-- Right Content --}}
  <div class="col-xl-9 col-lg-8">
    <div class="tab-content">

      {{-- ===== System Health ===== --}}
      <div class="tab-pane fade show active" id="tabHealth">
        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Application</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Laravel Version</span><strong>{{ $status['application']['laravelVersion'] }}</strong></div>
            <div class="info-row"><span>PHP Version</span><strong>{{ $status['application']['phpVersion'] }}</strong></div>
            <div class="info-row"><span>Environment</span><strong class="text-capitalize">{{ $status['application']['environment'] }}</strong></div>
            <div class="info-row"><span>Debug Status</span><strong>
              @if($status['application']['debugMode'])<span class="badge bg-label-warning">Enabled</span>@else<span class="badge bg-label-success">Disabled</span>@endif
            </strong></div>
            <div class="info-row"><span>Application URL</span><strong>{{ config('app.url') }}</strong></div>
          </div>
        </div>

        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Database</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Driver</span><strong>{{ $status['database']['connectionName'] }}</strong></div>
            <div class="info-row"><span>Connection Status</span><strong>
              @if($status['database']['connected'])<span class="badge bg-label-success">Connected</span>@else<span class="badge bg-label-danger">Down</span>@endif
            </strong></div>
            <div class="info-row"><span>Database Size</span><strong>{{ $status['database']['sizeMb'] !== null ? number_format($status['database']['sizeMb'],1).' MB' : 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Migration Status</span><strong>{{ $status['database']['migrations']['ran'] ?? '—' }} / {{ $status['database']['migrations']['total'] ?? '—' }} ran @if(($status['database']['migrations']['pending'] ?? 0) > 0)<span class="badge bg-label-warning ms-1">{{ $status['database']['migrations']['pending'] }} pending</span>@else<span class="badge bg-label-success ms-1">Up to date</span>@endif</strong></div>
          </div>
        </div>

        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Cache</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Driver</span><strong>{{ ucfirst($status['cache']['driver']) }}</strong></div>
            <div class="info-row"><span>Connection Status</span><strong>
              @if($status['cache']['healthy'])<span class="badge bg-label-success">Healthy</span>@else<span class="badge bg-label-danger">Unhealthy</span>@endif
            </strong></div>
            <div class="info-row"><span>Latency</span><strong>{{ $status['cache']['latencyMs'] !== null ? $status['cache']['latencyMs'].' ms' : 'Not tracked yet' }}</strong></div>
          </div>
        </div>

        <div class="card mb-6">
          <div class="card-header border-bottom"><h6 class="mb-0">Queue</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Driver</span><strong>{{ ucfirst($status['queue']['driver']) }}</strong></div>
            <div class="info-row"><span>Pending Jobs</span><strong>{{ $status['queue']['pending'] ?? 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Failed Jobs</span><strong>{{ $status['queue']['failed'] ?? 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Worker Status</span><strong class="not-tracked">Not tracked yet — no worker heartbeat mechanism exists</strong></div>
          </div>
        </div>

        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Storage</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Driver</span><strong>{{ config('filesystems.default') }}</strong></div>
            <div class="info-row"><span>Storage Used</span><strong>{{ $status['storage']['usedMb'] !== null ? number_format($status['storage']['usedMb'],1).' MB' : 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Available Space</span><strong>{{ $status['storage']['availableMb'] !== null ? number_format($status['storage']['availableMb'],1).' MB' : 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Upload Configuration</span><strong>Max {{ $platformSettings->max_upload_size_mb }} MB &middot; {{ $platformSettings->allowed_file_types }}</strong></div>
          </div>
        </div>
      </div>

      {{-- ===== Maintenance Mode ===== --}}
      <div class="tab-pane fade" id="tabMaintenanceMode">
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Maintenance Mode</h6></div>
          <div class="card-body">
            <p>Status:
              @if($status['isDownForMaintenance'])
              <span class="badge bg-label-danger">Down for maintenance</span>
              @else
              <span class="badge bg-label-success">Live</span>
              @endif
            </p>
            @if(!$status['isDownForMaintenance'])
            <form action="{{ route('super-admin.settings.maintenance.enable') }}" method="POST" onsubmit="return confirm('This will take the entire platform offline for every visitor except you. Continue?');">
              @csrf
              <div class="mb-3">
                <label class="form-label">Maintenance Message</label>
                <textarea name="message" class="form-control" rows="2" maxlength="500" placeholder="We're performing scheduled maintenance. Back shortly."></textarea>
              </div>
              <button type="submit" class="btn btn-label-danger"><i class="icon-base ti tabler-tools me-1"></i> Enable Maintenance Mode</button>
            </form>
            @else
            <form action="{{ route('super-admin.settings.maintenance.disable') }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-circle-check me-1"></i> Disable Maintenance Mode</button>
            </form>
            @endif
            <small class="text-muted d-block mt-3">Uses Laravel's own <code>artisan down</code>/<code>up</code> mechanism. This action is shared with, not duplicated from, <a href="{{ route('super-admin.settings.index') }}">Global Settings</a> — both pages control the exact same maintenance state.</small>
          </div>
        </div>
      </div>

      {{-- ===== Cache Management ===== --}}
      <div class="tab-pane fade" id="tabCache">
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Cache Management</h6></div>
          <div class="card-body">
            <p class="text-body-secondary">Clear cached data safely. Each action is confirmed and audit-logged.</p>
            <div class="row g-3">
              @foreach(['application' => ['tabler-database-cog','Clears cached application data (Cache::flush).'], 'config' => ['tabler-settings','Clears the cached configuration file.'], 'route' => ['tabler-route','Clears the cached route file.'], 'view' => ['tabler-layout','Clears compiled Blade view files.']] as $type => [$icon, $desc])
              <div class="col-md-6">
                <div class="border rounded p-3 h-100 d-flex flex-column">
                  <div class="d-flex align-items-center gap-2 mb-2"><i class="icon-base ti {{ $icon }}"></i><strong class="text-capitalize">{{ $type }} Cache</strong></div>
                  <p class="small text-body-secondary flex-grow-1">{{ $desc }}</p>
                  <form action="{{ route('super-admin.maintenance.cache.clear', $type) }}" method="POST" onsubmit="return confirm('Clear the {{ $type }} cache now?');">
                    @csrf
                    <button type="submit" class="btn btn-label-secondary btn-sm w-100">Clear {{ ucfirst($type) }} Cache</button>
                  </form>
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Queue Management ===== --}}
      <div class="tab-pane fade" id="tabQueue">
        <div class="card mb-6">
          <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Queue Status</h6>
            <div class="d-flex gap-2">
              <form action="{{ route('super-admin.maintenance.queue.retry') }}" method="POST" onsubmit="return confirm('Retry every failed job?');">
                @csrf
                <button type="submit" class="btn btn-label-primary btn-sm"><i class="icon-base ti tabler-refresh me-1"></i> Retry All Failed</button>
              </form>
              <form action="{{ route('super-admin.maintenance.queue.clear') }}" method="POST" onsubmit="return confirm('Permanently clear every failed job record? This cannot be undone.');">
                @csrf
                <button type="submit" class="btn btn-label-danger btn-sm"><i class="icon-base ti tabler-trash me-1"></i> Clear All Failed</button>
              </form>
            </div>
          </div>
          <div class="card-body">
            <div class="info-row"><span>Driver</span><strong>{{ ucfirst($status['queue']['driver']) }}</strong></div>
            <div class="info-row"><span>Pending Jobs</span><strong>{{ $status['queue']['pending'] ?? 'Not tracked yet' }}</strong></div>
            <div class="info-row"><span>Failed Jobs</span><strong>{{ $status['queue']['failed'] ?? 0 }}</strong></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Failed Jobs</h6></div>
          <div class="table-responsive">
            <table class="table table-sm" id="failedJobsTable" style="width:100%">
              <thead><tr><th>Connection</th><th>Queue</th><th>Exception</th><th>Failed At</th></tr></thead>
            </table>
          </div>
        </div>
      </div>

      {{-- ===== Database ===== --}}
      <div class="tab-pane fade" id="tabDatabase">
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Database (Read-Only)</h6></div>
          <div class="card-body">
            <div class="info-row"><span>Connection Status</span><strong>
              @if($status['database']['connected'])<span class="badge bg-label-success">Connected</span>@else<span class="badge bg-label-danger">Down</span>@endif
            </strong></div>
            <div class="info-row"><span>Total Migrations</span><strong>{{ $status['database']['migrations']['total'] ?? '—' }}</strong></div>
            <div class="info-row"><span>Ran</span><strong>{{ $status['database']['migrations']['ran'] ?? '—' }}</strong></div>
            <div class="info-row"><span>Pending</span><strong>{{ $status['database']['migrations']['pending'] ?? '—' }}</strong></div>
            <p class="text-muted small mt-3 mb-0"><i class="icon-base ti tabler-info-circle me-1"></i> No destructive database operations are available from this screen — no drop, delete, or direct SQL execution.</p>
          </div>
        </div>
      </div>

      {{-- ===== Backup ===== --}}
      <div class="tab-pane fade" id="tabBackup">
        <div class="card">
          <div class="card-header border-bottom"><h6 class="mb-0">Backup</h6></div>
          <div class="card-body">
            @if($status['backups']['configured'])
            <div class="info-row"><span>Last Backup</span><strong>{{ $status['backups']['latest'] }}</strong></div>
            <div class="info-row"><span>Status</span><strong>{{ $status['backups']['status'] }}</strong></div>
            @else
            <p class="not-tracked mb-0">Not tracked yet — no backup package or mechanism is installed in this application. A "Trigger Backup" action will appear here once one is.</p>
            @endif
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#failedJobsTable').DataTable({
    ajax: { url: '{{ route('super-admin.maintenance.queue.failed-jobs') }}', dataSrc: 'data' },
    columns: [
      { data: 'connection' },
      { data: 'queue' },
      { data: 'exception', render: (d) => (d || '').substring(0, 120) + ((d || '').length > 120 ? '…' : '') },
      { data: 'failed_at' },
    ],
    pageLength: 10,
    language: { emptyTable: 'No failed jobs.', zeroRecords: 'No failed jobs.' },
  });
});
</script>
@endsection
