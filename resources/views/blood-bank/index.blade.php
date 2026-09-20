@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Blood Bank - HMS')

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
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-0">{{ $totalUnits }}</h4>
          <p class="mb-0">Total Units</p>
        </div>
        <div class="avatar">
          <span class="avatar-initial rounded bg-label-primary text-heading">
            <i class="icon-base ti tabler-droplet-filled icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-0">{{ $nearExpiryCount }}</h4>
          <p class="mb-0">Near Expiry (7 days)</p>
        </div>
        <div class="avatar">
          <span class="avatar-initial rounded bg-label-danger text-heading">
            <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-12 col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <p class="mb-2">Blood Group Availability</p>
        <div class="d-flex flex-wrap gap-2">
          @foreach ($byGroup as $group => $count)
          <span class="badge bg-label-{{ $count > 0 ? 'success' : 'secondary' }} p-2" style="font-size:0.9rem;">
            {{ $group }}: {{ $count }}
          </span>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Blood Units Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Blood Bank Inventory</h5>
    <div class="d-flex gap-2 align-items-center">
      <select id="groupFilter" class="form-select form-select-sm" style="width:120px;">
        <option value="">All Groups</option>
        @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g)
        <option value="{{ $g }}">{{ $g }}</option>
        @endforeach
      </select>
      <select id="statusFilter" class="form-select form-select-sm" style="width:140px;">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="reserved">Reserved</option>
        <option value="transfused">Transfused</option>
        <option value="expired">Expired</option>
        <option value="discarded">Discarded</option>
      </select>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasUnitAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Unit
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="unitsTable">
      <thead>
        <tr>
          <th></th>
          <th>Unit No</th>
          <th>Blood Group</th>
          <th>Component</th>
          <th>Donor</th>
          <th>Expiry</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Unit Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasUnitAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Blood Unit</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('blood-bank.store') }}" method="POST">
        @csrf
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Blood Group *</label>
            <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group" required>
              <option value="">Select</option>
              @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g)
              <option value="{{ $g }}" @selected(old('blood_group')==$g)>{{ $g }}</option>
              @endforeach
            </select>
            @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Component *</label>
            <select class="form-select @error('component') is-invalid @enderror" name="component" required>
              <option value="whole_blood" @selected(old('component','whole_blood')=='whole_blood')>Whole Blood</option>
              <option value="packed_rbc" @selected(old('component')=='packed_rbc')>Packed RBC</option>
              <option value="ffp" @selected(old('component')=='ffp')>FFP</option>
              <option value="platelets" @selected(old('component')=='platelets')>Platelets</option>
              <option value="cryoprecipitate" @selected(old('component')=='cryoprecipitate')>Cryoprecipitate</option>
            </select>
            @error('component') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Donor Name</label>
          <input type="text" class="form-control @error('donor_name') is-invalid @enderror" name="donor_name" value="{{ old('donor_name') }}" />
          @error('donor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Collection Date *</label>
            <input type="date" class="form-control @error('collection_date') is-invalid @enderror" name="collection_date" value="{{ old('collection_date') }}" required />
            @error('collection_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Expiry Date *</label>
            <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" name="expiry_date" value="{{ old('expiry_date') }}" required />
            @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Transfuse Modal --}}
  <div class="modal fade" id="transfuseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Transfuse Blood Unit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="transfuseForm">
            <input type="hidden" id="transfuse_unit_id" />
            <div class="mb-4">
              <label class="form-label">Patient *</label>
              <select class="form-select" id="transfuse_patient_id" required>
                <option value="">Select Patient</option>
                @foreach ($patients as $p)
                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->mrn }})</option>
                @endforeach
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Indication</label>
              <input type="text" class="form-control" id="transfuse_indication" placeholder="e.g. Severe anemia" />
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ti tabler-droplet me-1"></i> Confirm Transfusion
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
let unitsTable;

function statusColor(s) {
  const map = { available: 'success', reserved: 'warning', transfused: 'info', expired: 'danger', discarded: 'secondary' };
  return map[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  unitsTable = $('#unitsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('blood-bank.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'unit_no', name: 'unit_no' },
      { data: 'blood_group_badge', name: 'blood_group' },
      { data: 'component_display', name: 'component' },
      { data: 'donor_name', name: 'donor_name', defaultContent: '—' },
      { data: 'expiry_display', name: 'expiry_date' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('groupFilter').addEventListener('change', function () {
    unitsTable.column(2).search(this.value).draw();
  });
  document.getElementById('statusFilter').addEventListener('change', function () {
    unitsTable.column(6).search(this.value).draw();
  });
});

function openTransfuse(id) {
  document.getElementById('transfuse_unit_id').value = id;
  document.getElementById('transfuse_patient_id').value = '';
  document.getElementById('transfuse_indication').value = '';
  new bootstrap.Modal(document.getElementById('transfuseModal')).show();
}

document.getElementById('transfuseForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('transfuse_unit_id').value;
  fetch(`/blood-bank/${id}/transfuse`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      patient_id: document.getElementById('transfuse_patient_id').value,
      indication: document.getElementById('transfuse_indication').value,
    })
  }).then(res => res.json()).then(res => {
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance(document.getElementById('transfuseModal')).hide();
    unitsTable.ajax.reload(null, false);
  });
});

function deleteUnit(id) {
  confirmDelete(`/blood-bank/${id}`, 'Blood Unit', function() {
    unitsTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasUnitAdd')).show();
});
@endif
</script>
@endsection

@endsection
