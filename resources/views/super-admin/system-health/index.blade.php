@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'System Health - HMS')

@section('page-style')
<style>
.health-section-title { font-size: 0.9375rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.health-row { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--bs-border-color); }
.health-row:last-child { border-bottom: none; }
.not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Platform Analytics', 'url' => route('super-admin.analytics.index')], ['label' => 'System Health']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">System Health</h4>
    <p class="mb-0 text-body-secondary">Live status of the application, database, cache, queue, and storage.</p>
  </div>
  @if(count($health['alerts']) === 0)
  <span class="badge bg-label-success py-2 px-3"><i class="icon-base ti tabler-circle-check me-1"></i> All systems normal</span>
  @else
  <span class="badge bg-label-danger py-2 px-3"><i class="icon-base ti tabler-alert-triangle me-1"></i> {{ count($health['alerts']) }} alert(s)</span>
  @endif
</div>

@if(count($health['alerts']) > 0)
<div class="card mb-6">
  <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-alert-triangle me-1"></i> System Alerts</h6></div>
  <div class="card-body">
    @foreach($health['alerts'] as $alert)
    <div class="alert alert-{{ $alert['level'] }} mb-2 py-2 px-3">{{ $alert['message'] }}</div>
    @endforeach
  </div>
</div>
@endif

<div class="row g-4">

  {{-- Application --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-app-window me-1"></i> Application</h6></div>
      <div class="card-body">
        <div class="health-row"><span>Laravel Version</span><strong>{{ $health['application']['laravelVersion'] }}</strong></div>
        <div class="health-row"><span>PHP Version</span><strong>{{ $health['application']['phpVersion'] }}</strong></div>
        <div class="health-row"><span>Environment</span><strong>{{ ucfirst($health['application']['environment']) }}</strong></div>
        <div class="health-row">
          <span>Debug Mode</span>
          <strong class="{{ $health['application']['debugMode'] ? 'text-danger' : 'text-success' }}">
            {{ $health['application']['debugMode'] ? 'Enabled' : 'Disabled' }}
          </strong>
        </div>
      </div>
    </div>
  </div>

  {{-- Database --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-database me-1"></i> Database</h6></div>
      <div class="card-body">
        <div class="health-row">
          <span>Connection Status</span>
          <strong class="{{ $health['database']['connected'] ? 'text-success' : 'text-danger' }}">
            {{ $health['database']['connected'] ? 'Connected' : 'Disconnected' }} ({{ $health['database']['connectionName'] }})
          </strong>
        </div>
        <div class="health-row">
          <span>Database Size</span>
          @if($health['database']['sizeMb'] !== null)
          <strong>{{ number_format($health['database']['sizeMb'], 2) }} MB</strong>
          @else
          <span class="not-tracked">Not tracked yet</span>
          @endif
        </div>
        <div class="health-row">
          <span>Migration Status</span>
          @if($health['database']['migrations']['pending'] === null)
          <span class="not-tracked">Not tracked yet</span>
          @elseif($health['database']['migrations']['pending'] === 0)
          <strong class="text-success">Up to date ({{ $health['database']['migrations']['ran'] }} ran)</strong>
          @else
          <strong class="text-warning">{{ $health['database']['migrations']['pending'] }} pending</strong>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Cache --}}
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-bolt me-1"></i> Cache</h6></div>
      <div class="card-body">
        <div class="health-row"><span>Cache Driver</span><strong>{{ $health['cache']['driver'] }}</strong></div>
        <div class="health-row">
          <span>Cache Health</span>
          <strong class="{{ $health['cache']['healthy'] ? 'text-success' : 'text-danger' }}">
            {{ $health['cache']['healthy'] ? 'Healthy' : 'Unavailable' }}
            @if($health['cache']['latencyMs'] !== null) ({{ $health['cache']['latencyMs'] }} ms) @endif
          </strong>
        </div>
      </div>
    </div>
  </div>

  {{-- Queue --}}
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-stack-2 me-1"></i> Queue</h6></div>
      <div class="card-body">
        <div class="health-row"><span>Queue Driver</span><strong>{{ $health['queue']['driver'] }}</strong></div>
        <div class="health-row">
          <span>Pending Jobs</span>
          @if($health['queue']['pending'] === null)<span class="not-tracked">Not tracked yet</span>@else<strong>{{ $health['queue']['pending'] }}</strong>@endif
        </div>
        <div class="health-row">
          <span>Failed Jobs</span>
          @if($health['queue']['failed'] === null)
          <span class="not-tracked">Not tracked yet</span>
          @else
          <strong class="{{ $health['queue']['failed'] > 0 ? 'text-danger' : 'text-success' }}">{{ $health['queue']['failed'] }}</strong>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Scheduler --}}
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-clock me-1"></i> Scheduler</h6></div>
      <div class="card-body">
        @if(!$health['scheduler']['configured'])
        <p class="not-tracked mb-0">No scheduled tasks are configured on this installation yet.</p>
        @else
        <div class="health-row"><span>Last Scheduled Run</span><strong>{{ $health['scheduler']['lastRun'] }}</strong></div>
        <div class="health-row"><span>Next Scheduled Run</span><strong>{{ $health['scheduler']['nextRun'] }}</strong></div>
        @endif
      </div>
    </div>
  </div>

  {{-- Storage --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-server-2 me-1"></i> Storage</h6></div>
      <div class="card-body">
        <div class="health-row">
          <span>Total Storage Used</span>
          @if($health['storage']['usedMb'] !== null)<strong>{{ number_format($health['storage']['usedMb'], 2) }} MB</strong>@else<span class="not-tracked">Not tracked yet</span>@endif
        </div>
        <div class="health-row">
          <span>Available Storage</span>
          @if($health['storage']['availableMb'] !== null)<strong>{{ number_format($health['storage']['availableMb'] / 1024, 2) }} GB</strong>@else<span class="not-tracked">Not tracked yet</span>@endif
        </div>
      </div>
    </div>
  </div>

  {{-- Backups --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-cloud-upload me-1"></i> Backups</h6></div>
      <div class="card-body">
        @if(!$health['backups']['configured'])
        <p class="not-tracked mb-0">Not tracked yet — no backup mechanism is configured on this installation.</p>
        @else
        <div class="health-row"><span>Latest Backup</span><strong>{{ $health['backups']['latest'] }}</strong></div>
        <div class="health-row"><span>Backup Status</span><strong>{{ $health['backups']['status'] }}</strong></div>
        @endif
      </div>
    </div>
  </div>

  {{-- Recent Errors --}}
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-bug me-1"></i> Recent Errors</h6></div>
      <div class="p-0">
        @forelse($health['recentErrors'] as $err)
        <div class="d-flex align-items-start gap-3 px-4 py-3 border-bottom">
          <i class="icon-base ti tabler-alert-circle text-danger mt-1"></i>
          <div>
            <p class="mb-0 small font-monospace">{{ $err['message'] }}</p>
            <small class="text-muted">{{ $err['time'] }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="not-tracked">No recent errors logged</small></div>
        @endforelse
      </div>
    </div>
  </div>

</div>

@endsection
