@php
$configData = Helper::appClasses();
$currency = config('hospital.currency', 'LKR');
$primaryCategory = $report['categories'][0];
@endphp

@extends('layouts/layoutMaster')

@section('title', $report['label'] . ' - Reports - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/apex-charts/apexcharts.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('page-style')
<style>
.not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
.stat-mini { padding: 1rem; border: 1px solid var(--bs-border-color); border-radius: 10px; height: 100%; }
.stat-mini .stat-mini-value { font-size: 1.25rem; font-weight: 600; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Platform Dashboard', 'url' => route('dashboard')],
  ['label' => 'Reports & Export Centre', 'url' => route('super-admin.reports.index')],
  ['label' => $report['label']],
]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">{{ $report['label'] }}</h4>
    <p class="mb-0 text-body-secondary">{{ $report['description'] }}</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('super-admin.reports.export-csv', ['slug' => $slug] + $filters) }}" class="btn btn-label-secondary" id="exportCsvBtn">
      <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export CSV
    </a>
    <a href="{{ route('super-admin.reports.print', ['slug' => $slug] + $filters) }}" target="_blank" class="btn btn-label-secondary" id="exportPdfBtn">
      <i class="icon-base ti tabler-file-type-pdf me-1"></i> Export PDF
    </a>
    <a href="{{ route('super-admin.reports.print', ['slug' => $slug] + $filters) }}" target="_blank" class="btn btn-primary" id="printBtn">
      <i class="icon-base ti tabler-printer me-1"></i> Print
    </a>
  </div>
</div>

