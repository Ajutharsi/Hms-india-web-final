@php
$configData = Helper::appClasses();
$statusColors = ['active' => 'success', 'expired' => 'danger', 'suspended' => 'warning', 'cancelled' => 'secondary'];
$paymentColors = ['paid' => 'success', 'unpaid' => 'warning', 'overdue' => 'danger'];
$planName = $subscription->planCatalog?->name ?? ucfirst(str_replace('_', ' ', $subscription->plan));
$hospitalName = $subscription->hospital?->name ?? $subscription->hospital_name;

$planChanges = $auditEvents->where('action', 'plan_changed');
$renewals = $auditEvents->whereIn('action', ['extended', 'reactivated']);
@endphp

@extends('layouts/layoutMaster')

@section('title', $hospitalName . ' Subscription - HMS')

@section('page-style')
<style>
.timeline-scroll { max-height: 420px; overflow-y: auto; }
.stat-tile { text-align: center; padding: 1rem 0.5rem; border-radius: 10px; background: var(--bs-secondary-bg, rgba(105,108,255,0.04)); }
.modal-footer { padding: 1rem 1.5rem !important; gap: 0.6rem; display: flex; align-items: center; justify-content: space-between !important; }
.modal-footer > * { margin: 0 !important; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Dashboard', 'url' => route('dashboard')],
  ['label' => 'Subscriptions', 'url' => route('subscription.index')],
  ['label' => $hospitalName],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4"><i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4"><i class="icon-base ti tabler-alert-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($subscription->trashed())
<div class="alert alert-secondary alert-dismissible mb-4">
  <i class="icon-base ti tabler-archive me-2"></i>This subscription is archived.
  <button type="button" class="btn btn-sm btn-label-success ms-3" onclick="restoreSubscription({{ $subscription->id }})"><i class="icon-base ti tabler-refresh me-1"></i>Restore</button>
</div>
@endif

<div class="row">
  <div class="col-xl-4 col-lg-5">
    {{-- Hospital Information --}}
    <div class="card mb-4">
      <div class="card-body pt-8">
        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-xl mb-3">
            <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:2rem;width:72px;height:72px;">
              <i class="icon-base ti tabler-building-hospital"></i>
            </span>
          </div>
          <h5 class="mb-1">{{ $hospitalName }}</h5>
          @if($subscription->hospital)
          <a href="{{ route('super-admin.hospitals.show', $subscription->hospital) }}" class="small">View Hospital Profile</a>
          @endif
          <div class="d-flex gap-1 flex-wrap justify-content-center mt-2">
            <span class="badge bg-label-primary">{{ $planName }}</span>
            <span class="badge bg-label-{{ $subscription->trashed() ? 'secondary' : ($statusColors[$subscription->status] ?? 'secondary') }} text-capitalize">{{ $subscription->trashed() ? 'Archived' : $subscription->status }}</span>
            <span class="badge bg-label-{{ $paymentColors[$subscription->payment_status] ?? 'secondary' }} text-capitalize">{{ $subscription->payment_status }}</span>
          </div>
        </div>

        <div class="section-title" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--bs-secondary-color);padding:0.5rem 0;border-bottom:2px solid var(--bs-border-color);margin-bottom:0.75rem;">
          Hospital Information
        </div>
        <div class="mb-4">
          @foreach([
            ['Email', $subscription->hospital?->email ?? $subscription->hospital_email ?? 'N/A'],
            ['Phone', $subscription->hospital?->phone ?? $subscription->hospital_phone ?? 'N/A'],
          ] as [$label, $value])
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted small">{{ $label }}</span>
            <span class="fw-medium small">{{ $value }}</span>
          </div>
          @endforeach
        </div>

        <div class="section-title" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--bs-secondary-color);padding:0.5rem 0;border-bottom:2px solid var(--bs-border-color);margin-bottom:0.75rem;">
          Subscription Timeline
        </div>
        <div class="mb-4">
          @foreach([
            ['Start Date', optional($subscription->trial_started_at ?? $subscription->created_at)->format('d M Y')],
            ['Expiry Date', optional($subscription->expires_at)->format('d M Y') ?? 'N/A'],
            ['Renewal Date', optional($subscription->last_renewed_at)->format('d M Y') ?? 'Never renewed'],
            ['Cancelled On', optional($subscription->cancelled_at)->format('d M Y') ?? 'N/A'],
          ] as [$label, $value])
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted small">{{ $label }}</span>
            <span class="fw-medium small">{{ $value }}</span>
          </div>
          @endforeach
        </div>

        @if($subscription->notes)
        <div class="pt-1 mb-4">
          <span class="text-muted small d-block mb-1">Notes</span>
          <p class="small mb-0">{{ $subscription->notes }}</p>
        </div>
        @endif

        @unless($subscription->trashed())
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('subscription.index') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-arrow-left me-1"></i> Back</a>
          <button class="btn btn-primary" onclick="openExtendModal({{ $subscription->id }}, '{{ addslashes($hospitalName) }}')"><i class="icon-base ti tabler-calendar-plus me-1"></i> Extend</button>
          <button class="btn btn-label-primary" onclick="openChangePlanModal({{ $subscription->id }}, '{{ addslashes($hospitalName) }}', {{ $subscription->plan_id ?? 'null' }})"><i class="icon-base ti tabler-replace me-1"></i> Change Plan</button>
          @if($subscription->status === 'cancelled')
          <button class="btn btn-label-success" onclick="openReactivateModal({{ $subscription->id }}, '{{ addslashes($hospitalName) }}')"><i class="icon-base ti tabler-rotate me-1"></i> Reactivate</button>
          @else
          <button class="btn btn-label-danger" onclick="cancelSubscription({{ $subscription->id }})"><i class="icon-base ti tabler-ban me-1"></i> Cancel</button>
          @endif
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-xl-8 col-lg-7">
    {{-- Current Plan & Billing --}}
    <div class="card mb-4">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-crown me-2 text-primary"></i>Current Plan &amp; Billing</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Plan</small>
            <h6 class="mb-0">{{ $planName }}</h6>
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Billing Cycle</small>
            <h6 class="mb-0 text-capitalize">{{ $subscription->billing_cycle ?? 'monthly' }}</h6>
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Amount</small>
            <h6 class="mb-0">{{ $subscription->currency ?? 'LKR' }} {{ number_format($subscription->total_amount ?? $subscription->calculateTotal(), 2) }}</h6>
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Modules</small>
            <h6 class="mb-0">{{ count($subscription->getAllowedModules()) }}</h6>
          </div>
        </div>
      </div>
    </div>

    {{-- Previous Plans --}}
    <div class="card mb-4">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-history-toggle me-2 text-info"></i>Previous Plans</h6>
      </div>
      <div class="p-0">
        @forelse($planChanges as $event)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium small">{{ $event->description }}</p>
            <small class="text-muted">{{ $event->user_name }} &middot; {{ $event->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No plan changes recorded — still on the original plan.</small></div>
        @endforelse
      </div>
    </div>

    {{-- Renewal History --}}
    <div class="card mb-4">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-calendar-repeat me-2 text-success"></i>Renewal History</h6>
      </div>
      <div class="p-0">
        @forelse($renewals as $event)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium small">{{ $event->description }}</p>
            <small class="text-muted">{{ $event->user_name }} &middot; {{ $event->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No renewals recorded yet.</small></div>
        @endforelse
      </div>
    </div>

    {{-- Audit Events --}}
    <div class="card">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-history me-2 text-warning"></i>Audit Events</h6>
      </div>
      <div class="p-0 timeline-scroll">
        @forelse($auditEvents as $event)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="avatar-initial rounded-circle bg-label-info" style="width:32px;height:32px;font-size:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="icon-base ti tabler-crown" style="font-size:0.9rem;"></i>
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium small">{{ $event->description }}</p>
            <small class="text-muted">{{ $event->user_name }} &middot; {{ $event->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No activity recorded yet</small></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- Extend Modal --}}
<div class="modal fade" id="extendModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="extendForm" method="POST">
        @csrf @method('PUT')
        <div class="modal-header border-bottom">
          <h5 class="modal-title"><i class="icon-base ti tabler-calendar-plus me-2 text-primary"></i>Extend Subscription — <span id="extendName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <label class="form-label">New Expiry Date *</label>
          <input type="date" name="expires_at" id="extendExpiry" class="form-control" required min="{{ now()->addDay()->format('Y-m-d') }}" />
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-calendar-plus me-1"></i> Extend</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Change Plan Modal --}}
<div class="modal fade" id="changePlanModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="changePlanForm" method="POST">
        @csrf @method('PATCH')
        <div class="modal-header border-bottom">
          <h5 class="modal-title"><i class="icon-base ti tabler-replace me-2 text-primary"></i>Change Plan — <span id="changePlanName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <label class="form-label">New Plan *</label>
          <select class="form-select" name="plan_id" id="changePlanSelect" required>
            @foreach(\App\Models\Plan::active()->orderBy('price')->get() as $plan)
            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ config('hospital.currency', 'LKR') }} {{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</option>
            @endforeach
          </select>
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-replace me-1"></i> Change Plan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Reactivate Modal --}}
<div class="modal fade" id="reactivateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="reactivateForm" method="POST">
        @csrf
        <div class="modal-header border-bottom">
          <h5 class="modal-title"><i class="icon-base ti tabler-rotate me-2 text-success"></i>Reactivate Subscription — <span id="reactivateName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p class="text-muted small">This subscription is currently cancelled. Reactivating requires a new expiry date, just like a fresh signup.</p>
          <label class="form-label">New Expiry Date *</label>
          <input type="date" name="expires_at" id="reactivateExpiry" class="form-control" required min="{{ now()->addDay()->format('Y-m-d') }}" />
        </div>
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="icon-base ti tabler-rotate me-1"></i> Reactivate</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function openExtendModal(id, name) {
  document.getElementById('extendForm').action = `/subscription/${id}/extend`;
  document.getElementById('extendName').textContent = name;
  new bootstrap.Modal(document.getElementById('extendModal')).show();
}

function openChangePlanModal(id, name, currentPlanId) {
  document.getElementById('changePlanForm').action = `/subscription/${id}/change-plan`;
  document.getElementById('changePlanName').textContent = name;
  if (currentPlanId) document.getElementById('changePlanSelect').value = currentPlanId;
  new bootstrap.Modal(document.getElementById('changePlanModal')).show();
}

function openReactivateModal(id, name) {
  document.getElementById('reactivateForm').action = `/subscription/${id}/reactivate`;
  document.getElementById('reactivateName').textContent = name;
  new bootstrap.Modal(document.getElementById('reactivateModal')).show();
}

function cancelSubscription(id) {
  if (!confirm('Cancel this subscription? The hospital will lose access once its current period ends.')) return;
  fetch(`/subscription/${id}/cancel`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
  }).then(res => res.json().then(data => ({ ok: res.ok, data })))
    .then(({ ok, data }) => { if (ok && data.success) location.reload(); else alert(data.error || 'Unable to cancel this subscription.'); });
}

function restoreSubscription(id) {
  fetch(`/subscription/${id}/restore`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
  }).then(() => location.reload());
}
</script>
@endsection
