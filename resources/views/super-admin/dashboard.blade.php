@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Platform Dashboard - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-style')
<style>
.kpi-card-link { display: block; width: 100%; text-align: left; text-decoration: none; color: inherit; border: none; background: none; padding: 0; }
.kpi-card { transition: transform 0.15s ease, box-shadow 0.15s ease; cursor: pointer; }
.kpi-card-link:hover .kpi-card, .kpi-card-link:focus-visible .kpi-card { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }

.platform-notification-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.25rem; border-bottom: 1px solid var(--bs-border-color); text-decoration: none; color: inherit; border-radius: 6px; }
.platform-notification-item:last-child { border-bottom: none; }
.platform-notification-item:hover { background: var(--bs-secondary-bg, rgba(0,0,0,0.03)); color: inherit; }
.platform-notification-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

.quick-action-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 1.25rem 0.5rem; border-radius: 12px; border: 1px solid var(--bs-border-color); text-decoration: none; color: var(--bs-body-color); transition: all 0.15s ease; height: 100%; min-height: 104px; text-align: center; overflow: hidden; }
.quick-action-btn:hover { border-color: var(--bs-primary); background: rgba(105,108,255,0.05); color: var(--bs-primary); }
.quick-action-btn .quick-action-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(105,108,255,0.08); color: var(--bs-primary); flex-shrink: 0; }
.quick-action-btn small { display: block; width: 100%; overflow-wrap: break-word; word-break: break-word; line-height: 1.25; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Platform Overview</h4>
    <p class="mb-0 text-body-secondary">Welcome back, {{ auth()->user()->name }} — here's how the platform is doing.</p>
  </div>
  <span class="badge bg-label-primary py-2 px-3"><i class="icon-base ti tabler-crown me-1"></i> Super Admin</span>
</div>

{{-- KPI Strip --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Total Hospitals" :value="$platformStats['total']" sub="All registered tenants"
      icon="tabler-building-hospital" color="primary" :href="route('super-admin.hospitals.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Active Hospitals" :value="$platformStats['active']" sub="Paying subscribers"
      icon="tabler-circle-check" color="success" :href="route('super-admin.hospitals.index', ['status' => 'active'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Trial Hospitals" :value="$platformStats['trial']" sub="Evaluating the platform"
      icon="tabler-clock-hour-4" color="warning" :href="route('super-admin.hospitals.index', ['status' => 'trial'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Suspended Hospitals" :value="$platformStats['suspended']" sub="Needs follow-up"
      icon="tabler-ban" color="warning" :href="route('super-admin.hospitals.index', ['status' => 'suspended'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Expiring Subscriptions" :value="$platformStats['expiringSoon']" sub="Renewing within 30 days"
      icon="tabler-calendar-due" color="warning" :href="route('super-admin.hospitals.index', ['expiring' => 1])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Monthly Revenue" :value="config('hospital.currency', 'LKR') . ' ' . number_format($platformStats['monthlyRevenue'], 2)"
      sub="Current MRR from active tenants" icon="tabler-currency-dollar" color="success" :href="route('super-admin.revenue')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Platform Users" :value="$platformStats['platformUsers']" sub="Across every role"
      icon="tabler-users" color="primary" :href="route('users.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="System Health" value="View Status" sub="{{ $systemHealth['db_size'] }} &middot; v{{ $systemHealth['laravel_version'] }}"
      icon="tabler-heartbeat" color="primary" modal-target="#systemHealthModal" />
  </div>
</div>

{{-- Quick Actions --}}
<div class="card mb-6">
  <div class="card-header border-bottom">
    <h5 class="mb-0">Quick Actions</h5>
  </div>
  <div class="card-body">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-3">
      <div class="col">
        <a href="{{ route('super-admin.hospitals.index', ['new' => 1]) }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-plus icon-22px"></i></span>
          <small class="fw-medium">Add Hospital</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('subscription.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-crown icon-22px"></i></span>
          <small class="fw-medium">Subscriptions</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.revenue') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-report-money icon-22px"></i></span>
          <small class="fw-medium">View Revenue</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('users.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-users-group icon-22px"></i></span>
          <small class="fw-medium">Manage Users</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.analytics.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-chart-infographic icon-22px"></i></span>
          <small class="fw-medium">Platform Analytics</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.health.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-heartbeat icon-22px"></i></span>
          <small class="fw-medium">System Health</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.support.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-headset icon-22px"></i></span>
          <small class="fw-medium">Support Centre</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.reports.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-report icon-22px"></i></span>
          <small class="fw-medium">Reports & Export Centre</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.settings.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-settings-cog icon-22px"></i></span>
          <small class="fw-medium">Global Settings</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.audit.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-shield-check icon-22px"></i></span>
          <small class="fw-medium">Audit & Activity Centre</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.maintenance.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-server-cog icon-22px"></i></span>
          <small class="fw-medium">Platform Maintenance</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.notifications.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-bell-ringing icon-22px"></i></span>
          <small class="fw-medium">Notification Centre</small>
        </a>
      </div>
      <div class="col">
        <a href="{{ route('super-admin.features.index') }}" class="quick-action-btn">
          <span class="quick-action-icon"><i class="icon-base ti tabler-puzzle icon-22px"></i></span>
          <small class="fw-medium">Feature Management</small>
        </a>
      </div>
    </div>
  </div>
</div>

{{-- Growth chart + Notifications --}}
<div class="row g-4 mb-6">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header border-bottom">
        <h5 class="mb-0">Hospital Growth</h5>
        <small class="text-body-secondary">New tenant registrations, last 6 months</small>
      </div>
      <div class="card-body">
        <div id="hospitalGrowthChart"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="icon-base ti tabler-bell me-1"></i> Notifications</h5>
        @if(count($notifications) > 0)
        <button type="button" class="btn btn-sm btn-label-primary" data-bs-toggle="modal" data-bs-target="#notificationsModal">
          View All ({{ count($notifications) }})
        </button>
        @endif
      </div>
      <div class="card-body">
        @forelse(array_slice($notifications, 0, 5) as $n)
        <a href="{{ $n['url'] }}" class="platform-notification-item">
          <span class="platform-notification-icon bg-label-{{ $n['level'] }}"><i class="icon-base ti {{ $n['icon'] }}"></i></span>
          <span class="small">{{ $n['message'] }}</span>
        </a>
        @empty
        <div class="text-center py-6 text-body-secondary">
          <i class="icon-base ti tabler-bell-off icon-32px mb-2 d-block"></i>
          <small>No notifications right now — all clear.</small>
        </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- Recent Platform Activity --}}
<div class="row g-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="mb-0"><i class="icon-base ti tabler-history me-1"></i> Recent Platform Activity</h5>
      </div>
      <div class="p-0">
        @forelse($recentActivity as $log)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="avatar-initial rounded-circle bg-label-primary" style="width:32px;height:32px;font-size:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="icon-base ti {{ $log->module === 'subscription' ? 'tabler-crown' : 'tabler-building-hospital' }}" style="font-size:0.9rem;"></i>
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium small">{{ $log->description }}</p>
            <small class="text-muted">{{ $log->user_name }} &middot; {{ $log->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No platform activity recorded yet</small></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- System Health Modal --}}
<div class="modal fade" id="systemHealthModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="icon-base ti tabler-heartbeat me-2"></i>System Health</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ti tabler-database me-2 text-body-secondary"></i>Database Size</span>
            <strong>{{ $systemHealth['db_size'] }}</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ti tabler-users me-2 text-body-secondary"></i>Total Users</span>
            <strong>{{ $systemHealth['total_users'] }}</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ti tabler-brand-php me-2 text-body-secondary"></i>PHP Version</span>
            <strong>{{ $systemHealth['php_version'] }}</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ti tabler-brand-laravel me-2 text-body-secondary"></i>Laravel Version</span>
            <strong>{{ $systemHealth['laravel_version'] }}</strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><i class="icon-base ti tabler-clock me-2 text-body-secondary"></i>Server Time</span>
            <strong>{{ $systemHealth['server_time'] }}</strong>
          </li>
        </ul>
      </div>
      <div class="modal-footer">
        <a href="{{ route('super-admin.health.index') }}" class="btn btn-primary">View Full Report</a>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Notifications "View All" Modal --}}
<div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="icon-base ti tabler-bell me-2"></i>All Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @forelse($notifications as $n)
        <a href="{{ $n['url'] }}" class="platform-notification-item">
          <span class="platform-notification-icon bg-label-{{ $n['level'] }}"><i class="icon-base ti {{ $n['icon'] }}"></i></span>
          <span class="small">{{ $n['message'] }}</span>
        </a>
        @empty
        <p class="text-body-secondary text-center py-4 mb-0">No notifications right now.</p>
        @endforelse
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const growthData = @json($hospitalGrowthTrend);

  if (growthData.length) {
    new ApexCharts(document.querySelector('#hospitalGrowthChart'), {
      chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
      series: [{ name: 'New Hospitals', data: growthData.map(d => d.count) }],
      xaxis: { categories: growthData.map(d => d.month) },
      colors: ['#696cff'],
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
      stroke: { curve: 'smooth', width: 2 },
      dataLabels: { enabled: false },
      grid: { borderColor: 'rgba(0,0,0,0.05)', strokeDashArray: 4 },
      tooltip: { theme: 'light' },
    }).render();
  }
});
</script>
@endsection
