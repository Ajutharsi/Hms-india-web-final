@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Vitals / Observations - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/apex-charts/apexcharts.js',
])
@endsection

@section('content')

@if($fromPatient)
<x-breadcrumb :items="[
  ['label' => 'Vitals', 'url' => route('vitals.index')],
  ['label' => $fromPatient->name],
]" />
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $todayCount }}</h4>
              <p class="mb-0">Today's Recordings</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-activity icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $criticalCount }}</h4>
              <p class="mb-0">Critical (SpO2 &lt; 94%)</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $highBpCount }}</h4>
              <p class="mb-0">High BP</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-arrow-up icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $lowBpCount }}</h4>
              <p class="mb-0">Low BP</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-arrow-down icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Vitals Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Vitals / Observations</h5>
    <div class="d-flex gap-2 align-items-center">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="admittedOnlyFilter">
        <label class="form-check-label small" for="admittedOnlyFilter">Admitted patients only</label>
      </div>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasVitalAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Record Vitals
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="vitalsTable">
      <thead>
        <tr>
          <th></th>
          <th>Patient</th>
          <th>BP</th>
          <th>Temp (°C)</th>
          <th>Pulse</th>
          <th>SpO2</th>
          <th>Weight (kg)</th>
          <th>NEWS Score</th>
          <th>Time</th>
          <th>Recorded By</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Vitals Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasVitalAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Record Vitals</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('vitals.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
            <option value="">Select Patient</option>
            @foreach ($patients as $p)
            <option value="{{ $p->id }}" @selected(old('patient_id')==$p->id)>{{ $p->name }} ({{ $p->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Blood Pressure</label>
            <input type="text" class="form-control @error('blood_pressure') is-invalid @enderror" name="blood_pressure" placeholder="120/80" value="{{ old('blood_pressure') }}" />
            @error('blood_pressure') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Temperature (°C)</label>
            <input type="number" step="0.1" class="form-control @error('temperature') is-invalid @enderror" name="temperature" placeholder="37.0" value="{{ old('temperature') }}" />
            @error('temperature') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Pulse Rate (bpm)</label>
            <input type="number" class="form-control @error('pulse_rate') is-invalid @enderror" name="pulse_rate" value="{{ old('pulse_rate') }}" />
            @error('pulse_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Respiratory Rate</label>
            <input type="number" class="form-control @error('respiratory_rate') is-invalid @enderror" name="respiratory_rate" value="{{ old('respiratory_rate') }}" />
            @error('respiratory_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">SpO2 (%)</label>
            <input type="number" class="form-control @error('spo2') is-invalid @enderror" name="spo2" value="{{ old('spo2') }}" />
            @error('spo2') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Weight (kg)</label>
            <input type="number" step="0.01" class="form-control @error('weight') is-invalid @enderror" name="weight" value="{{ old('weight') }}" />
            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Height (cm)</label>
            <input type="number" step="0.01" class="form-control @error('height') is-invalid @enderror" name="height" value="{{ old('height') }}" />
            @error('height') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Trend Modal --}}
<div class="modal fade" id="trendModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="trendModalTitle">Vitals Trend</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="trendChart"></div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
let vitalsTable, trendChart;

function bpColor(bp) {
  if (!bp) return 'secondary';
  const systolic = parseInt(bp.split('/')[0]);
  if (systolic >= 140 || systolic < 90) return 'danger';
  if (systolic >= 130) return 'warning';
  return 'success';
}

function spo2Color(v) {
  if (v === null || v === undefined) return 'secondary';
  if (v < 94) return 'danger';
  if (v < 96) return 'warning';
  return 'success';
}

function newsColor(v) {
  if (v === null || v === undefined) return 'secondary';
  if (v >= 7) return 'danger';
  if (v >= 5) return 'warning';
  return 'success';
}

document.addEventListener('DOMContentLoaded', function () {
  vitalsTable = $('#vitalsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('vitals.data') }}', type: 'GET', data: function (d) { d.admitted_only = document.getElementById('admittedOnlyFilter').checked ? 1 : 0; } },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'blood_pressure', name: 'blood_pressure', render: d => d ? `<span class="badge bg-label-${bpColor(d)}">${d}</span>` : '—' },
      { data: 'temperature', name: 'temperature', defaultContent: '—' },
      { data: 'pulse_rate', name: 'pulse_rate', defaultContent: '—' },
      { data: 'spo2', name: 'spo2', render: d => d !== null ? `<span class="badge bg-label-${spo2Color(d)}">${d}%</span>` : '—' },
      { data: 'weight', name: 'weight', defaultContent: '—' },
      { data: 'news_score', name: 'news_score', render: d => d !== null ? `<span class="badge bg-label-${newsColor(d)}">${d}</span>` : '—' },
      { data: 'recorded_at', name: 'recorded_at' },
      { data: 'recorded_by_name', name: 'recorded_by_name', orderable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[8, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('admittedOnlyFilter').addEventListener('change', function () {
    vitalsTable.ajax.reload();
  });
});

function viewTrend(patientId, patientName) {
  fetch(`/vitals/${patientId}`)
    .then(res => res.json())
    .then(res => {
      const data = res.data.slice().reverse();
      document.getElementById('trendModalTitle').textContent = 'Vitals Trend — ' + patientName;

      const categories = data.map(v => v.recorded_at);
      const options = {
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        series: [
          { name: 'Pulse Rate', data: data.map(v => v.pulse_rate) },
          { name: 'SpO2', data: data.map(v => v.spo2) },
          { name: 'Temperature', data: data.map(v => v.temperature) },
        ],
        xaxis: { categories, labels: { show: false } },
        stroke: { curve: 'smooth', width: 2 },
        legend: { position: 'top' },
      };

      if (trendChart) trendChart.destroy();
      trendChart = new ApexCharts(document.getElementById('trendChart'), options);
      trendChart.render();

      new bootstrap.Modal(document.getElementById('trendModal')).show();
    });
}

function deleteVital(id) {
  confirmDelete(`/vitals/${id}`, 'Vitals Record', function() {
    vitalsTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasVitalAdd')).show();
});
@elseif($fromPatient)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('offcanvasVitalAdd').querySelector('form');
  form.querySelector('[name="patient_id"]').value = '{{ $fromPatient->id }}';
  new bootstrap.Offcanvas(document.getElementById('offcanvasVitalAdd')).show();
});
@endif
</script>
@endsection

@endsection
