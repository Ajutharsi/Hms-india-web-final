@php
$configData = Helper::appClasses();
$currency = config('hospital.currency', 'LKR');
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Platform Analytics - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-style')
<style>
.analytics-section-title { font-size: 0.9375rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.stat-mini { padding: 1rem; border: 1px solid var(--bs-border-color); border-radius: 10px; height: 100%; }
.stat-mini .stat-mini-value { font-size: 1.35rem; font-weight: 600; }
.not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Platform Analytics']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Platform Analytics</h4>
    <p class="mb-0 text-body-secondary">Growth, revenue, and usage across every tenant on the platform.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('super-admin.analytics.export-csv') }}" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export CSV
    </a>
    <a href="{{ route('super-admin.analytics.pdf') }}" target="_blank" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-printer me-1"></i> Export PDF
    </a>
    <a href="{{ route('super-admin.health.index') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-heartbeat me-1"></i> System Health
    </a>
  </div>
</div>

{{-- Platform Overview --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-building-hospital"></i> Platform Overview</div>
<div class="row g-4 mb-6 row-cols-1 row-cols-sm-2 row-cols-xl-5">
  <div class="col">
    <x-kpi-card label="Total Hospitals" :value="$overview['total']" icon="tabler-building-hospital" color="primary" :href="route('super-admin.hospitals.index')" />
  </div>
  <div class="col">
    <x-kpi-card label="Active" :value="$overview['active']" icon="tabler-circle-check" color="success" :href="route('super-admin.hospitals.index', ['status' => 'active'])" />
  </div>
  <div class="col">
    <x-kpi-card label="Trial" :value="$overview['trial']" icon="tabler-clock-hour-4" color="warning" :href="route('super-admin.hospitals.index', ['status' => 'trial'])" />
  </div>
  <div class="col">
    <x-kpi-card label="Suspended" :value="$overview['suspended']" icon="tabler-ban" color="danger" :href="route('super-admin.hospitals.index', ['status' => 'suspended'])" />
  </div>
  <div class="col">
    <x-kpi-card label="Archived" :value="$overview['archived']" icon="tabler-archive" color="secondary" :href="route('super-admin.hospitals.index', ['status' => 'archived'])" />
  </div>
</div>

{{-- Growth Analytics --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-trending-up"></i> Growth Analytics <small class="text-body-secondary fw-normal">— last 6 months</small></div>
<div class="row g-4 mb-6">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body"><div id="growthChart"></div></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0">Hospital Status Distribution</h6></div>
      <div class="card-body"><div id="statusDonutChart"></div></div>
    </div>
  </div>
</div>

{{-- Revenue Analytics --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-currency-dollar"></i> Revenue Analytics</div>
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Monthly Revenue" :value="$currency . ' ' . number_format($revenue['monthlyRevenue'], 2)" sub="Active subscriptions" icon="tabler-report-money" color="success" :href="route('super-admin.revenue')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Annual Revenue" :value="$currency . ' ' . number_format($revenue['annualRevenue'], 2)" sub="Billing-cycle aware projection" icon="tabler-calendar-dollar" color="success" :href="route('super-admin.revenue')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Active Subscription Value" :value="$currency . ' ' . number_format($revenue['activeSubscriptionValue'], 2)" icon="tabler-circle-check" color="primary" :href="route('subscription.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Expired Subscription Value" :value="$currency . ' ' . number_format($revenue['expiredSubscriptionValue'], 2)" icon="tabler-calendar-x" color="warning" :href="route('subscription.index')" />
  </div>
</div>

{{-- Subscription Analytics --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-crown"></i> Subscription Analytics</div>
<div class="row g-4 mb-6">
  @foreach([
    ['Active', $subscriptions['active'], 'success', 'tabler-circle-check'],
    ['Trial', $subscriptions['trial'], 'info', 'tabler-clock-hour-4'],
    ['Expiring Soon', $subscriptions['expiringSoon'], 'warning', 'tabler-calendar-due'],
    ['Expired', $subscriptions['expired'], 'danger', 'tabler-calendar-x'],
    ['Cancelled', $subscriptions['cancelled'], 'secondary', 'tabler-x'],
    ['Archived', $subscriptions['archived'], 'secondary', 'tabler-archive'],
  ] as [$label, $value, $color, $icon])
  <div class="col-sm-4 col-xl-2">
    <div class="stat-mini text-center">
      <span class="avatar-initial rounded bg-label-{{ $color }} mb-2 d-inline-flex" style="width:38px;height:38px;align-items:center;justify-content:center;"><i class="icon-base ti {{ $icon }}"></i></span>
      <div class="stat-mini-value">{{ $value }}</div>
      <small class="text-body-secondary">{{ $label }}</small>
    </div>
  </div>
  @endforeach
</div>

{{-- Plan Analytics --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-package"></i> Plan Analytics</div>
<div class="row g-4 mb-6">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0">Distribution by Plan</h6></div>
      <div class="card-body">
        @if($plans['distribution']->isEmpty())
        <p class="not-tracked mb-0">No active subscriptions on any plan yet.</p>
        @else
        <div id="planDistributionChart"></div>
        @endif
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Plan Revenue Breakdown</h6>
        @if($plans['mostPopular'])
        <span class="badge bg-label-primary">Most Popular: {{ $plans['mostPopular']->name }}</span>
        @endif
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Plan</th><th class="text-end">Subscriptions</th><th class="text-end">Revenue</th></tr></thead>
          <tbody>
            @forelse($plans['distribution'] as $row)
            <tr>
              <td>{{ $row->name }}</td>
              <td class="text-end">{{ $row->subscriptions }}</td>
              <td class="text-end">{{ $currency }} {{ number_format($row->revenue, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center not-tracked py-4">No active subscriptions on any plan yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Usage Analytics --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-users"></i> Usage Analytics</div>
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Total Platform Users" :value="$usage['totalUsers']" icon="tabler-users" color="primary" :href="route('users.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Doctors" :value="$usage['doctors']" icon="tabler-stethoscope" color="info" :href="route('doctors.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Staff" :value="$usage['staff']" icon="tabler-users-group" color="warning" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Patients" :value="$usage['patients']" icon="tabler-user" color="success" :href="route('patients.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-mini">
      <small class="text-body-secondary d-block mb-1">Average Users per Hospital</small>
      @if($usage['avgUsersPerHospital'] !== null)
      <span class="stat-mini-value">{{ $usage['avgUsersPerHospital'] }}</span>
      @else
      <span class="not-tracked">Not tracked yet — requires per-hospital user scoping</span>
      @endif
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-mini">
      <small class="text-body-secondary d-block mb-1">Storage Usage</small>
      @if($usage['storageUsedMb'] !== null)
      <span class="stat-mini-value">{{ number_format($usage['storageUsedMb'], 1) }} MB</span>
      @else
      <span class="not-tracked">Not tracked yet</span>
      @endif
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-mini">
      <small class="text-body-secondary d-block mb-1">Average Storage Usage</small>
      @if($usage['avgStorageUsedMb'] !== null)
      <span class="stat-mini-value">{{ number_format($usage['avgStorageUsedMb'], 1) }} MB</span>
      @else
      <span class="not-tracked">Not tracked yet — no per-hospital storage attribution</span>
      @endif
    </div>
  </div>
</div>

{{-- Registration Analytics --}}
<div class="analytics-section-title"><i class="icon-base ti tabler-user-plus"></i> Registration Analytics</div>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="row g-4">
      <div class="col-12">
        <x-kpi-card label="New Hospitals" :value="$registrations['newHospitals30d']" sub="Last 30 days" icon="tabler-building-hospital" color="primary" :href="route('super-admin.hospitals.index')" />
      </div>
      <div class="col-12">
        <x-kpi-card label="New Users" :value="$registrations['newUsers30d']" sub="Last 30 days" icon="tabler-user-plus" color="success" :href="route('users.index')" />
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0">Recent Registrations</h6></div>
      <div class="p-0">
        @forelse($registrations['recentHospitals'] as $h)
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;"><i class="icon-base ti tabler-building-hospital" style="font-size:0.9rem;"></i></span>
            <div>
              <p class="mb-0 fw-medium small">{{ $h->name }}</p>
              <small class="text-muted">{{ ucfirst($h->status) }} &middot; {{ $h->created_at->diffForHumans() }}</small>
            </div>
          </div>
          <a href="{{ route('super-admin.hospitals.show', $h->id) }}" class="btn btn-sm btn-label-secondary">View</a>
        </div>
        @empty
        <div class="text-center py-6"><small class="not-tracked">No hospitals registered yet</small></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const growth = @json($growth);
  const overview = @json($overview);
  const planDist = @json($plans['distribution']);

  new ApexCharts(document.querySelector('#growthChart'), {
    chart: { type: 'line', height: 340, toolbar: { show: false } },
    series: [
      { name: 'Hospitals', data: growth.hospitals.map(d => d.count) },
      { name: 'Users', data: growth.users.map(d => d.count) },
      { name: 'Doctors', data: growth.doctors.map(d => d.count) },
      { name: 'Patients', data: growth.patients.map(d => d.count) },
      { name: 'Subscriptions', data: growth.subscriptions.map(d => d.count) },
    ],
    xaxis: { categories: growth.hospitals.map(d => d.month) },
    colors: ['#696cff', '#71dd37', '#03c3ec', '#ffab00', '#ff3e1d'],
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
    grid: { borderColor: 'rgba(0,0,0,0.05)', strokeDashArray: 4 },
  }).render();

  new ApexCharts(document.querySelector('#statusDonutChart'), {
    chart: { type: 'donut', height: 300 },
    series: [overview.active, overview.trial, overview.suspended, overview.archived],
    labels: ['Active', 'Trial', 'Suspended', 'Archived'],
    colors: ['#71dd37', '#ffab00', '#ff3e1d', '#8592a3'],
    legend: { position: 'bottom' },
  }).render();

  if (planDist.length) {
    new ApexCharts(document.querySelector('#planDistributionChart'), {
      chart: { type: 'pie', height: 280 },
      series: planDist.map(p => p.subscriptions),
      labels: planDist.map(p => p.name),
      legend: { position: 'bottom' },
    }).render();
  }
});
</script>
@endsection
