@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Admissions - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('content')

@if($fromPatient)
<x-breadcrumb :items="[
  ['label' => 'Ward Admissions', 'url' => route('ward.admissions')],
  ['label' => $fromPatient->name],
]" />
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
  <i class="icon-base ti tabler-x me-2"></i>{{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Total Admissions</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $admissionStats['total'] }}</h4>
            </div>
            <small>All time</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-clipboard-list icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Currently Admitted</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $admissionStats['admitted'] }}</h4>
            </div>
            <small>Active patients</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-user-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Discharged</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $admissionStats['discharged'] }}</h4>
            </div>
            <small>Total discharged</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-secondary">
              <i class="icon-base ti tabler-logout icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Available Beds</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $admissionStats['availableBeds'] }}</h4>
            </div>
            <small>Ready for admission</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-bed icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Admissions Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Patient Admissions</h5>
    @role('super_admin|admin|receptionist|nurse')
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAdmit">
      <i class="icon-base ti tabler-plus me-1"></i> Admit Patient
    </button>
    @endrole
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="admissionsTable">
      <thead>
        <tr>
          <th></th>
          <th>Admission No</th>
          <th>Patient</th>
          <th>Ward</th>
          <th>Bed</th>
          <th>Doctor</th>
          <th>Admission Date</th>
          <th>Discharge Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Admit Patient Offcanvas --}}
  @role('super_admin|admin|receptionist|nurse')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdmit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Admit Patient</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('ward.admit') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
            <option value="">Select Patient</option>
            @foreach($activePatients as $patient)
            <option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->name }} ({{ $patient->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Doctor *</label>
          <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id" required>
            <option value="">Select Doctor</option>
            @foreach($activeDoctors as $doctor)
            <option value="{{ $doctor->id }}" @selected(old('doctor_id')==$doctor->id)>Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
            @endforeach
          </select>
          @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Ward *</label>
          <select class="form-select @error('ward_id') is-invalid @enderror" name="ward_id" id="wardSelect" required>
            <option value="">Select Ward</option>
            @foreach($openWards as $ward)
            <option value="{{ $ward->id }}" @selected(old('ward_id')==$ward->id)>{{ $ward->name }} ({{ $ward->available_beds }} beds available)</option>
            @endforeach
          </select>
          @error('ward_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Bed *</label>
          <select class="form-select @error('bed_id') is-invalid @enderror" name="bed_id" id="bedSelect" required>
            <option value="">Select Ward First</option>
          </select>
          @error('bed_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Admission Date *</label>
          <input type="date" class="form-control @error('admission_date') is-invalid @enderror" name="admission_date"
            value="{{ old('admission_date', now()->toDateString()) }}" required />
          @error('admission_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Admission Reason</label>
          <textarea class="form-control @error('admission_reason') is-invalid @enderror" name="admission_reason" rows="2">{{ old('admission_reason') }}</textarea>
          @error('admission_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Admit
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

  {{-- Discharge Offcanvas — doctors are view-only for admissions --}}
  @role('super_admin|admin|nurse')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDischarge">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Discharge Patient</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="dischargeForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_discharge_admission_id" id="discharge_admission_id" value="{{ old('_discharge_admission_id') }}" />
        <div class="mb-4">
          <label class="form-label">Discharge Date *</label>
          <input type="date" class="form-control @error('discharge_date') is-invalid @enderror" name="discharge_date"
            value="{{ old('discharge_date', now()->toDateString()) }}" required />
          @error('discharge_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div id="dischargeBalanceInfo" class="alert alert-info py-2 px-3 mb-4 small d-none">
          Invoice total: <strong id="db_invoice_total">—</strong> ·
          Payments: <strong id="db_payments">—</strong> ·
          Deposits: <strong id="db_deposits">—</strong> ·
          Outstanding: <strong id="db_outstanding">—</strong>
        </div>
        <div class="mb-4">
          <label class="form-label">Total Charges (LKR) *</label>
          <input type="number" class="form-control @error('total_charges') is-invalid @enderror" name="total_charges"
            step="0.01" min="0" placeholder="0.00" value="{{ old('total_charges') }}" required />
          <small class="text-muted">Pre-filled from the computed outstanding balance — adjust if needed.</small>
          @error('total_charges') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Discharge Notes</label>
          <textarea class="form-control @error('discharge_notes') is-invalid @enderror" name="discharge_notes" rows="3">{{ old('discharge_notes') }}</textarea>
          @error('discharge_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-warning">
            <i class="icon-base ti tabler-logout me-1"></i> Discharge
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

  {{-- Transfer Offcanvas — doctors are view-only for admissions --}}
  @role('super_admin|admin|nurse')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTransfer">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Transfer Patient</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="transferForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_transfer_admission_id" id="transfer_admission_id" value="{{ old('_transfer_admission_id') }}" />
        <div class="mb-4">
          <label class="form-label">Current Ward</label>
          <input type="text" class="form-control" id="transfer_current_ward" disabled />
        </div>
        <div class="mb-4">
          <label class="form-label">Current Bed</label>
          <input type="text" class="form-control" id="transfer_current_bed" disabled />
        </div>
        <hr class="my-4" />
        <div class="mb-4">
          <label class="form-label">New Ward *</label>
          <select class="form-select @error('new_ward_id') is-invalid @enderror" name="new_ward_id" id="transferWardSelect" required>
            <option value="">Select Ward</option>
            @foreach($openWards as $ward)
            <option value="{{ $ward->id }}" @selected(old('new_ward_id')==$ward->id)>{{ $ward->name }} ({{ $ward->available_beds }} beds available)</option>
            @endforeach
          </select>
          @error('new_ward_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">New Bed *</label>
          <select class="form-select @error('new_bed_id') is-invalid @enderror" name="new_bed_id" id="transferBedSelect" required>
            <option value="">Select Ward First</option>
          </select>
          @error('new_bed_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Transfer Reason</label>
          <textarea class="form-control @error('transfer_reason') is-invalid @enderror" name="transfer_reason" rows="2" placeholder="e.g. Needs ICU care">{{ old('transfer_reason') }}</textarea>
          @error('transfer_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-info">
            <i class="icon-base ti tabler-transfer me-1"></i> Transfer
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const canManageAdmissions = @json(!auth()->user()->hasRole('doctor'));

  $('#admissionsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('ward.admissions.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'admission_no' },
      { data: null, render: d => `<div class="d-flex align-items-center gap-2">
          <img src="${encodeURI(d.patient_photo_url)}" alt="" class="rounded-circle flex-shrink-0" style="width:32px;height:32px;object-fit:cover;" onerror="this.src='{{ asset('images/defaults/patient.png') }}'">
          <div><strong>${d.patient_name}</strong><br><small class="text-muted">${d.patient_mrn}</small></div>
        </div>` },
      { data: 'ward_name' },
      { data: 'bed_no', render: d => `<span class="badge bg-label-info">${d}</span>` },
      { data: null, render: d => `<div class="d-flex align-items-center gap-2">
          <img src="${encodeURI(d.doctor_photo_url)}" alt="" class="rounded-circle flex-shrink-0" style="width:28px;height:28px;object-fit:cover;" onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}'">
          <span>${d.doctor_name}</span>
        </div>` },
      { data: 'admission_date' },
      { data: 'discharge_date' },
      { data: 'status', render: d => {
        const c = { admitted:'success', discharged:'secondary', transferred:'warning' };
        return `<span class="badge bg-label-${c[d]||'secondary'}">${d}</span>`;
      }},
      { data: 'id', orderable: false, searchable: false, render: (d, type, row) => {
        let btns = '';
        if (row.status === 'admitted' && canManageAdmissions) {
          btns += `<button onclick="transferPatient(${d}, '${row.ward_name}', '${row.bed_no}')" class="btn btn-sm btn-icon btn-label-info" title="Transfer">
            <i class="icon-base ti tabler-transfer"></i>
          </button>`;
          btns += `<button onclick="dischargePatient(${d})" class="btn btn-sm btn-icon btn-label-warning" title="Discharge">
            <i class="icon-base ti tabler-logout"></i>
          </button>`;
        }
        return `<div class="d-flex gap-2">${btns}</div>`;
      }}
    ]
  });

  // Load beds when ward selected (Admit form)
  document.getElementById('wardSelect')?.addEventListener('change', function() {
    const wardId = this.value;
    const bedSelect = document.getElementById('bedSelect');
    bedSelect.innerHTML = '<option value="">Loading...</option>';

    if (!wardId) {
      bedSelect.innerHTML = '<option value="">Select Ward First</option>';
      return;
    }

    fetch(`/ward/available-beds?ward_id=${wardId}`)
      .then(res => res.json())
      .then(data => {
        bedSelect.innerHTML = '<option value="">Select Bed</option>';
        data.beds.forEach(bed => {
          bedSelect.innerHTML += `<option value="${bed.id}">${bed.bed_no} (${bed.type})</option>`;
        });
      });
  });

  // Load beds when ward selected (Transfer form)
  document.getElementById('transferWardSelect')?.addEventListener('change', function() {
    const wardId = this.value;
    const bedSelect = document.getElementById('transferBedSelect');
    bedSelect.innerHTML = '<option value="">Loading...</option>';

    if (!wardId) {
      bedSelect.innerHTML = '<option value="">Select Ward First</option>';
      return;
    }

    fetch(`/ward/available-beds?ward_id=${wardId}`)
      .then(res => res.json())
      .then(data => {
        bedSelect.innerHTML = '<option value="">Select Bed</option>';
        data.beds.forEach(bed => {
          bedSelect.innerHTML += `<option value="${bed.id}">${bed.bed_no} (${bed.type})</option>`;
        });
      });
  });

});

function dischargePatient(id) {
  const f = document.getElementById('dischargeForm');
  f.action = `/ward/discharge/${id}`;
  document.getElementById('discharge_admission_id').value = id;

  const infoBox = document.getElementById('dischargeBalanceInfo');
  infoBox.classList.add('d-none');
  fetch(`/ward/admissions/${id}/balance`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('db_invoice_total').textContent = Number(data.invoice_total).toFixed(2);
      document.getElementById('db_payments').textContent = Number(data.payments_made).toFixed(2);
      document.getElementById('db_deposits').textContent = Number(data.deposit_balance).toFixed(2);
      document.getElementById('db_outstanding').textContent = Number(data.outstanding).toFixed(2);
      infoBox.classList.remove('d-none');
      f.querySelector('[name="total_charges"]').value = Number(data.outstanding).toFixed(2);
    })
    .catch(() => {});

  new bootstrap.Offcanvas(document.getElementById('offcanvasDischarge')).show();
}

function transferPatient(id, currentWard, currentBed) {
  const f = document.getElementById('transferForm');
  f.action = `/ward/transfer/${id}`;
  document.getElementById('transfer_admission_id').value = id;
  document.getElementById('transfer_current_ward').value = currentWard;
  document.getElementById('transfer_current_bed').value = currentBed;
  new bootstrap.Offcanvas(document.getElementById('offcanvasTransfer')).show();
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_discharge_admission_id'))
    dischargePatient({{ old('_discharge_admission_id') }});
  @elseif(old('_transfer_admission_id'))
    transferPatient({{ old('_transfer_admission_id') }}, '', '');
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasAdmit')).show();
  @endif
});
@elseif($fromPatient)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('offcanvasAdmit').querySelector('form');
  form.querySelector('[name="patient_id"]').value = '{{ $fromPatient->id }}';
  new bootstrap.Offcanvas(document.getElementById('offcanvasAdmit')).show();
});
@endif
</script>

@endsection