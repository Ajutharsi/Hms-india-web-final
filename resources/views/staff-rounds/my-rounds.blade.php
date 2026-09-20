@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'My Rounds - HMS')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Alert Banner --}}
<div id="alertBanner" class="d-none mb-4"></div>

{{-- Stats --}}
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">My Wards</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2" id="totalWards">—</h4>
            </div>
            <small>Assigned to me</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-building-hospital icon-26px"></i>
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
            <span class="text-heading">Overdue</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2 text-danger" id="overdueCount">—</h4>
            </div>
            <small>Need immediate round</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
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
            <span class="text-heading">Due Soon</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2 text-warning" id="dueCount">—</h4>
            </div>
            <small>Within 15 minutes</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-clock-exclamation icon-26px"></i>
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
            <span class="text-heading">My Rounds Today</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ \App\Models\StaffRound::whereDate('round_time', today())->where('staff_id', auth()->id())->count() }}</h4>
            </div>
            <small>Completed</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-clock-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Ward Cards --}}
<div class="row g-4" id="tilesContainer">
  <div class="col-12 text-center py-5">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 text-muted">Loading your assignments...</p>
  </div>
</div>

{{-- Mark Round Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMarkRound" style="width:500px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title" id="roundPatientName">Mark Round</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form id="markRoundForm" method="POST">
      @csrf
      <input type="hidden" name="assignment_id" id="assignment_id_input" value="{{ old('assignment_id') }}" />
      <input type="hidden" name="_round_patient_id" id="round_patient_id_input" value="{{ old('_round_patient_id') }}" />

      <p class="text-muted small mb-4">Record vitals (optional) and mark round as complete.</p>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="form-label">Blood Pressure</label>
          <input type="text" class="form-control @error('blood_pressure') is-invalid @enderror" name="blood_pressure" placeholder="e.g. 120/80" value="{{ old('blood_pressure') }}" />
          @error('blood_pressure') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-6">
          <label class="form-label">Temperature (°F)</label>
          <input type="number" step="0.01" min="0" class="form-control @error('temperature') is-invalid @enderror" name="temperature" placeholder="e.g. 98.6" value="{{ old('temperature') }}" />
          @error('temperature') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-6">
          <label class="form-label">Pulse Rate (bpm)</label>
          <input type="number" min="0" class="form-control @error('pulse_rate') is-invalid @enderror" name="pulse_rate" placeholder="e.g. 72" value="{{ old('pulse_rate') }}" />
          @error('pulse_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-6">
          <label class="form-label">SpO2 (%)</label>
          <input type="number" min="0" max="100" class="form-control @error('spo2') is-invalid @enderror" name="spo2" placeholder="e.g. 98" value="{{ old('spo2') }}" />
          @error('spo2') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="mb-6">
        <label class="form-label">Notes</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3"
          placeholder="Patient condition, observations...">{{ old('notes') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success flex-fill">
          <i class="icon-base ti tabler-clock-check me-1"></i> Mark Round Complete
        </button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>

{{-- History Modal --}}
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="historyModalTitle">Round History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" id="historyModalBody"></div>
    </div>
  </div>
</div>

<script>
function formatTime(mins) {
  if (mins === null) return '—';
  if (mins < 0) return `${Math.abs(mins)} min overdue`;
  if (mins < 60) return `in ${mins} min`;
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return `in ${h}h ${m}m`;
}

function getColor(name) {
  const colors = ['primary','success','danger','warning','info','secondary'];
  let sum = 0;
  for (let c of name) sum += c.charCodeAt(0);
  return colors[sum % colors.length];
}

function renderTiles(data) {
  const container = document.getElementById('tilesContainer');

  // Update stats
  document.getElementById('totalWards').textContent  = data.length;
  document.getElementById('overdueCount').textContent = data.filter(w => w.alert_status === 'danger').length;
  document.getElementById('dueCount').textContent     = data.filter(w => w.alert_status === 'warning').length;

  // Alert banner
  const overdue = data.filter(w => w.is_overdue);
  const banner  = document.getElementById('alertBanner');
  if (overdue.length > 0) {
    banner.className = 'alert alert-danger mb-4 d-flex align-items-center gap-2';
    banner.innerHTML = `
      <i class="icon-base ti tabler-alert-triangle" style="font-size:1.25rem;"></i>
      <strong>${overdue.length} ward${overdue.length > 1 ? 's' : ''} overdue for rounds!</strong>
      Please check: ${overdue.map(w => w.ward_name).join(', ')}`;
  } else {
    banner.className = 'd-none';
  }

  if (data.length === 0) {
    container.innerHTML = `
      <div class="col-12 text-center py-5">
        <i class="icon-base ti tabler-clipboard-x" style="font-size:3rem;color:var(--bs-secondary-color);"></i>
        <p class="mt-2 text-muted">No rounds assigned to you.</p>
      </div>`;
    return;
  }

  container.innerHTML = data.map(w => {
    const alertColor = w.alert_status === 'danger' ? 'danger' : (w.alert_status === 'warning' ? 'warning' : 'success');
    const timeText   = formatTime(w.minutes_left);

    const patientsHtml = w.patients.length === 0
      ? '<p class="text-muted small mb-0">No admitted patients</p>'
      : w.patients.map(p => `
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar" style="width:28px;height:28px;">
              <span class="avatar-initial rounded-circle bg-label-${getColor(p.name)}"
                style="font-size:0.7rem;width:28px;height:28px;">
                ${p.name.charAt(0).toUpperCase()}
              </span>
            </div>
            <div>
              <p class="mb-0 fw-medium" style="font-size:0.8rem;">${p.name}</p>
              <small class="text-muted">${p.mrn} · Bed: ${p.bed_no}</small>
            </div>
          </div>
          <div class="d-flex gap-1">
            <button onclick="markRound(${p.id}, '${p.name}', ${w.id})"
              class="btn btn-sm btn-${w.is_overdue ? 'danger' : 'success'} py-1 px-2"
              style="font-size:0.72rem;">
              <i class="icon-base ti tabler-clock-check"></i>
            </button>
            <button onclick="viewHistory(${p.id}, '${p.name}')"
              class="btn btn-sm btn-label-info py-1 px-2"
              style="font-size:0.72rem;">
              <i class="icon-base ti tabler-history"></i>
            </button>
          </div>
        </div>`).join('');

    return `
    <div class="col-xl-4 col-lg-6 col-md-6">
      <div class="card h-100" style="border:1px solid var(--bs-border-color);box-shadow:none;border-top:3px solid var(--bs-${alertColor});">

        <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-3"
          style="border-bottom:1px solid var(--bs-border-color);">
          <div>
            <h6 class="mb-0 fw-semibold">${w.ward_name}</h6>
            <small class="text-muted">Every ${w.frequency_hours}hr · Next: ${w.next_round_at || '—'} (${timeText})</small>
          </div>
          ${w.is_overdue
            ? '<span class="badge bg-label-danger">⚠ Overdue</span>'
            : w.alert_status === 'warning'
            ? '<span class="badge bg-label-warning">Due Soon</span>'
            : '<span class="badge bg-label-success">On Track</span>'
          }
        </div>

        <div class="px-4 py-3" style="max-height:280px;overflow-y:auto;">
          ${patientsHtml}
        </div>

        ${w.last_round_at ? `
        <div class="px-4 py-2" style="border-top:1px solid var(--bs-border-color);background:var(--bs-body-bg);">
          <small class="text-muted">Last round: ${w.last_round_at}</small>
        </div>` : ''}

      </div>
    </div>`;
  }).join('');
}

function loadRounds() {
  fetch('{{ route('staff-rounds.my-rounds.data') }}')
    .then(res => res.json())
    .then(data => {
      renderTiles(data.data);
    });
}

function markRound(patientId, patientName, assignmentId) {
  document.getElementById('roundPatientName').textContent = 'Mark Round — ' + patientName;
  document.getElementById('markRoundForm').action = `/staff-rounds/${patientId}/mark`;
  document.getElementById('assignment_id_input').value = assignmentId;
  document.getElementById('markRoundForm').reset();
  document.getElementById('assignment_id_input').value = assignmentId;
  document.getElementById('round_patient_id_input').value = patientId;
  new bootstrap.Offcanvas(document.getElementById('offcanvasMarkRound')).show();
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_round_patient_id'))
    document.getElementById('markRoundForm').action = '/staff-rounds/{{ old('_round_patient_id') }}/mark';
    new bootstrap.Offcanvas(document.getElementById('offcanvasMarkRound')).show();
  @endif
});
@endif

function viewHistory(patientId, patientName) {
  document.getElementById('historyModalTitle').textContent = patientName + ' — Round History';
  document.getElementById('historyModalBody').innerHTML = `
    <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`;
  new bootstrap.Modal(document.getElementById('historyModal')).show();

  fetch(`/staff-rounds/${patientId}/history`)
    .then(res => res.json())
    .then(data => {
      if (data.data.length === 0) {
        document.getElementById('historyModalBody').innerHTML =
          '<p class="text-muted text-center py-4">No rounds recorded.</p>';
        return;
      }
      document.getElementById('historyModalBody').innerHTML = `
        <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr><th>Time</th><th>BP</th><th>Temp</th><th>Pulse</th><th>SpO2</th><th>Notes</th></tr>
          </thead>
          <tbody>
            ${data.data.map(r => `
            <tr>
              <td><small>${r.round_time}</small></td>
              <td><small>${r.blood_pressure || '—'}</small></td>
              <td><small>${r.temperature || '—'}</small></td>
              <td><small>${r.pulse_rate || '—'}</small></td>
              <td><small>${r.spo2 || '—'}</small></td>
              <td><small>${r.notes || '—'}</small></td>
            </tr>`).join('')}
          </tbody>
        </table>
        </div>`;
    });
}

document.addEventListener('DOMContentLoaded', function() {
  loadRounds();
  setInterval(loadRounds, 2 * 60 * 1000);
});
</script>

@endsection