@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Subscription Management - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-style')
<style>
#subscriptionsTable_wrapper .table-responsive { overflow-x: auto; }
#subscriptionsTable tbody tr.sub-row-clickable { cursor: pointer; transition: background-color 0.15s ease; }
#subscriptionsTable tbody tr.sub-row-clickable:hover { background-color: var(--bs-secondary-bg, rgba(105,108,255,0.06)); }
.modal-footer { padding: 1rem 1.5rem !important; gap: 0.6rem; display: flex; align-items: center; justify-content: space-between !important; }
.modal-footer > * { margin: 0 !important; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Dashboard', 'url' => route('dashboard')],
  ['label' => 'Subscriptions'],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-6">
  <i class="icon-base ti tabler-circle-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-6">
  <i class="icon-base ti tabler-alert-triangle me-2"></i>{{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-6 flex-wrap gap-3">
  <div class="d-flex align-items-center gap-3">
    <div class="avatar">
      <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-crown icon-26px"></i></span>
    </div>
    <div>
      <h4 class="mb-0">Subscription Management</h4>
      <small class="text-muted">Manage every hospital's subscription, renewals & billing</small>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('subscription.plans.index') }}" class="btn btn-label-primary">
      <i class="icon-base ti tabler-package me-1"></i> Manage Plans
    </a>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddSub">
      <i class="icon-base ti tabler-plus me-1"></i> Add Subscription
    </button>
  </div>
</div>

{{-- Stats --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Total Subscriptions" :value="$stats['total']" sub="All hospitals" icon="tabler-crown" color="primary" :href="route('subscription.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Active" :value="$stats['active']" sub="Currently paying" icon="tabler-circle-check" color="success" :href="route('subscription.index', ['status' => 'active'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Expiring Soon" :value="$stats['expiring']" sub="Within 30 days" icon="tabler-calendar-due" color="warning" :href="route('subscription.index', ['expiry' => '30'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Cancelled" :value="$stats['cancelled']" sub="Needs follow-up" icon="tabler-ban" color="warning" :href="route('subscription.index', ['status' => 'cancelled'])" />
  </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="searchInput" placeholder="Search hospital..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All Status</option>
          <option value="active" @selected($statusFilter==='active')>Active</option>
          <option value="expired" @selected($statusFilter==='expired')>Expired</option>
          <option value="suspended" @selected($statusFilter==='suspended')>Suspended</option>
          <option value="cancelled" @selected($statusFilter==='cancelled')>Cancelled</option>
          <option value="archived" @selected($statusFilter==='archived')>Archived</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterPlan">
          <option value="">All Plans</option>
          @foreach($plans as $plan)
          <option value="{{ $plan->id }}" @selected((string)$planFilter===(string)$plan->id)>{{ $plan->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterExpiry">
          <option value="">Any Expiry</option>
          <option value="7" @selected($expiryFilter==='7')>Expiring in 7 days</option>
          <option value="30" @selected($expiryFilter==='30')>Expiring in 30 days</option>
          <option value="expired" @selected($expiryFilter==='expired')>Already expired</option>
        </select>
      </div>
      <div class="col-md-3">
        <div class="input-group input-group-sm">
          <input type="date" class="form-control" id="filterDateFrom" title="From" />
          <span class="input-group-text">to</span>
          <input type="date" class="form-control" id="filterDateTo" title="To" />
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">All Subscriptions</h5>
  </div>
  <div class="table-responsive">
    <table class="table" id="subscriptionsTable" style="width:100%">
      <thead class="table-light">
        <tr>
          <th>Hospital</th>
          <th>Current Plan</th>
          <th>Status</th>
          <th>Start Date</th>
          <th>Expiry Date</th>
          <th>Renewal Date</th>
          <th>Billing Cycle</th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- Add Subscription Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddSub" style="width:480px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Add Subscription</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('subscription.store') }}" method="POST">
      @csrf
      <div class="mb-4">
        <label class="form-label">Hospital *</label>
        <select class="form-select" name="hospital_id" required>
          <option value="">Select a hospital...</option>
          @foreach($hospitals as $hospital)
          <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-4">
        <label class="form-label">Plan *</label>
        <select class="form-select" name="plan_id" required>
          <option value="">Select a plan...</option>
          @foreach($plans as $plan)
          <option value="{{ $plan->id }}">{{ $plan->name }} — {{ config('hospital.currency', 'LKR') }} {{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</option>
          @endforeach
        </select>
      </div>
      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Payment Status</label>
          <select class="form-select" name="payment_status">
            <option value="unpaid">Unpaid</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Billing Cycle</label>
          <select class="form-select" name="billing_cycle">
            <option value="monthly">Monthly (plan default)</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Currency</label>
          <select class="form-select" name="currency">
            <option value="LKR">LKR</option>
            <option value="USD">USD</option>
            <option value="INR">INR</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Expires On <small class="text-body-secondary">(defaults to plan trial/1 month)</small></label>
          <input type="date" class="form-control" name="expires_at" />
        </div>
      </div>
      <div class="mb-6">
        <label class="form-label">Notes</label>
        <textarea class="form-control" name="notes" rows="2"></textarea>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Subscription</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
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
            @foreach($plans as $plan)
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
let subscriptionsTable;

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

document.addEventListener('DOMContentLoaded', function () {
  subscriptionsTable = $('#subscriptionsTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    rowId: 'id',
    ajax: {
      url: '{{ route('subscription.data') }}',
      type: 'GET',
      data: function (d) {
        d.status = document.getElementById('filterStatus').value;
        d.plan_id = document.getElementById('filterPlan').value;
        d.expiry = document.getElementById('filterExpiry').value;
        d.date_from = document.getElementById('filterDateFrom').value;
        d.date_to = document.getElementById('filterDateTo').value;
      }
    },
    columns: [
      { data: 'hospital_display', name: 'hospital_name', render: (d) => `<span class="fw-medium">${escapeHtml(d)}</span>` },
      { data: 'plan_display', name: 'plan_display', orderable: false, searchable: false },
      { data: 'status_badge', name: 'status', orderable: false, searchable: false },
      { data: 'start_display', name: 'start_display', orderable: false, searchable: false },
      { data: 'expiry_display', name: 'expires_at' },
      { data: 'renewal_display', name: 'last_renewed_at' },
      { data: 'billing_cycle_display', name: 'billing_cycle', orderable: false, searchable: false },
      { data: 'amount_display', name: 'total_amount' },
      { data: 'payment_status_badge', name: 'payment_status', orderable: false, searchable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    pageLength: 25,
    order: [[0, 'asc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>' },
    createdRow: function (row, data) {
      row.classList.add('sub-row-clickable');
      row.dataset.subId = data.id;
    }
  });

  $('#subscriptionsTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('a, button, select, input').length) return;
    const id = this.dataset.subId;
    if (id) window.location.href = `/subscription/${id}`;
  });

  ['filterStatus', 'filterPlan', 'filterExpiry', 'filterDateFrom', 'filterDateTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => subscriptionsTable.draw());
  });

  let searchDebounce;
  document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => subscriptionsTable.search(this.value).draw(), 300);
  });
});

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
    .then(({ ok, data }) => { if (ok && data.success) subscriptionsTable.ajax.reload(null, false); else alert(data.error || 'Unable to cancel this subscription.'); });
}

function archiveSubscription(id) {
  if (!confirm('Archive this subscription? It will be hidden from the active list but its history is preserved.')) return;
  fetch(`/subscription/${id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
  }).then(res => res.json().then(data => ({ ok: res.ok, data })))
    .then(({ ok, data }) => { if (ok && data.success) subscriptionsTable.ajax.reload(null, false); else alert(data.error || 'Unable to archive this subscription.'); });
}

function restoreSubscription(id) {
  fetch(`/subscription/${id}/restore`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
  }).then(() => subscriptionsTable.ajax.reload(null, false));
}
</script>
@endsection
