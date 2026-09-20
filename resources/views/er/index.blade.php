@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Emergency (ER) - HMS')

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
              <h4 class="mb-0" id="statWaiting">{{ $waitingCount }}</h4>
              <p class="mb-0">Waiting</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0" id="statTreatment">{{ $underTreatment }}</h4>
              <p class="mb-0">Under Treatment</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-first-aid-kit icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0" id="statRed">{{ $redAlertCount }}</h4>
              <p class="mb-0">Red Alert</p>
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
              <h4 class="mb-0">{{ $todayTotal }}</h4>
              <p class="mb-0">Today Total</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-ambulance icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ER Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Emergency / Casualty Registrations</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="triageFilter" class="form-select form-select-sm" style="width:140px;">
        <option value="">All Triage</option>
        <option value="red">Red - Immediate</option>
        <option value="yellow">Yellow - Urgent</option>
        <option value="green">Green - Minor</option>
        <option value="black">Black - Deceased</option>
      </select>
      <select id="statusFilter" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Status</option>
        <option value="waiting">Waiting</option>
        <option value="under_treatment">Under Treatment</option>
        <option value="admitted">Admitted</option>
        <option value="discharged">Discharged</option>
        <option value="transferred">Transferred</option>
        <option value="expired">Expired</option>
      </select>
      <button class="btn btn-danger" data-bs-toggle="offcanvas" data-bs-target="#offcanvasErAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Quick Register
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="erTable">
      <thead>
        <tr>
          <th></th>
          <th>ER No</th>
          <th>Patient</th>
          <th>Age/Gender</th>
          <th>Triage</th>
          <th>Complaint</th>
          <th>Doctor</th>
          <th>Status</th>
          <th>Arrival</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Quick Registration Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasErAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">ER Quick Registration</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('er.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Existing Patient (optional)</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" id="er_patient_select">
            <option value="">Walk-in / New</option>
            @foreach ($patients as $p)
            <option value="{{ $p->id }}" data-name="{{ $p->name }}" data-phone="{{ $p->phone }}" @selected(old('patient_id')==$p->id)>{{ $p->name }} ({{ $p->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Patient Name *</label>
          <input type="text" class="form-control @error('patient_name') is-invalid @enderror" name="patient_name" id="er_patient_name" value="{{ old('patient_name') }}" required />
          @error('patient_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control @error('patient_phone') is-invalid @enderror" name="patient_phone" id="er_patient_phone" value="{{ old('patient_phone') }}" />
            @error('patient_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-3 mb-4">
            <label class="form-label">Age</label>
            <input type="number" min="0" class="form-control @error('age') is-invalid @enderror" name="age" value="{{ old('age') }}" />
            @error('age') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-3 mb-4">
            <label class="form-label">Gender</label>
            <select class="form-select @error('gender') is-invalid @enderror" name="gender">
              <option value="">-</option>
              <option value="male" @selected(old('gender')=='male')>M</option>
              <option value="female" @selected(old('gender')=='female')>F</option>
              <option value="other" @selected(old('gender')=='other')>O</option>
            </select>
            @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Triage Level *</label>
          <select class="form-select @error('triage_level') is-invalid @enderror" name="triage_level" required>
            <option value="red" @selected(old('triage_level')=='red')>🔴 Red - Immediate</option>
            <option value="yellow" @selected(old('triage_level','yellow')=='yellow')>🟡 Yellow - Urgent</option>
            <option value="green" @selected(old('triage_level')=='green')>🟢 Green - Minor</option>
            <option value="black" @selected(old('triage_level')=='black')>⚫ Black - Deceased/Expectant</option>
          </select>
          @error('triage_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Mode of Arrival</label>
          <select class="form-select @error('mode_of_arrival') is-invalid @enderror" name="mode_of_arrival">
            <option value="walk-in" @selected(old('mode_of_arrival','walk-in')=='walk-in')>Walk-in</option>
            <option value="ambulance" @selected(old('mode_of_arrival')=='ambulance')>Ambulance</option>
            <option value="referred" @selected(old('mode_of_arrival')=='referred')>Referred</option>
          </select>
          @error('mode_of_arrival') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Chief Complaint *</label>
          <input type="text" class="form-control @error('chief_complaint') is-invalid @enderror" name="chief_complaint" value="{{ old('chief_complaint') }}" required />
          @error('chief_complaint') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Presenting Symptoms</label>
          <textarea class="form-control @error('presenting_symptoms') is-invalid @enderror" name="presenting_symptoms" rows="2">{{ old('presenting_symptoms') }}</textarea>
          @error('presenting_symptoms') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-danger">
            <i class="icon-base ti tabler-ambulance me-1"></i> Register
          </button>
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Triage / Assign Doctor Modal --}}
  <div class="modal fade" id="manageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Manage ER Case</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="manageForm">
            <input type="hidden" id="manage_er_id" />
            <div class="mb-4">
              <label class="form-label">Triage Level</label>
              <select class="form-select" id="manage_triage_level">
                <option value="red">🔴 Red - Immediate</option>
                <option value="yellow">🟡 Yellow - Urgent</option>
                <option value="green">🟢 Green - Minor</option>
                <option value="black">⚫ Black</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Attending Doctor</label>
              <select class="form-select" id="manage_doctor_id">
                <option value="">Unassigned</option>
                @foreach ($doctors as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Status</label>
              <select class="form-select" id="manage_status">
                <option value="waiting">Waiting</option>
                <option value="under_treatment">Under Treatment</option>
                <option value="admitted">Admitted</option>
                <option value="discharged">Discharged</option>
                <option value="transferred">Transferred</option>
                <option value="expired">Expired</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">ICD-10 Diagnosis Code</label>
              <input type="text" class="form-control" id="manage_icd10_code" placeholder="e.g. J18.9" />
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ti tabler-check me-1"></i> Update
              </button>
              <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
let erTable, refreshInterval;

function triageColor(t) {
  return { red: 'danger', yellow: 'warning', green: 'success', black: 'dark' }[t] || 'secondary';
}

function statusColor(s) {
  const map = { waiting: 'warning', under_treatment: 'info', admitted: 'primary', discharged: 'success', transferred: 'secondary', expired: 'dark' };
  return map[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  erTable = $('#erTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('er.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'er_no', name: 'er_no' },
      { data: 'patient_name', name: 'patient_name', render: (d, t, r) => `${d}<br><small class="text-muted">${r.patient_mrn}</small>` },
      { data: 'age', name: 'age', render: (d, t, r) => `${d ?? '—'} / ${(r.gender || '—').charAt(0).toUpperCase()}` },
      { data: 'triage_badge', name: 'triage_level' },
      { data: 'chief_complaint', name: 'chief_complaint' },
      { data: 'attending_doctor', name: 'attending_doctor', orderable: false },
      { data: 'status_badge', name: 'status' },
      { data: 'arrival_time', name: 'arrival_time' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[8, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('triageFilter').addEventListener('change', function () {
    erTable.column(4).search(this.value).draw();
  });
  document.getElementById('statusFilter').addEventListener('change', function () {
    erTable.column(7).search(this.value).draw();
  });

  document.getElementById('er_patient_select').addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    if (opt && opt.value) {
      document.getElementById('er_patient_name').value = opt.dataset.name || '';
      document.getElementById('er_patient_phone').value = opt.dataset.phone || '';
    }
  });

  refreshInterval = setInterval(function () {
    erTable.ajax.reload(null, false);
  }, 60000);
});

function openManage(id) {
  fetch(`/er/${id}`)
    .then(res => res.json())
    .then(d => {
      document.getElementById('manage_er_id').value = id;
      document.getElementById('manage_triage_level').value = d.triage_level;
      document.getElementById('manage_doctor_id').value = d.attending_doctor_id || '';
      document.getElementById('manage_status').value = d.status;
      document.getElementById('manage_icd10_code').value = d.icd10_code || '';
      new bootstrap.Modal(document.getElementById('manageModal')).show();
    });
}

document.getElementById('manageForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('manage_er_id').value;
  const triage = document.getElementById('manage_triage_level').value;

  fetch(`/er/${id}/triage`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ triage_level: triage })
  }).then(() => {
    return fetch(`/er/${id}`, {
      method: 'PUT',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        attending_doctor_id: document.getElementById('manage_doctor_id').value || null,
        status: document.getElementById('manage_status').value,
        icd10_code: document.getElementById('manage_icd10_code').value || null,
      })
    });
  }).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('manageModal')).hide();
    erTable.ajax.reload(null, false);
  });
});

function deleteEr(id) {
  confirmDelete(`/er/${id}`, 'ER Record', function() {
    erTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasErAdd')).show();
});
@endif
</script>
@endsection

@endsection
