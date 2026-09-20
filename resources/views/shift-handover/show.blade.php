@php
$configData = Helper::appClasses();
$statusColors = ['draft' => 'secondary', 'pending_acceptance' => 'warning', 'accepted' => 'success', 'rejected' => 'danger'];
$isIncoming = auth()->id() === $shiftHandover->incoming_nurse_id;
$isOutgoing = auth()->id() === $shiftHandover->outgoing_nurse_id;
$canAcceptReject = $isIncoming && $shiftHandover->isPending() && auth()->user()->can('accept handovers');
$canEdit = $isOutgoing && $shiftHandover->isEditable();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Handover ' . $shiftHandover->handover_no . ' - HMS')

@section('page-style')
<style>
  /* Modal footer — same padding/gap/alignment fix already applied to the
     dashboard's popups, so the Reject modal on this page matches that convention. */
  .modal-footer {
    padding: 1rem 1.5rem !important;
    gap: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: space-between !important;
  }
  .modal-footer > * { margin: 0 !important; }
  .modal-footer .btn {
    padding: 0.55rem 1rem;
    font-weight: 600;
    border-radius: 8px;
  }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Shift Handover', 'url' => route('shift-handover.index')],
  ['label' => $shiftHandover->handover_no],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">
      Handover {{ $shiftHandover->handover_no }}
      <span class="badge bg-label-{{ $statusColors[$shiftHandover->status] }} text-capitalize ms-2">{{ str_replace('_', ' ', $shiftHandover->status) }}</span>
    </h4>
    <p class="text-muted mb-0">{{ $shiftHandover->ward->name ?? 'N/A' }} &middot; {{ ucfirst($shiftHandover->current_shift) }} &rarr; {{ ucfirst($shiftHandover->next_shift) }} &middot; {{ $shiftHandover->handover_date->format('d M Y') }}</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    @if($canEdit)
    <a href="{{ route('shift-handover.edit', $shiftHandover) }}" class="btn btn-label-warning"><i class="icon-base ti tabler-edit me-1"></i> Edit</a>
    <button onclick="submitHandover({{ $shiftHandover->id }})" class="btn btn-primary"><i class="icon-base ti tabler-send me-1"></i> Submit for Handover</button>
    @endif
    @if($canAcceptReject)
    <button onclick="acceptHandover({{ $shiftHandover->id }})" class="btn btn-success"><i class="icon-base ti tabler-check me-1"></i> Accept</button>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="icon-base ti tabler-x me-1"></i> Reject</button>
    @endif
    <a href="{{ route('shift-handover.print', $shiftHandover) }}" target="_blank" class="btn btn-label-secondary"><i class="icon-base ti tabler-printer me-1"></i> Print</a>
    <a href="{{ route('shift-handover.index') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-arrow-left me-1"></i> Back</a>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">

    {{-- Alerts panel --}}
    @php
    $criticalPatients = $shiftHandover->patients->where('condition', 'critical');
    $highPriority     = $shiftHandover->patients->where('is_high_priority', true);
    $medsDue          = $shiftHandover->patients->where('medication_status', '!=', 'completed');
    $abnormalVitals   = $shiftHandover->patients->filter(function ($c) {
        $v = $c->latestVitals();
        return $v && (($v->spo2 !== null && $v->spo2 < 94) || ($v->pulse_rate !== null && ($v->pulse_rate < 50 || $v->pulse_rate > 120)));
    });
    @endphp
    @if($criticalPatients->count() || $highPriority->count() || $medsDue->count() || $abnormalVitals->count())
    <div class="card mb-6 border-danger">
      <div class="card-header border-bottom bg-label-danger">
        <h6 class="mb-0"><i class="icon-base ti tabler-alert-triangle me-2"></i>Alerts</h6>
      </div>
      <div class="card-body">
        <div class="row g-3 text-center">
          <div class="col-6 col-md-3">
            <h5 class="mb-0 text-danger">{{ $criticalPatients->count() }}</h5>
            <small class="text-muted">Critical Patients</small>
          </div>
          <div class="col-6 col-md-3">
            <h5 class="mb-0 text-warning">{{ $highPriority->count() }}</h5>
            <small class="text-muted">High Priority</small>
          </div>
          <div class="col-6 col-md-3">
            <h5 class="mb-0 text-warning">{{ $medsDue->count() }}</h5>
            <small class="text-muted">Medication Due</small>
          </div>
          <div class="col-6 col-md-3">
            <h5 class="mb-0 text-danger">{{ $abnormalVitals->count() }}</h5>
            <small class="text-muted">Abnormal Vitals</small>
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- Patient cards --}}
    <h6 class="mb-3">Patients ({{ $shiftHandover->patients->count() }})</h6>
    @forelse($shiftHandover->patients as $card)
      @include('shift-handover._patient-card', ['card' => $card])
    @empty
    <div class="card mb-4"><div class="card-body text-center text-muted py-6">No patients on this handover.</div></div>
    @endforelse

    {{-- Pending Tasks --}}
    <div class="card mb-6">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-checklist me-2"></i>Pending Tasks</h6>
      </div>
      <div class="p-0">
        @forelse($shiftHandover->tasks as $task)
        <div class="d-flex align-items-center gap-2 px-4 py-3 border-bottom">
          <i class="icon-base ti {{ $task->is_completed ? 'tabler-square-check text-success' : 'tabler-square' }}"></i>
          <span class="{{ $task->is_completed ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->task_description }}</span>
          @if($task->patient)
          <span class="badge bg-label-secondary ms-auto">{{ $task->patient->name }}</span>
          @endif
        </div>
        @empty
        <div class="text-center py-4 text-muted small">No pending tasks recorded</div>
        @endforelse
      </div>
    </div>

    {{-- Shift Summary --}}
    @if($shiftHandover->shift_summary)
    <div class="card mb-6">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-notes me-2"></i>Shift Summary</h6>
      </div>
      <div class="card-body">
        <p class="mb-0" style="white-space:pre-wrap;">{{ $shiftHandover->shift_summary }}</p>
      </div>
    </div>
    @endif

  </div>

  <div class="col-lg-4">
    {{-- Timeline --}}
    <div class="card mb-6">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-history me-2"></i>Timeline</h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="d-flex gap-3 mb-4">
            <span class="avatar-initial rounded-circle bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="icon-base ti tabler-user" style="font-size:0.9rem;"></i></span>
            <div>
              <div class="fw-medium small">Outgoing: {{ $shiftHandover->outgoingNurse->name ?? 'N/A' }}</div>
              <small class="text-muted">{{ $shiftHandover->created_at->format('d M Y h:i A') }}</small>
            </div>
          </li>
          @if($shiftHandover->submitted_at)
          <li class="d-flex gap-3 mb-4">
            <span class="avatar-initial rounded-circle bg-label-warning" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="icon-base ti tabler-send" style="font-size:0.9rem;"></i></span>
            <div>
              <div class="fw-medium small">Submitted for Handover</div>
              <small class="text-muted">{{ $shiftHandover->submitted_at->format('d M Y h:i A') }}</small>
            </div>
          </li>
          @endif
          <li class="d-flex gap-3 mb-4">
            <span class="avatar-initial rounded-circle bg-label-info" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="icon-base ti tabler-user-check" style="font-size:0.9rem;"></i></span>
            <div>
              <div class="fw-medium small">Incoming: {{ $shiftHandover->incomingNurse->name ?? 'N/A' }}</div>
              <small class="text-muted">{{ $shiftHandover->isPending() ? 'Awaiting response' : '—' }}</small>
            </div>
          </li>
          @if($shiftHandover->accepted_at)
          <li class="d-flex gap-3">
            <span class="avatar-initial rounded-circle bg-label-success" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="icon-base ti tabler-check" style="font-size:0.9rem;"></i></span>
            <div>
              <div class="fw-medium small">Accepted</div>
              <small class="text-muted">{{ $shiftHandover->accepted_at->format('d M Y h:i A') }}</small>
            </div>
          </li>
          @elseif($shiftHandover->rejected_at)
          <li class="d-flex gap-3">
            <span class="avatar-initial rounded-circle bg-label-danger" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="icon-base ti tabler-x" style="font-size:0.9rem;"></i></span>
            <div>
              <div class="fw-medium small">Rejected</div>
              <small class="text-muted">{{ $shiftHandover->rejected_at->format('d M Y h:i A') }}</small>
              <p class="small text-danger mb-0 mt-1">{{ $shiftHandover->rejection_reason }}</p>
            </div>
          </li>
          @endif
        </ul>
      </div>
    </div>
  </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reject Handover</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Reason for rejection *</label>
        <textarea id="rejectReason" class="form-control" rows="4" placeholder="Explain why this handover is being rejected..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" onclick="rejectHandover({{ $shiftHandover->id }})">Reject Handover</button>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
function submitHandover(id) {
  if (!confirm('Submit this handover? You will not be able to edit it after submission.')) return;
  fetch(`/shift-handover/${id}/submit`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
  }).then(() => window.location.reload());
}

function acceptHandover(id) {
  if (!confirm('Accept this handover? You are confirming you have reviewed all patients and pending work.')) return;
  fetch(`/shift-handover/${id}/accept`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
  }).then(res => res.json()).then(() => window.location.reload());
}

function rejectHandover(id) {
  const reason = document.getElementById('rejectReason').value.trim();
  if (!reason) { alert('Please provide a reason for rejection.'); return; }
  fetch(`/shift-handover/${id}/reject`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ reason })
  }).then(res => res.json()).then(() => window.location.reload());
}
</script>
@endsection

@endsection
