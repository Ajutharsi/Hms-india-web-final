@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Reports & Analytics - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-6">
  <div class="d-flex align-items-center gap-3">
    <div class="avatar">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-chart-bar icon-26px"></i>
      </span>
    </div>
    <div>
      <h4 class="mb-0">Reports & Analytics</h4>
      <small class="text-muted">HMS Dashboard KPIs & Reports — {{ now()->format('d M Y') }}</small>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('reports.patients') }}" class="btn btn-label-primary btn-sm">
      <i class="icon-base ti tabler-users me-1"></i> Patient Report
    </a>
    <a href="{{ route('reports.billing') }}" class="btn btn-label-success btn-sm">
      <i class="icon-base ti tabler-file-invoice me-1"></i> Billing Report
    </a>
    <a href="{{ route('reports.inventory') }}" class="btn btn-label-warning btn-sm">
      <i class="icon-base ti tabler-pill me-1"></i> Inventory Report
    </a>
  </div>
</div>

{{-- KPI Stats --}}
<div class="row g-4 mb-6" id="kpiStats">
  <div class="col-12 text-center py-5">
    <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;"></div>
    <p class="text-muted mt-3 mb-0">Loading KPIs...</p>
  </div>
</div>

{{-- Charts Row 1 --}}
<div class="row g-4 mb-6">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">
          <i class="icon-base ti tabler-users me-2 text-primary"></i>Patient Registrations
        </h5>
        <small class="text-muted">Last 6 months</small>
      </div>
      <div class="card-body">
        <div id="monthlyPatientsChart"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">
          <i class="icon-base ti tabler-chart-donut me-2 text-info"></i>Patient Type
        </h5>
        <small class="text-muted">OP vs IP</small>
      </div>
      <div class="card-body">
        <div id="patientTypeChart"></div>
      </div>
    </div>
  </div>
</div>

{{-- Charts Row 2 --}}
<div class="row g-4 mb-6">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">
          <i class="icon-base ti tabler-currency-dollar me-2 text-success"></i>Revenue Trend
        </h5>
        <small class="text-muted">Last 6 months ({{ $currency }})</small>
      </div>
      <div class="card-body">
        <div id="monthlyRevenueChart"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">
          <i class="icon-base ti tabler-calendar me-2 text-warning"></i>Appointments
        </h5>
        <small class="text-muted">By Status</small>
      </div>
      <div class="card-body">
        <div id="appointmentStatusChart"></div>
      </div>
    </div>
  </div>
</div>

{{-- Quick Report Links --}}
<p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Detailed Reports</p>
<div class="row g-4">
  @foreach([
    ['Patient Report',   'View and export patient registration data',  'reports.patients',  'reports.patients.pdf',  'tabler-users',       'primary'],
    ['Billing Report',   'View and export billing & payment data',     'reports.billing',   'reports.billing.pdf',   'tabler-file-invoice','success'],
    ['Inventory Report', 'View drug stock levels and expiry alerts',   'reports.inventory', 'reports.inventory.pdf', 'tabler-pill',        'warning'],
  ] as [$title, $desc, $viewRoute, $pdfRoute, $icon, $color])
  <div class="col-md-4">
    <div class="card">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-{{ $color }}">
              <i class="icon-base ti {{ $icon }} icon-26px"></i>
            </span>
          </div>
          <div>
            <h6 class="mb-0">{{ $title }}</h6>
            <small class="text-muted">{{ $desc }}</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route($viewRoute) }}" class="btn btn-{{ $color }} btn-sm flex-grow-1">
            <i class="icon-base ti tabler-eye me-1"></i> View
          </a>
          <a href="{{ route($pdfRoute) }}" class="btn btn-label-danger btn-sm">
            <i class="icon-base ti tabler-file-type-pdf me-1"></i> PDF
          </a>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection

@section('page-script')
<script>
const currency = '{{ $currency }}';

