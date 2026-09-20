@php
$configData = Helper::appClasses();
$isEdit = isset($handover) && $handover;
@endphp

@extends('layouts/layoutMaster')

@section('title', ($isEdit ? 'Edit' : 'New') . ' Shift Handover - HMS')

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Shift Handover', 'url' => route('shift-handover.index')],
  ['label' => $isEdit ? 'Edit ' . $handover?->handover_no : 'New Shift Handover'],
]" />

@if ($errors->any())
<div class="alert alert-danger mb-4">
  <i class="icon-base ti tabler-alert-circle me-2"></i>Please fix the errors below before saving.
  <ul class="mb-0 mt-2">
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<form action="{{ $isEdit ? route('shift-handover.update', $handover) : route('shift-handover.store') }}" method="POST" id="handoverForm">
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- Header --}}
  <div class="card mb-6">
    <div class="card-header border-bottom">
      <h5 class="mb-0"><i class="icon-base ti tabler-info-circle me-2 text-primary"></i>Handover Details</h5>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-3">
          <label class="form-label">Ward *</label>
          <select class="form-select @error('ward_id') is-invalid @enderror" name="ward_id" id="wardSelect" required {{ $isEdit ? 'disabled' : '' }}>
            <option value="">Select Ward</option>
            @foreach($wards as $ward)
            <option value="{{ $ward->id }}" @selected(old('ward_id', $handover?->ward_id ?? '') == $ward->id)>{{ $ward->name }}</option>
            @endforeach
          </select>
          @if($isEdit)<input type="hidden" name="ward_id" value="{{ $handover?->ward_id }}">@endif
          @error('ward_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
          <label class="form-label">Current Shift *</label>
          <select class="form-select @error('current_shift') is-invalid @enderror" name="current_shift" required>
            <option value="">Select</option>
            <option value="morning" @selected(old('current_shift', $handover?->current_shift ?? '')=='morning')>Morning</option>
            <option value="evening" @selected(old('current_shift', $handover?->current_shift ?? '')=='evening')>Evening</option>
            <option value="night" @selected(old('current_shift', $handover?->current_shift ?? '')=='night')>Night</option>
          </select>
          @error('current_shift') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
          <label class="form-label">Next Shift *</label>
          <select class="form-select @error('next_shift') is-invalid @enderror" name="next_shift" required>
            <option value="">Select</option>
            <option value="morning" @selected(old('next_shift', $handover?->next_shift ?? '')=='morning')>Morning</option>
            <option value="evening" @selected(old('next_shift', $handover?->next_shift ?? '')=='evening')>Evening</option>
            <option value="night" @selected(old('next_shift', $handover?->next_shift ?? '')=='night')>Night</option>
          </select>
          @error('next_shift') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Incoming Nurse *</label>
          <select class="form-select @error('incoming_nurse_id') is-invalid @enderror" name="incoming_nurse_id" required>
            <option value="">Select Nurse</option>
            @foreach($nurses as $nurse)
            <option value="{{ $nurse->id }}" @selected(old('incoming_nurse_id', $handover?->incoming_nurse_id ?? '') == $nurse->id)>{{ $nurse->name }}</option>
            @endforeach
          </select>
          @error('incoming_nurse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
          <label class="form-label">Date *</label>
          <input type="date" class="form-control @error('handover_date') is-invalid @enderror" name="handover_date"
            value="{{ old('handover_date', $handover?->handover_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
          @error('handover_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>
      @if(!$isEdit)
      <div class="mt-4">
        <button type="button" id="loadPatientsBtn" class="btn btn-label-primary">
          <i class="icon-base ti tabler-refresh me-1"></i> Load Admitted Patients
        </button>
        <small class="text-muted ms-2">Automatically loads every currently-admitted patient in the selected ward.</small>
      </div>
      @endif
    </div>
  </div>

  {{-- Patient cards --}}
  <div class="card mb-6">
    <div class="card-header border-bottom">
      <h5 class="mb-0"><i class="icon-base ti tabler-users me-2 text-primary"></i>Patients</h5>
    </div>
    <div class="card-body">
      <div id="patientCardsContainer"></div>
      <div id="noPatientsMsg" class="text-center text-muted py-6">
        Select a ward{{ $isEdit ? '' : ' and click "Load Admitted Patients"' }} to see the patient list.
      </div>
      @error('patients') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Pending Tasks --}}
  <div class="card mb-6">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="icon-base ti tabler-checklist me-2 text-primary"></i>Pending Tasks</h5>
      <button type="button" id="addTaskBtn" class="btn btn-sm btn-label-primary"><i class="icon-base ti tabler-plus me-1"></i> Add Task</button>
    </div>
    <div class="card-body">
      <div id="tasksContainer"></div>
      <div id="noTasksMsg" class="text-muted small">No tasks added yet — e.g. "Give insulin at 8 PM", "Change IV fluid at 10 PM".</div>
    </div>
  </div>

  {{-- Shift Summary --}}
  <div class="card mb-6">
    <div class="card-header border-bottom">
      <h5 class="mb-0"><i class="icon-base ti tabler-notes me-2 text-primary"></i>Shift Summary</h5>
    </div>
    <div class="card-body">
      <textarea class="form-control" name="shift_summary" rows="5" placeholder="e.g. Ward remained stable. One patient developed mild fever. Continue observation overnight.">{{ old('shift_summary', $handover?->shift_summary ?? '') }}</textarea>
    </div>
  </div>

  <div class="d-flex gap-2 mb-6">
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save as Draft</button>
    <a href="{{ $isEdit ? route('shift-handover.show', $handover) : route('shift-handover.index') }}" class="btn btn-label-secondary">Cancel</a>
  </div>
</form>

@section('page-script')
<script>
let patientIndex = 0;
let taskIndex = 0;
const nurses = @json($nurses);

function conditionOptions(selected) {
  return ['stable','improving','critical','observation'].map(v =>
    `<option value="${v}" ${v===selected?'selected':''}>${v.charAt(0).toUpperCase()+v.slice(1)}</option>`).join('');
}
function medStatusOptions(selected) {
  return ['completed','pending','missed'].map(v =>
    `<option value="${v}" ${v===selected?'selected':''}>${v.charAt(0).toUpperCase()+v.slice(1)}</option>`).join('');
}

function renderPatientCard(p) {
  const idx = patientIndex++;
  const saved = p.saved || {};
  const vitals = p.vitals;
  const vitalsHtml = vitals
    ? `<div class="row g-2 small">
         <div class="col-6"><span class="text-muted">BP:</span> ${vitals.blood_pressure ?? '—'}</div>
         <div class="col-6"><span class="text-muted">Pulse:</span> ${vitals.pulse_rate ?? '—'}</div>
         <div class="col-6"><span class="text-muted">Temp:</span> ${vitals.temperature ?? '—'}&deg;C</div>
         <div class="col-6"><span class="text-muted">RR:</span> ${vitals.respiratory_rate ?? '—'}</div>
         <div class="col-6"><span class="text-muted">SpO2:</span> ${vitals.spo2 ?? '—'}%</div>
         <div class="col-12 text-muted" style="font-size:0.7rem;">as of ${vitals.recorded_at ?? ''}</div>
       </div>`
    : `<div class="text-muted small">No vitals recorded</div>`;

  return `
  <div class="card mb-4 patient-card" data-patient-id="${p.patient_id}">
    <div class="card-body">
      <input type="hidden" name="patients[${idx}][patient_id]" value="${p.patient_id}">
      <input type="hidden" name="patients[${idx}][admission_id]" value="${p.admission_id ?? ''}">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
          <img src="${p.photo_url}" alt="${p.name}" class="rounded-circle" style="width:52px;height:52px;object-fit:cover;">
          <div>
            <div class="fw-semibold">${p.name}</div>
            <div class="text-muted small">${p.mrn} &middot; Bed ${p.bed_no}</div>
            <div class="text-muted small">Admitted ${p.admission_date ?? 'N/A'}</div>
          </div>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="patients[${idx}][is_high_priority]" value="1" id="hp${idx}" ${saved.is_high_priority ? 'checked' : ''}>
          <label class="form-check-label small fw-medium text-danger" for="hp${idx}">High Priority</label>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label small">Condition</label>
          <select class="form-select form-select-sm" name="patients[${idx}][condition]">${conditionOptions(saved.condition || 'stable')}</select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Medication Status</label>
          <select class="form-select form-select-sm" name="patients[${idx}][medication_status]">${medStatusOptions(saved.medication_status || p.suggested_med_status || 'pending')}</select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">IV Fluid Status</label>
          <select class="form-select form-select-sm" name="patients[${idx}][iv_fluid_status]">
            <option value="">N/A</option>
            <option value="running" ${saved.iv_fluid_status==='running'?'selected':''}>Running</option>
            <option value="completed" ${saved.iv_fluid_status==='completed'?'selected':''}>Completed</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Next IV Change</label>
          <input type="datetime-local" class="form-control form-control-sm" name="patients[${idx}][iv_next_change_time]" value="${saved.iv_next_change_time ?? ''}">
        </div>
      </div>

      <div class="border rounded p-3 mb-3">
        <div class="text-muted small fw-semibold text-uppercase mb-2">Latest Vitals</div>
        ${vitalsHtml}
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small">Doctor Instructions</label>
          <textarea class="form-control form-control-sm" rows="2" name="patients[${idx}][doctor_instructions]">${saved.doctor_instructions ?? ''}</textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Pending Procedures</label>
          <textarea class="form-control form-control-sm" rows="2" name="patients[${idx}][pending_procedures]">${saved.pending_procedures ?? ''}</textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Pending Lab Reports</label>
          <textarea class="form-control form-control-sm" rows="2" name="patients[${idx}][pending_lab_reports]">${saved.pending_lab_reports ?? ''}</textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Pending Imaging</label>
          <textarea class="form-control form-control-sm" rows="2" name="patients[${idx}][pending_imaging]">${saved.pending_imaging ?? ''}</textarea>
        </div>
        <div class="col-12">
          <label class="form-label small">Special Notes</label>
          <textarea class="form-control form-control-sm" rows="2" name="patients[${idx}][special_notes]">${saved.special_notes ?? ''}</textarea>
        </div>
      </div>
    </div>
  </div>`;
}

function renderPatients(list) {
  const container = document.getElementById('patientCardsContainer');
  const noMsg = document.getElementById('noPatientsMsg');
  patientIndex = 0;
  container.innerHTML = list.map(renderPatientCard).join('');
  noMsg.style.display = list.length ? 'none' : 'block';
}

function patientOptionsForTasks() {
  const cards = [...document.querySelectorAll('.patient-card')];
  return cards.map(c => {
    const name = c.querySelector('.fw-semibold').textContent;
    return `<option value="${c.dataset.patientId}">${name}</option>`;
  }).join('');
}

function renderTaskRow(task) {
  const idx = taskIndex++;
  task = task || {};
  return `
  <div class="row g-2 align-items-center mb-2 task-row">
    <div class="col-md-3">
      <select class="form-select form-select-sm" name="tasks[${idx}][patient_id]">
        <option value="">Ward-wide</option>
        ${patientOptionsForTasks()}
      </select>
    </div>
    <div class="col-md-8">
      <input type="text" class="form-control form-control-sm" name="tasks[${idx}][task_description]" placeholder="e.g. Give insulin at 8 PM" value="${task.task_description ?? ''}">
    </div>
    <div class="col-md-1">
      <button type="button" class="btn btn-sm btn-icon btn-label-danger" onclick="this.closest('.task-row').remove()"><i class="icon-base ti tabler-trash"></i></button>
    </div>
  </div>`;
}

document.getElementById('addTaskBtn').addEventListener('click', function () {
  document.getElementById('tasksContainer').insertAdjacentHTML('beforeend', renderTaskRow());
  document.getElementById('noTasksMsg').style.display = 'none';
  // re-select any previously chosen patient on existing rows is not needed — new row only.
});

@if(!$isEdit)
document.getElementById('loadPatientsBtn').addEventListener('click', function () {
  const wardId = document.getElementById('wardSelect').value;
  if (!wardId) { alert('Please select a ward first.'); return; }
  fetch(`{{ route('shift-handover.ward-patients') }}?ward_id=${wardId}`)
    .then(res => res.json())
    .then(data => {
      renderPatients(data);
      if (!data.length) alert('No admitted patients found in this ward.');
    });
});
@endif

document.addEventListener('DOMContentLoaded', function () {
  const existingPatients = @json($existingPatients);
  const existingTasks = @json($existingTasks);

  if (existingPatients.length) {
    renderPatients(existingPatients);
  }
  if (existingTasks.length) {
    document.getElementById('noTasksMsg').style.display = 'none';
    existingTasks.forEach(t => {
      document.getElementById('tasksContainer').insertAdjacentHTML('beforeend', renderTaskRow(t));
    });
    // Re-select the saved patient_id on each hydrated task row now that patient cards exist.
    document.querySelectorAll('.task-row select').forEach((sel, i) => {
      if (existingTasks[i] && existingTasks[i].patient_id) sel.value = existingTasks[i].patient_id;
    });
  }
});

document.getElementById('handoverForm').addEventListener('submit', function (e) {
  if (!document.querySelectorAll('.patient-card').length) {
    e.preventDefault();
    alert('Please load and include at least one patient before saving.');
  }
});
</script>
@endsection

@endsection