@if(!empty($report['filters']))
<div class="card mb-6">
  <div class="card-body py-4">
    <form id="filterForm" method="GET" action="{{ route('super-admin.reports.show', $slug) }}">
      <div class="row g-3 align-items-center">
        @if(in_array('search', $report['filters']))
        <div class="col-md-3">
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
            <input type="text" class="form-control" name="search" id="filterSearch" placeholder="Search..." value="{{ $filters['search'] ?? '' }}" />
          </div>
        </div>
        @endif
        @if(in_array('hospital', $report['filters']))
        <div class="col-md-3">
          <select class="form-select" name="hospital_id" id="filterHospital">
            <option value="">All Hospitals</option>
            @foreach($hospitals as $h)
            <option value="{{ $h->id }}" @selected(($filters['hospital_id'] ?? null) == $h->id)>{{ $h->name }}</option>
            @endforeach
          </select>
        </div>
        @endif
        @if(in_array('status', $report['filters']))
        <div class="col-md-2">
          <select class="form-select" name="status" id="filterStatus">
            <option value="">All Status</option>
            @foreach(['open','active','trial','suspended','cancelled','inactive','resolved','closed','in_progress','waiting_customer'] as $s)
            <option value="{{ $s }}" @selected(($filters['status'] ?? null) === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
            @endforeach
          </select>
        </div>
        @endif
        @if(in_array('plan', $report['filters']))
        <div class="col-md-2">
          <select class="form-select" name="plan" id="filterPlan">
            <option value="">All Plans</option>
            @foreach($plans as $p)
            <option value="{{ $p->id }}" @selected(($filters['plan'] ?? null) == $p->id)>{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        @endif
        @if(in_array('country', $report['filters']))
        <div class="col-md-2">
          <select class="form-select" name="country" id="filterCountry">
            <option value="">All Countries</option>
            @foreach($countries as $c)
            <option value="{{ $c }}" @selected(($filters['country'] ?? null) === $c)>{{ $c }}</option>
            @endforeach
          </select>
        </div>
        @endif
        @if(in_array('date', $report['filters']))
        <div class="col-md-2">
          <input type="date" class="form-control" name="from_date" id="filterFrom" title="From date" value="{{ $filters['from_date'] ?? '' }}" />
        </div>
        <div class="col-md-2">
          <input type="date" class="form-control" name="to_date" id="filterTo" title="To date" value="{{ $filters['to_date'] ?? '' }}" />
        </div>
        @endif
        <div class="col-md-auto">
          <button type="submit" class="btn btn-primary btn-sm" id="applyFiltersBtn"><i class="icon-base ti tabler-filter me-1"></i> Apply</button>
          <a href="{{ route('super-admin.reports.show', $slug) }}" class="btn btn-label-secondary btn-sm">Reset</a>
        </div>
      </div>
    </form>
  </div>
</div>
@endif

@if($report['type'] === 'summary')

@if($summary['kpis'])
<div class="row g-4 mb-6 row-cols-1 row-cols-sm-2 row-cols-xl-{{ min(count($summary['kpis']), 4) }}">
  @foreach($summary['kpis'] as $kpi)
  <div class="col">
    <div class="stat-mini text-center">
      <span class="avatar-initial rounded bg-label-{{ $kpi['color'] }} mb-2 d-inline-flex" style="width:38px;height:38px;align-items:center;justify-content:center;"><i class="icon-base ti {{ $kpi['icon'] }}"></i></span>
      <div class="stat-mini-value">{{ $kpi['value'] }}</div>
      <small class="text-body-secondary">{{ $kpi['label'] }}</small>
    </div>
  </div>
  @endforeach
</div>
@endif

@if($summary['chart'])
<div class="card mb-6">
  <div class="card-body"><div id="reportChart"></div></div>
</div>
@endif

@if($summary['table'])
<div class="card">
  <div class="card-header border-bottom"><h6 class="mb-0">Breakdown</h6></div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr>@foreach($summary['table']['columns'] as $c)<th>{{ $c['title'] }}</th>@endforeach</tr></thead>
      <tbody>
        @forelse($summary['table']['rows'] as $row)
        <tr>@foreach($summary['table']['columns'] as $c)<td>{{ $row[$c['data']] }}</td>@endforeach</tr>
        @empty
        <tr><td colspan="{{ count($summary['table']['columns']) }}" class="text-center not-tracked py-4">Not tracked yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

@if(!$summary['kpis'] && !$summary['chart'] && !$summary['table'])
<div class="card"><div class="card-body text-center py-8"><p class="not-tracked mb-0">Not tracked yet.</p></div></div>
@endif

@else

{{-- Table report --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Results</h5>
    <small class="text-body-secondary">Scroll horizontally to see every column</small>
  </div>
  <div class="table-responsive">
    <table class="table" id="reportTable" style="width:100%">
      <thead>
        <tr>@foreach($columns as $c)<th>{{ $c['title'] }}</th>@endforeach</tr>
      </thead>
    </table>
  </div>
</div>

@endif

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if($report['type'] === 'summary' && $summary['chart'])
  const chartData = @json($summary['chart']);
  new ApexCharts(document.querySelector('#reportChart'), {
    chart: { type: chartData.type, height: 340, toolbar: { show: false } },
    series: chartData.type === 'pie' ? chartData.series : chartData.series,
    labels: chartData.type === 'pie' ? chartData.categories : undefined,
    xaxis: chartData.type !== 'pie' ? { categories: chartData.categories } : undefined,
    colors: ['#696cff', '#71dd37', '#03c3ec', '#ffab00', '#ff3e1d'],
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    legend: { position: chartData.type === 'pie' ? 'bottom' : 'top' },
    grid: { borderColor: 'rgba(0,0,0,0.05)', strokeDashArray: 4 },
  }).render();
  @endif

  @if($report['type'] === 'table')
  const columns = @json($columns);
  const orderableColumns = @json($orderableColumns);
  const isOrderable = (key) => orderableColumns.length === 0 || orderableColumns.includes(key);

  const dtColumns = columns.map(c => ({ data: c.data, name: c.data, orderable: isOrderable(c.data) }));

  const table = $('#reportTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    ajax: {
      url: '{{ route('super-admin.reports.data', $slug) }}',
      type: 'GET',
      data: function (d) {
        @foreach(($report['filters'] ?? []) as $f)
        @if($f === 'date')
        d.from_date = document.getElementById('filterFrom') ? document.getElementById('filterFrom').value : '';
        d.to_date = document.getElementById('filterTo') ? document.getElementById('filterTo').value : '';
        @elseif($f === 'hospital')
        d.hospital_id = document.getElementById('filterHospital') ? document.getElementById('filterHospital').value : '';
        @elseif($f === 'status')
        d.status = document.getElementById('filterStatus') ? document.getElementById('filterStatus').value : '';
        @elseif($f === 'plan')
        d.plan = document.getElementById('filterPlan') ? document.getElementById('filterPlan').value : '';
        @elseif($f === 'country')
        d.country = document.getElementById('filterCountry') ? document.getElementById('filterCountry').value : '';
        @elseif($f === 'search')
        d.search = document.getElementById('filterSearch') ? document.getElementById('filterSearch').value : '';
        @endif
        @endforeach
      }
    },
    columns: dtColumns,
    pageLength: 25,
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>',
      emptyTable: 'Not tracked yet.',
      zeroRecords: 'Not tracked yet.',
    },
  });

  document.querySelectorAll('#filterHospital, #filterStatus, #filterPlan, #filterCountry, #filterFrom, #filterTo').forEach(el => {
    el && el.addEventListener('change', () => table.draw());
  });
  const searchInput = document.getElementById('filterSearch');
  if (searchInput) {
    let debounce;
    searchInput.addEventListener('input', function () {
      clearTimeout(debounce);
      debounce = setTimeout(() => table.draw(), 300);
    });
  }
  const applyBtn = document.getElementById('applyFiltersBtn');
  if (applyBtn) {
    applyBtn.addEventListener('click', function (e) {
      e.preventDefault();
      table.draw();
    });
  }
  @endif
});
</script>
@endsection
