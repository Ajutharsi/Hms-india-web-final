@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'eMAR - Medication Administration Record - HMS')

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

@if($fromPatient)
<x-breadcrumb :items="[
  ['label' => 'eMAR', 'url' => route('emar.index')],
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
              <h4 class="mb-0">{{ $scheduledCount }}</h4>
              <p class="mb-0">Scheduled</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $administeredCount }}</h4>
              <p class="mb-0">Administered Today</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $missedCount }}</h4>
              <p class="mb-0">Missed</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $prnCount }}</h4>
              <p class="mb-0">PRN Pending</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-pill icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- eMAR Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Medication Administration Record</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="statusFilter" class="form-select form-select-sm" style="width:160px;">
        <option value="">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="administered">Administered</option>
        <option value="missed">Missed</option>
        <option value="refused">Refused</option>
        <option value="held">Held</option>
      </select>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="admittedOnlyFilter">
        <label class="form-check-label small" for="admittedOnlyFilter">Admitted patients only</label>
      </div>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMarAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Medication
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="marTable">
      <thead>
        <tr>
          <th></th>
          <th>Patient</th>
          <th>Drug</th>
          <th>Dosage</th>
          <th>Route</th>
          <th>Frequency</th>
          <th>Scheduled</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Medication Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMarAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Medication Order</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('emar.store') }}" method="POST">
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
        <div class="mb-4">
          <label class="form-label">Drug Name *</label>
          <input type="text" class="form-control @error('drug_name') is-invalid @enderror" name="drug_name"
            placeholder="e.g. Paracetamol 500mg" value="{{ old('drug_name') }}" required />
          @error('drug_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Dosage *</label>
          <input type="text" class="form-control @error('dosage') is-invalid @enderror" name="dosage"
            placeholder="e.g. 1 tablet" value="{{ old('dosage') }}" required />
          @error('dosage') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Route</label>
          <select class="form-select @error('route') is-invalid @enderror" name="route">
            <option value="">Select</option>
            @foreach (['oral'=>'Oral','IV'=>'IV','IM'=>'IM','SC'=>'SC','topical'=>'Topical','inhalation'=>'Inhalation'] as $val => $label)
            <option value="{{ $val }}" @selected(old('route')==$val)>{{ $label }}</option>
            @endforeach
          </select>
          @error('route') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Frequency</label>
          <select class="form-select @error('frequency') is-invalid @enderror" name="frequency">
            <option value="">Select</option>
            @foreach (['OD'=>'OD - Once Daily','BD'=>'BD - Twice Daily','TDS'=>'TDS - Thrice Daily','QID'=>'QID - Four Times Daily','PRN'=>'PRN - As Needed','STAT'=>'STAT - Immediately'] as $val => $label)
            <option value="{{ $val }}" @selected(old('frequency')==$val)>{{ $label }}</option>
            @endforeach
          </select>
          @error('frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Scheduled Time</label>
          <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror" name="scheduled_time" value="{{ old('scheduled_time') }}" />
          @error('scheduled_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_prn" value="1" id="is_prn" @checked(old('is_prn')) />
            <label class="form-check-label" for="is_prn">PRN (As Needed)</label>
          </div>
        </div>
        <div class="mb-4">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_controlled" value="1" id="is_controlled" @checked(old('is_controlled')) />
            <label class="form-check-label" for="is_controlled">Controlled Drug</label>
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

  {{-- Administer Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdminister">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Record Administration</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="administerForm">
        <input type="hidden" id="administer_id" />
        <div class="mb-4">
          <label class="form-label">Status *</label>
          <select class="form-select" id="administer_status" required>
            <option value="administered">Administered</option>
            <option value="missed">Missed</option>
            <option value="refused">Refused</option>
            <option value="held">Held</option>
          </select>
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control" id="administer_notes" rows="3"></textarea>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-check me-1"></i> Confirm
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('page-script')
<script>
let marTable;
document.addEventListener('DOMContentLoaded', function () {
  marTable = $('#marTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('emar.data') }}', type: 'GET', data: function (d) { d.admitted_only = document.getElementById('admittedOnlyFilter').checked ? 1 : 0; } },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'drug_display', name: 'drug_name' },
      { data: 'dosage', name: 'dosage' },
      { data: 'route', name: 'route', defaultContent: '—' },
      { data: 'frequency_display', name: 'frequency', orderable: false },
      { data: 'scheduled_time', name: 'scheduled_time', defaultContent: '—' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('statusFilter').addEventListener('change', function () {
    marTable.column(7).search(this.value).draw();
  });

  document.getElementById('admittedOnlyFilter').addEventListener('change', function () {
    marTable.ajax.reload();
  });
});

function openAdminister(id) {
  document.getElementById('administer_id').value = id;
  document.getElementById('administer_status').value = 'administered';
  document.getElementById('administer_notes').value = '';
  new bootstrap.Offcanvas(document.getElementById('offcanvasAdminister')).show();
}

document.getElementById('administerForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('administer_id').value;
  fetch(`/emar/${id}/administer`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      status: document.getElementById('administer_status').value,
      notes: document.getElementById('administer_notes').value,
    })
  }).then(res => res.json()).then(() => {
    bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasAdminister')).hide();
    marTable.ajax.reload(null, false);
  });
});

function deleteMar(id) {
  confirmDelete(`/emar/${id}`, 'Medication Order', function() {
    marTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasMarAdd')).show();
});
@elseif($fromPatient)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('offcanvasMarAdd').querySelector('form');
  form.querySelector('[name="patient_id"]').value = '{{ $fromPatient->id }}';
  new bootstrap.Offcanvas(document.getElementById('offcanvasMarAdd')).show();
});
@endif
</script>
@endsection

@endsection
