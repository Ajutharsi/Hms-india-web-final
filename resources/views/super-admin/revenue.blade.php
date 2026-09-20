@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'SaaS Revenue Report - HMS')

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Dashboard', 'url' => route('dashboard')],
  ['label' => 'Revenue Report'],
]" />

<div class="row g-4 mb-6">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Current MRR</span>
            <div class="d-flex align-items-center my-1"><h4 class="mb-0">{{ config('hospital.currency', 'LKR') }} {{ number_format($currentMrr, 2) }}</h4></div>
            <small class="mb-0">From active hospitals</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-currency-dollar icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Paying Hospitals</span>
            <div class="d-flex align-items-center my-1"><h4 class="mb-0">{{ $paying->count() }}</h4></div>
            <small class="mb-0">Active + suspended</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-building-hospital icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Avg. Revenue / Hospital</span>
            <div class="d-flex align-items-center my-1"><h4 class="mb-0">{{ config('hospital.currency', 'LKR') }} {{ $paying->count() ? number_format($currentMrr / max(1, \App\Models\Hospital::where('status','active')->count()), 2) : '0.00' }}</h4></div>
            <small class="mb-0">Active hospitals only</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-chart-bar icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-6">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-chart-line me-2 text-success"></i>MRR Trend — Last 6 Months</h6>
      </div>
      <div class="p-3"><canvas id="mrrChart" height="90"></canvas></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-chart-pie me-2 text-primary"></i>Revenue by Plan</h6>
      </div>
      <div class="p-0">
        @foreach(['basic' => 'primary', 'professional' => 'info', 'enterprise' => 'warning'] as $plan => $color)
        @php $row = $byPlan->get($plan) @endphp
        <a href="{{ route('super-admin.hospitals.index', ['plan' => $plan, 'status' => 'active']) }}" class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom text-decoration-none text-reset">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-{{ $color }} text-capitalize">{{ $plan }}</span>
            <small class="text-muted">{{ $row->hospitals ?? 0 }} hospitals</small>
          </div>
          <span class="fw-medium">{{ config('hospital.currency', 'LKR') }} {{ number_format($row->mrr ?? 0, 2) }}</span>
        </a>
        @endforeach
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h6 class="mb-0">Paying Hospitals</h6>
    <a href="{{ route('super-admin.hospitals.index') }}" class="text-primary small">View All Hospitals</a>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Hospital</th><th>Plan</th><th>Status</th><th>Monthly Fee</th><th>Renewal</th></tr></thead>
      <tbody>
        @forelse($paying->sortByDesc('monthly_fee')->take(15) as $h)
        <tr style="cursor:pointer;" onclick="window.location.href='{{ route('super-admin.hospitals.show', $h->id) }}'">
          <td><span class="fw-medium">{{ $h->name }}</span> <small class="text-muted d-block">{{ $h->code }}</small></td>
          <td><span class="badge bg-label-primary text-capitalize">{{ $h->plan }}</span></td>
          <td><span class="badge bg-label-{{ $h->status === 'active' ? 'success' : 'warning' }} text-capitalize">{{ $h->status }}</span></td>
          <td>{{ config('hospital.currency', 'LKR') }} {{ number_format($h->monthly_fee, 2) }}</td>
          <td>{{ optional($h->subscription_ends_at)->format('d M Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center py-4 text-muted">No paying hospitals yet</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection

@section('page-script')
<script>
const mrrLabels = @json($trend->pluck('month'));
const mrrAmounts = @json($trend->pluck('mrr'));

const mrrCtx = document.getElementById('mrrChart');
if (mrrCtx) {
  new Chart(mrrCtx, {
    type: 'line',
    data: {
      labels: mrrLabels,
      datasets: [{ label: 'MRR', data: mrrAmounts, backgroundColor: 'rgba(113,221,55,0.1)', borderColor: 'rgba(113,221,55,1)', borderWidth: 2, tension: 0.4, fill: true, pointBackgroundColor: 'rgba(113,221,55,1)' }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
}
</script>
@endsection
