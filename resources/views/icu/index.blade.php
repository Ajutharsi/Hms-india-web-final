@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'ICU Charting - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

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
              <h4 class="mb-0">{{ $icuPatientsCount }}</h4>
              <p class="mb-0">ICU Patients Today</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-heart-rate-monitor icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $criticalCount }}</h4>
              <p class="mb-0">Critical (GCS &lt; 9)</p>
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
              <h4 class="mb-0">{{ $onVentilatorCount }}</h4>
              <p class="mb-0">On Ventilator</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-lungs icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $todayChartsCount }}</h4>
              <p class="mb-0">Today's Charts</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-clipboard-list icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ICU Charts Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">ICU Charting</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasChartAdd">
      <i class="icon-base ti tabler-plus me-1"></i> New Chart Entry
    </button>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="icuTable">
      <thead>
        <tr>
          <th></th>
          <th>Patient</th>
          <th>BP</th>
          <th>HR</th>
          <th>GCS</th>
          <th>FiO2</th>
          <th>PEEP</th>
          <th>Fluid Balance</th>
          <th>Time</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add ICU Chart Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasChartAdd" data-bs-scroll="true" style="width:500px;max-width:92vw;">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New ICU Chart Entry</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('icu.store') }}" method="POST">
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

        <h6 class="mb-3 text-primary">Ventilator</h6>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Mode</label>
            <select class="form-select" name="ventilator_mode">
              <option value="">None</option>
              <option value="AC">AC</option>
              <option value="SIMV">SIMV</option>
              <option value="CPAP">CPAP</option>
              <option value="PSV">PSV</option>
            </select>
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">FiO2 (%)</label>
            <input type="number" min="0" max="100" class="form-control" name="fio2" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">PEEP (cmH2O)</label>
            <input type="number" min="0" class="form-control" name="peep" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Tidal Volume (ml)</label>
            <input type="number" min="0" class="form-control" name="tidal_volume" />
          </div>
          <div class="col-12 mb-4">
            <label class="form-label">Respiratory Rate (set)</label>
            <input type="number" min="0" class="form-control" name="respiratory_rate_set" />
          </div>
        </div>

        <h6 class="mb-3 text-primary">Hemodynamics</h6>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Arterial BP</label>
            <input type="text" class="form-control" name="arterial_bp" placeholder="120/80" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">CVP (mmHg)</label>
            <input type="number" class="form-control" name="cvp" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Heart Rate</label>
            <input type="number" min="0" max="300" class="form-control" name="heart_rate" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Rhythm</label>
            <input type="text" class="form-control" name="rhythm" placeholder="Sinus, AF..." />
          </div>
        </div>

        <h6 class="mb-3 text-primary">Glasgow Coma Scale</h6>
        <div class="row">
          <div class="col-4 mb-4">
            <label class="form-label">Eye (1-4)</label>
            <input type="number" min="1" max="4" class="form-control" name="gcs_eye" />
          </div>
          <div class="col-4 mb-4">
            <label class="form-label">Verbal (1-5)</label>
            <input type="number" min="1" max="5" class="form-control" name="gcs_verbal" />
          </div>
          <div class="col-4 mb-4">
            <label class="form-label">Motor (1-6)</label>
            <input type="number" min="1" max="6" class="form-control" name="gcs_motor" />
          </div>
        </div>

        <h6 class="mb-3 text-primary">Fluid Balance</h6>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Intake Oral (ml)</label>
            <input type="number" step="0.01" min="0" class="form-control" name="intake_oral" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Intake IV (ml)</label>
            <input type="number" step="0.01" min="0" class="form-control" name="intake_iv" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Output Urine (ml)</label>
            <input type="number" step="0.01" min="0" class="form-control" name="output_urine" />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Output Drain (ml)</label>
            <input type="number" step="0.01" min="0" class="form-control" name="output_drain" />
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Lines & Drains</label>
          <textarea class="form-control" name="lines_drains" rows="2" placeholder="CVL, arterial line, foley..."></textarea>
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="2"></textarea>
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

@section('page-script')
<script>
let icuTable;

function gcsColor(gcs) {
  if (gcs === null) return 'secondary';
  if (gcs < 9) return 'danger';
  if (gcs < 13) return 'warning';
  return 'success';
}

document.addEventListener('DOMContentLoaded', function () {
  icuTable = $('#icuTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('icu.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'arterial_bp', name: 'arterial_bp', defaultContent: '—' },
      { data: 'heart_rate', name: 'heart_rate', defaultContent: '—' },
      { data: 'gcs_total', name: 'gcs_total', render: d => d !== null ? `<span class="badge bg-label-${gcsColor(d)}">${d}</span>` : '—' },
      { data: 'fio2', name: 'fio2', defaultContent: '—', render: d => d !== null ? d + '%' : '—' },
      { data: 'peep', name: 'peep', defaultContent: '—' },
      { data: 'fluid_balance', name: 'fluid_balance', render: d => d !== null ? parseFloat(d).toFixed(0) + ' ml' : '—' },
      { data: 'charted_at', name: 'charted_at' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[8, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });
});

function deleteChart(id) {
  confirmDelete(`/icu/${id}`, 'ICU Chart', function() {
    icuTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasChartAdd')).show();
});
@endif
</script>
@endsection

@endsection
