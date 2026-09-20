@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Reports & Export Centre - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-style')
<style>
.report-category-card { display: block; padding: 1.25rem; border: 1px solid var(--bs-border-color); border-radius: 12px; height: 100%; text-decoration: none; color: inherit; transition: transform 0.15s ease, box-shadow 0.15s ease; }
.report-category-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); color: inherit; }
.report-category-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(105,108,255,0.08); color: var(--bs-primary); margin-bottom: 0.75rem; }
.not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Reports & Export Centre']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Reports & Export Centre</h4>
    <p class="mb-0 text-body-secondary">The central reporting hub for the platform owner — every report backed by real data.</p>
  </div>
</div>

{{-- Dashboard KPIs --}}
<div class="row g-4 mb-6 row-cols-1 row-cols-sm-2 row-cols-xl-4">
  <div class="col">
    <x-kpi-card label="Total Reports" :value="$dashboard['totalReports']" sub="Across 8 categories" icon="tabler-report" color="primary" />
  </div>
  <div class="col">
    @if($dashboard['scheduledReports'] === null)
    <div class="card kpi-card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Scheduled Reports</span>
            <div class="d-flex align-items-center my-1"><span class="not-tracked">Not tracked yet</span></div>
            <small class="mb-0">No scheduling infrastructure exists</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-secondary"><i class="icon-base ti tabler-calendar-time icon-26px"></i></span></div>
        </div>
      </div>
    </div>
    @else
    <x-kpi-card label="Scheduled Reports" :value="$dashboard['scheduledReports']" icon="tabler-calendar-time" color="info" />
    @endif
  </div>
  <div class="col">
    <x-kpi-card label="Total Exports" :value="$dashboard['totalExports']" sub="CSV + PDF + Print, all time" icon="tabler-download" color="success" />
  </div>
  <div class="col">
    <x-kpi-card label="Recent Exports" :value="$dashboard['recentExports']->count()" sub="Most recent generations" icon="tabler-history" color="warning" />
  </div>
</div>

<div class="row g-4 mb-6">
  {{-- Export Activity --}}
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-chart-bar me-1"></i> Export Activity — last 7 days</h6></div>
      <div class="card-body">
        @if(collect($dashboard['exportActivity'])->sum('count') > 0)
        <div id="exportActivityChart"></div>
        @else
        <p class="not-tracked mb-0 text-center py-6">No exports generated yet.</p>
        @endif
      </div>
    </div>
  </div>

  {{-- Most Generated Reports --}}
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-trophy me-1"></i> Most Generated Reports</h6></div>
      <div class="p-0">
        @forelse($dashboard['mostGenerated'] as $m)
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
          <span class="fw-medium small">{{ $m->report_label }}</span>
          <span class="badge bg-label-primary">{{ $m->generated_count }}</span>
        </div>
        @empty
        <div class="text-center py-6"><small class="not-tracked">No reports generated yet</small></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- Recent Exports --}}
<div class="card mb-6">
  <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-clock-edit me-1"></i> Recent Exports</h6></div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Report</th><th>Category</th><th>Format</th><th>Rows</th><th>By</th><th>When</th></tr></thead>
      <tbody>
        @forelse($dashboard['recentExports'] as $e)
        <tr>
          <td>{{ $e->report_label }}</td>
          <td class="text-capitalize">{{ $e->category }}</td>
          <td><span class="badge bg-label-secondary text-uppercase">{{ $e->format }}</span></td>
          <td>{{ $e->row_count ?? '—' }}</td>
          <td>{{ $e->generatedBy?->name ?? 'System' }}</td>
          <td>{{ $e->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center not-tracked py-4">No exports generated yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Report Categories --}}
<h5 class="mb-3">Report Categories</h5>
<div class="row g-4">
  @foreach($categories as $key => $meta)
  <div class="col-md-6 col-xl-3">
    <div class="report-category-card">
      <div class="report-category-icon"><i class="icon-base ti {{ $meta['icon'] }} icon-22px"></i></div>
      <h6 class="mb-1">{{ $meta['label'] }}</h6>
      <p class="text-body-secondary small mb-3">{{ count(\App\Services\Reports\ReportCatalog::byCategory($key)) }} report(s) available</p>
      <div class="d-flex flex-column gap-1">
        @foreach(\App\Services\Reports\ReportCatalog::byCategory($key) as $slug => $r)
        <a href="{{ route('super-admin.reports.show', $slug) }}" class="small text-reset text-decoration-none d-flex align-items-center gap-1">
          <i class="icon-base ti tabler-chevron-right" style="font-size:0.7rem;"></i> {{ $r['label'] }}
        </a>
        @endforeach
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const activity = @json($dashboard['exportActivity']);
  const el = document.querySelector('#exportActivityChart');
  if (el && activity.some(d => d.count > 0)) {
    new ApexCharts(el, {
      chart: { type: 'bar', height: 300, toolbar: { show: false } },
      series: [{ name: 'Exports', data: activity.map(d => d.count) }],
      xaxis: { categories: activity.map(d => d.day) },
      colors: ['#696cff'],
      dataLabels: { enabled: false },
      plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
      grid: { borderColor: 'rgba(0,0,0,0.05)', strokeDashArray: 4 },
    }).render();
  }
});
</script>
@endsection
