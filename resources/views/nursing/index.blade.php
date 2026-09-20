@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Nursing / Progress Notes - HMS')

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
  ['label' => 'Nursing Notes', 'url' => route('nursing.index')],
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
        <div class="col-sm-6 col-lg-4">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $todayCount }}</h4>
              <p class="mb-0">Today's Notes</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-notes icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $handoverCount }}</h4>
              <p class="mb-0">Handover Notes</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-transfer icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-4">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $urgentCount }}</h4>
              <p class="mb-0">Urgent / Critical</p>
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

{{-- Notes Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Nursing / Progress Notes</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="typeFilter" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Types</option>
        <option value="nursing">Nursing</option>
        <option value="progress">Progress</option>
        <option value="handover">Handover</option>
        <option value="assessment">Assessment</option>
        <option value="discharge_summary">Discharge Summary</option>
      </select>
      <select id="shiftFilter" class="form-select form-select-sm" style="width:130px;">
        <option value="">All Shifts</option>
        <option value="morning">Morning</option>
        <option value="afternoon">Afternoon</option>
        <option value="night">Night</option>
      </select>
      <select id="priorityFilter" class="form-select form-select-sm" style="width:130px;">
        <option value="">All Priority</option>
        <option value="routine">Routine</option>
        <option value="urgent">Urgent</option>
        <option value="critical">Critical</option>
      </select>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="admittedOnlyFilter">
        <label class="form-check-label small" for="admittedOnlyFilter">Admitted patients only</label>
      </div>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNoteAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Note
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="notesTable">
      <thead>
        <tr>
          <th></th>
          <th>Patient</th>
          <th>Type</th>
          <th>Shift</th>
          <th>Priority</th>
          <th>Note</th>
          <th>Written By</th>
          <th>Time</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Note Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNoteAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Note</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('nursing.store') }}" method="POST">
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
            <label class="form-label">Note Type *</label>
            <select class="form-select @error('note_type') is-invalid @enderror" name="note_type" required>
              <option value="nursing" @selected(old('note_type','nursing')=='nursing')>Nursing</option>
              <option value="progress" @selected(old('note_type')=='progress')>Progress</option>
              <option value="handover" @selected(old('note_type')=='handover')>Handover</option>
              <option value="assessment" @selected(old('note_type')=='assessment')>Assessment</option>
              <option value="discharge_summary" @selected(old('note_type')=='discharge_summary')>Discharge Summary</option>
            </select>
            @error('note_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Shift</label>
            <select class="form-select @error('shift') is-invalid @enderror" name="shift">
              <option value="">Select</option>
              <option value="morning" @selected(old('shift')=='morning')>Morning</option>
              <option value="afternoon" @selected(old('shift')=='afternoon')>Afternoon</option>
              <option value="night" @selected(old('shift')=='night')>Night</option>
            </select>
            @error('shift') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Priority *</label>
          <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
            <option value="routine" @selected(old('priority','routine')=='routine')>Routine</option>
            <option value="urgent" @selected(old('priority')=='urgent')>Urgent</option>
            <option value="critical" @selected(old('priority')=='critical')>Critical</option>
          </select>
          @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Note *</label>
          <textarea class="form-control @error('note') is-invalid @enderror" name="note" rows="6" required>{{ old('note') }}</textarea>
          @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- View Note Modal --}}
  <div class="modal fade" id="viewNoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Note Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p id="viewNoteContent" style="white-space: pre-wrap;"></p>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
let notesTable;

function priorityColor(p) {
  return { routine: 'secondary', urgent: 'warning', critical: 'danger' }[p] || 'secondary';
}

function typeColor(t) {
  return { nursing: 'primary', progress: 'info', handover: 'warning', assessment: 'success', discharge_summary: 'dark' }[t] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  notesTable = $('#notesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('nursing.data') }}', type: 'GET', data: function (d) { d.admitted_only = document.getElementById('admittedOnlyFilter').checked ? 1 : 0; } },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'note_type', name: 'note_type', render: d => `<span class="badge bg-label-${typeColor(d)}">${d.replace('_',' ')}</span>` },
      { data: 'shift', name: 'shift', defaultContent: '—', render: d => d ? d.charAt(0).toUpperCase() + d.slice(1) : '—' },
      { data: 'priority', name: 'priority', render: d => `<span class="badge bg-label-${priorityColor(d)}">${d}</span>` },
      { data: 'note_preview', name: 'note_preview', orderable: false, render: (d, t, r) => `<span role="button" onclick='viewNote(${JSON.stringify(r.note)})' class="text-truncate d-inline-block" style="max-width:250px;">${d}</span>` },
      { data: 'written_by_name', name: 'written_by_name', orderable: false },
      { data: 'noted_at', name: 'noted_at' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[7, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('typeFilter').addEventListener('change', function () {
    notesTable.column(2).search(this.value).draw();
  });
  document.getElementById('shiftFilter').addEventListener('change', function () {
    notesTable.column(3).search(this.value, true, false).draw();
  });
  document.getElementById('priorityFilter').addEventListener('change', function () {
    notesTable.column(4).search(this.value).draw();
  });

  document.getElementById('admittedOnlyFilter').addEventListener('change', function () {
    notesTable.ajax.reload();
  });
});

function viewNote(note) {
  document.getElementById('viewNoteContent').textContent = note;
  new bootstrap.Modal(document.getElementById('viewNoteModal')).show();
}

function deleteNote(id) {
  confirmDelete(`/nursing/${id}`, 'Note', function() {
    notesTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasNoteAdd')).show();
});
@elseif($fromPatient)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('offcanvasNoteAdd').querySelector('form');
  form.querySelector('[name="patient_id"]').value = '{{ $fromPatient->id }}';
  new bootstrap.Offcanvas(document.getElementById('offcanvasNoteAdd')).show();
});
@endif
</script>
@endsection

@endsection
