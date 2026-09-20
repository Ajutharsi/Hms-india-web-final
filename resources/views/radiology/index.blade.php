@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Radiology / Imaging - HMS')

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
  ['label' => 'Radiology', 'url' => route('radiology.index')],
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
              <h4 class="mb-0">{{ $totalOrders }}</h4>
              <p class="mb-0">Total Orders</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-radio icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $pendingOrders }}</h4>
              <p class="mb-0">Pending</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $completedOrders }}</h4>
              <p class="mb-0">Completed</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $emergencyOrders }}</h4>
              <p class="mb-0">Emergency</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Orders Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Radiology / Imaging Orders</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="modalityFilter" class="form-select form-select-sm" style="width:140px;">
        <option value="">All Modality</option>
        <option value="X-Ray">X-Ray</option>
        <option value="CT">CT</option>
        <option value="MRI">MRI</option>
        <option value="Ultrasound">Ultrasound</option>
        <option value="Echo">Echo</option>
        <option value="PET">PET</option>
      </select>
      <select id="statusFilter" class="form-select form-select-sm" style="width:140px;">
        <option value="">All Status</option>
        <option value="ordered">Ordered</option>
        <option value="scheduled">Scheduled</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select id="priorityFilter" class="form-select form-select-sm" style="width:130px;">
        <option value="">All Priority</option>
        <option value="routine">Routine</option>
        <option value="urgent">Urgent</option>
        <option value="emergency">Emergency</option>
      </select>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasOrderAdd">
        <i class="icon-base ti tabler-plus me-1"></i> New Order
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="radiologyTable">
      <thead>
        <tr>
          <th></th>
          <th>Order No</th>
          <th>Patient</th>
          <th>Modality</th>
          <th>Study</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Order Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasOrderAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Radiology Order</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('radiology.store') }}" method="POST">
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
          <label class="form-label">Referring Doctor</label>
          <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id">
            <option value="">Select Doctor</option>
            @foreach ($doctors as $d)
            <option value="{{ $d->id }}" data-photo="{{ $d->photo_url }}" @selected(old('doctor_id')==$d->id)>{{ $d->name }}</option>
            @endforeach
          </select>
          @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Modality *</label>
            <select class="form-select @error('modality') is-invalid @enderror" name="modality" required>
              <option value="">Select</option>
              <option value="X-Ray" @selected(old('modality')=='X-Ray')>X-Ray</option>
              <option value="CT" @selected(old('modality')=='CT')>CT</option>
              <option value="MRI" @selected(old('modality')=='MRI')>MRI</option>
              <option value="Ultrasound" @selected(old('modality')=='Ultrasound')>Ultrasound</option>
              <option value="Echo" @selected(old('modality')=='Echo')>Echo</option>
              <option value="PET" @selected(old('modality')=='PET')>PET</option>
            </select>
            @error('modality') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Body Part *</label>
            <input type="text" class="form-control @error('body_part') is-invalid @enderror" name="body_part" placeholder="e.g. Chest" value="{{ old('body_part') }}" required />
            @error('body_part') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Study Description *</label>
          <input type="text" class="form-control @error('study_description') is-invalid @enderror" name="study_description" placeholder="e.g. Chest X-Ray PA View" value="{{ old('study_description') }}" required />
          @error('study_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Priority *</label>
          <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
            <option value="routine" @selected(old('priority','routine')=='routine')>Routine</option>
            <option value="urgent" @selected(old('priority')=='urgent')>Urgent</option>
            <option value="emergency" @selected(old('priority')=='emergency')>Emergency</option>
          </select>
          @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Scheduled Date/Time</label>
          <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" name="scheduled_at" value="{{ old('scheduled_at') }}" />
          @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Clinical Indication</label>
          <textarea class="form-control @error('clinical_indication') is-invalid @enderror" name="clinical_indication" rows="2">{{ old('clinical_indication') }}</textarea>
          @error('clinical_indication') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Enter Findings Modal --}}
  <div class="modal fade" id="findingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Enter Findings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="findingsForm">
            <input type="hidden" id="findings_order_id" />
            <div class="mb-4">
              <label class="form-label">Findings</label>
              <textarea class="form-control" id="findings_text" rows="5"></textarea>
            </div>
            <div class="mb-4">
              <label class="form-label">Impression</label>
              <textarea class="form-control" id="impression_text" rows="3"></textarea>
            </div>
            <div class="mb-4">
              <label class="form-label">Radiologist Name</label>
              <input type="text" class="form-control" id="radiologist_name" />
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ti tabler-check me-1"></i> Save & Complete
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
let radiologyTable;

function priorityColor(p) {
  return { emergency: 'danger', urgent: 'warning', routine: 'success' }[p] || 'secondary';
}

function statusColor(s) {
  const map = { ordered: 'secondary', scheduled: 'info', in_progress: 'warning', completed: 'success', cancelled: 'danger' };
  return map[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  radiologyTable = $('#radiologyTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('radiology.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'order_no', name: 'order_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'modality', name: 'modality' },
      { data: 'study_info', name: 'study_info' },
      { data: 'priority', name: 'priority', render: d => `<span class="badge bg-label-${priorityColor(d)}">${d}</span>` },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColor(d)}">${d.replace('_',' ')}</span>` },
      { data: 'order_date', name: 'order_date' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[7, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('modalityFilter').addEventListener('change', function () {
    radiologyTable.column(3).search(this.value).draw();
  });
  document.getElementById('statusFilter').addEventListener('change', function () {
    radiologyTable.column(6).search(this.value).draw();
  });
  document.getElementById('priorityFilter').addEventListener('change', function () {
    radiologyTable.column(5).search(this.value).draw();
  });
});

function openFindings(id) {
  document.getElementById('findings_order_id').value = id;
  document.getElementById('findings_text').value = '';
  document.getElementById('impression_text').value = '';
  document.getElementById('radiologist_name').value = '';
  new bootstrap.Modal(document.getElementById('findingsModal')).show();
}

document.getElementById('findingsForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('findings_order_id').value;
  fetch(`/radiology/${id}/findings`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      findings: document.getElementById('findings_text').value,
      impression: document.getElementById('impression_text').value,
      radiologist_name: document.getElementById('radiologist_name').value,
    })
  }).then(res => res.json()).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('findingsModal')).hide();
    radiologyTable.ajax.reload(null, false);
  });
});

function deleteOrder(id) {
  confirmDelete(`/radiology/${id}`, 'Radiology Order', function() {
    radiologyTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasOrderAdd')).show();
});
@elseif($fromPatient)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('offcanvasOrderAdd').querySelector('form');
  form.querySelector('[name="patient_id"]').value = '{{ $fromPatient->id }}';
  @if($fromDoctor)
  form.querySelector('[name="doctor_id"]').value = '{{ $fromDoctor->id }}';
  @endif
  new bootstrap.Offcanvas(document.getElementById('offcanvasOrderAdd')).show();
});
@endif
</script>
@endsection

@endsection
