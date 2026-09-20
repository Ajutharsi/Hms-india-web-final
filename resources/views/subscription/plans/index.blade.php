@php
$configData = Helper::appClasses();
$allModules = \App\Models\Subscription::DEFAULT_MODULE_PRICING;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Plan Management - HMS')

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Dashboard', 'url' => route('dashboard')],
  ['label' => 'Subscriptions', 'url' => route('subscription.index')],
  ['label' => 'Plans'],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-6">
  <i class="icon-base ti tabler-circle-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-6 flex-wrap gap-3">
  <div class="d-flex align-items-center gap-3">
    <div class="avatar">
      <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-package icon-26px"></i></span>
    </div>
    <div>
      <h4 class="mb-0">Plan Management</h4>
      <small class="text-muted">Define the subscription tiers hospitals can be placed on</small>
    </div>
  </div>
  <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddPlan">
    <i class="icon-base ti tabler-plus me-1"></i> Create Plan
  </button>
</div>

{{-- Stats --}}
<div class="row g-4 mb-6">
  @foreach([
    ['Total Plans',  $plans->count(),                          'tabler-package',       'primary'],
    ['Active',       $plans->where('status','active')->count(),'tabler-circle-check',  'success'],
    ['Inactive',     $plans->where('status','inactive')->count(),'tabler-player-pause','warning'],
    ['In Use',       $plans->sum('subscriptions_count'),        'tabler-crown',        'warning'],
  ] as [$label,$count,$icon,$color])
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">{{ $label }}</span>
            <h4 class="mb-0 mt-1">{{ $count }}</h4>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="icon-base ti {{ $icon }} icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Plan Catalog</h5>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead class="table-light">
        <tr>
          <th>Plan</th>
          <th>Price</th>
          <th>Billing Cycle</th>
          <th>Trial</th>
          <th>Limits</th>
          <th>Modules</th>
          <th>In Use</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($plans as $plan)
        <tr>
          <td>
            <strong class="d-block">{{ $plan->name }}</strong>
            <small class="text-muted">{{ $plan->key }}</small>
          </td>
          <td class="fw-medium">{{ config('hospital.currency', 'LKR') }} {{ number_format($plan->price, 2) }}</td>
          <td class="text-capitalize">{{ $plan->billing_cycle }}</td>
          <td>{{ $plan->trial_days ? $plan->trial_days . ' days' : '—' }}</td>
          <td>
            <small class="d-block text-muted">Users: {{ $plan->limitLabel($plan->user_limit) }}</small>
            <small class="d-block text-muted">Doctors: {{ $plan->limitLabel($plan->doctor_limit) }}</small>
            <small class="d-block text-muted">Patients: {{ $plan->limitLabel($plan->patient_limit) }}</small>
            <small class="d-block text-muted">Storage: {{ $plan->storage_limit_mb ? number_format($plan->storage_limit_mb) . ' MB' : 'Unlimited' }}</small>
          </td>
          <td>{{ count($plan->modules ?? []) }} modules</td>
          <td>{{ $plan->subscriptions_count }}</td>
          <td>
            @if($plan->trashed())
            <span class="badge bg-label-secondary">Archived</span>
            @else
            <span class="badge bg-label-{{ $plan->status === 'active' ? 'success' : 'warning' }} text-capitalize">{{ $plan->status }}</span>
            @endif
          </td>
          <td>
            <div class="d-flex gap-1">
              @if($plan->trashed())
              <button onclick="restorePlan({{ $plan->id }})" class="btn btn-sm btn-icon btn-label-success" title="Restore"><i class="icon-base ti tabler-refresh"></i></button>
              @else
              <button onclick="editPlan({{ $plan->id }})" class="btn btn-sm btn-icon btn-label-warning" title="Edit"><i class="icon-base ti tabler-edit"></i></button>
              @if($plan->status === 'active')
              <button onclick="setPlanStatus({{ $plan->id }}, 'inactive')" class="btn btn-sm btn-icon btn-label-secondary" title="Deactivate"><i class="icon-base ti tabler-player-pause"></i></button>
              @else
              <button onclick="setPlanStatus({{ $plan->id }}, 'active')" class="btn btn-sm btn-icon btn-label-success" title="Activate"><i class="icon-base ti tabler-player-play"></i></button>
              @endif
              <button onclick="archivePlan({{ $plan->id }})" class="btn btn-sm btn-icon btn-label-danger" title="Archive"><i class="icon-base ti tabler-archive"></i></button>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center text-muted py-6">No plans defined yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@include('subscription.plans._form-offcanvas', ['id' => 'offcanvasAddPlan', 'title' => 'Create Plan', 'formId' => 'addPlanForm', 'action' => route('subscription.plans.store'), 'method' => null, 'allModules' => $allModules])
@include('subscription.plans._form-offcanvas', ['id' => 'offcanvasEditPlan', 'title' => 'Edit Plan', 'formId' => 'editPlanForm', 'action' => null, 'method' => 'PUT', 'allModules' => $allModules])

@endsection

@section('page-script')
<script>
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function editPlan(id) {
  fetch(`/subscription/plans/${id}/json`)
    .then(res => res.json())
    .then(p => {
      const f = document.getElementById('editPlanForm');
      f.action = `/subscription/plans/${id}`;
      f.querySelector('[name="key"]').value = p.key || '';
      f.querySelector('[name="name"]').value = p.name || '';
      f.querySelector('[name="description"]').value = p.description || '';
      f.querySelector('[name="price"]').value = p.price || 0;
      f.querySelector('[name="billing_cycle"]').value = p.billing_cycle || 'monthly';
      f.querySelector('[name="trial_days"]').value = p.trial_days || '';
      f.querySelector('[name="user_limit"]').value = p.user_limit ?? '';
      f.querySelector('[name="doctor_limit"]').value = p.doctor_limit ?? '';
      f.querySelector('[name="patient_limit"]').value = p.patient_limit ?? '';
      f.querySelector('[name="storage_limit_mb"]').value = p.storage_limit_mb ?? '';
      f.querySelector('[name="status"]').value = p.status || 'active';

      const modules = p.modules || [];
      f.querySelectorAll('.plan-module-check').forEach(cb => cb.checked = modules.includes(cb.value));

      new bootstrap.Offcanvas(document.getElementById('offcanvasEditPlan')).show();
    });
}

function setPlanStatus(id, status) {
  fetch(`/subscription/plans/${id}/status`, {
    method: 'PATCH',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ status })
  }).then(res => res.json()).then(data => { if (data.success) location.reload(); });
}

function archivePlan(id) {
  if (!confirm('Archive this plan? It will no longer be selectable for new subscriptions.')) return;
  fetch(`/subscription/plans/${id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
  }).then(res => res.json().then(data => ({ ok: res.ok, data })))
    .then(({ ok, data }) => { if (ok && data.success) location.reload(); else alert(data.error || 'Unable to archive this plan.'); });
}

function restorePlan(id) {
  fetch(`/subscription/plans/${id}/restore`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
  }).then(res => res.json()).then(data => { if (data.success) location.reload(); });
}
</script>
@endsection