document.addEventListener('DOMContentLoaded', function () {

  fetch('{{ route('reports.kpis') }}')
    .then(res => res.json())
    .then(data => {
      renderKpis(data);
      renderMonthlyPatientsChart(data.monthly_patients);
      renderMonthlyRevenueChart(data.monthly_revenue);
      renderPatientTypeChart(data.patient_type_chart);
      renderAppointmentStatusChart(data.appointment_status_chart);
    })
    .catch(err => {
      document.getElementById('kpiStats').innerHTML = '<div class="col-12 text-center text-muted py-3">Failed to load KPIs</div>';
    });

  function renderKpis(data) {
    const kpis = [
      { label: 'Total Patients',    value: data.total_patients,                                                    icon: 'tabler-users',          color: 'primary' },
      { label: 'Today Appointments',value: data.today_appointments,                                                icon: 'tabler-calendar',       color: 'info'    },
      { label: 'Total Revenue',     value: currency + ' ' + parseFloat(data.total_revenue || 0).toLocaleString(),  icon: 'tabler-currency-dollar', color: 'success' },
      { label: 'Outstanding',       value: currency + ' ' + parseFloat(data.outstanding || 0).toLocaleString(),    icon: 'tabler-alert-circle',   color: 'warning' },
      { label: 'Lab Orders',        value: data.total_lab_orders,                                                  icon: 'tabler-flask',          color: 'primary' },
      { label: 'Pending Labs',      value: data.pending_lab_orders,                                                icon: 'tabler-clock',          color: 'warning' },
      { label: 'Low Stock Drugs',   value: data.low_stock_drugs,                                                   icon: 'tabler-alert-triangle', color: 'danger'  },
      { label: 'Near Expiry Drugs', value: data.near_expiry_drugs,                                                 icon: 'tabler-calendar-x',    color: 'warning' },
    ];

    const html = kpis.map(k => `
      <div class="col-sm-6 col-xl-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-between">
              <div class="content-left">
                <span class="text-heading" style="font-size:0.82rem;">${k.label}</span>
                <h4 class="mb-0 mt-1">${k.value ?? 0}</h4>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-${k.color}">
                  <i class="icon-base ti ${k.icon} icon-26px"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    `).join('');

    document.getElementById('kpiStats').innerHTML = html;
  }

  function renderMonthlyPatientsChart(data) {
    if (!data || !data.length) return;
    new ApexCharts(document.querySelector('#monthlyPatientsChart'), {
      chart: { type: 'area', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
      series: [{ name: 'Patients', data: data.map(d => d.count) }],
      xaxis: { categories: data.map(d => d.month) },
      colors: ['#696cff'],
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
      stroke: { curve: 'smooth', width: 2 },
      dataLabels: { enabled: false },
      grid: { borderColor: 'rgba(0,0,0,0.05)', strokeDashArray: 4 },
      tooltip: { theme: 'light' },
    }).render();
  }

  function renderMonthlyRevenueChart(data) {
    if (!data || !data.length) return;
    new ApexCharts(document.querySelector('#monthlyRevenueChart'), {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      series: [{ name: 'Revenue (' + currency + ')', data: data.map(d => d.amount) }],
      xaxis: { categories: data.map(d => d.month) },
      colors: ['#71dd37'],
      dataLabels: { enabled: false },
      plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
      grid: { borderColor: 'rgba(0,0,0,0.05)', strokeDashArray: 4 },
      tooltip: { theme: 'light' },
    }).render();
  }

  function renderPatientTypeChart(data) {
    if (!data) return;
    new ApexCharts(document.querySelector('#patientTypeChart'), {
      chart: { type: 'donut', height: 280 },
      series: [data.op || 0, data.ip || 0],
      labels: ['Out Patient (OP)', 'In Patient (IP)'],
      colors: ['#696cff', '#03c3ec'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: true },
      plotOptions: { pie: { donut: { size: '65%' } } },
    }).render();
  }

  function renderAppointmentStatusChart(data) {
    if (!data) return;
    new ApexCharts(document.querySelector('#appointmentStatusChart'), {
      chart: { type: 'donut', height: 280 },
      series: [data.scheduled || 0, data.completed || 0, data.cancelled || 0, data.no_show || 0],
      labels: ['Scheduled', 'Completed', 'Cancelled', 'No Show'],
      colors: ['#696cff', '#71dd37', '#ff3e1d', '#ffab00'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: true },
      plotOptions: { pie: { donut: { size: '65%' } } },
    }).render();
  }

});
</script>
@endsection