@php
$configData = Helper::appClasses();
$statusColors = ['trial' => 'info', 'active' => 'success', 'suspended' => 'warning', 'cancelled' => 'danger'];
$subStatus = $subscription->status ?? $hospital->status;
$subStatusColors = ['active' => 'success', 'trial' => 'info', 'suspended' => 'warning', 'cancelled' => 'danger', 'expired' => 'danger'];
@endphp

@extends('layouts/layoutMaster')

@section('title', $hospital->name . ' - HMS')

@section('page-style')
<style>
.timeline-scroll { max-height: 420px; overflow-y: auto; }
.stat-tile { text-align: center; padding: 1rem 0.5rem; border-radius: 10px; background: var(--bs-secondary-bg, rgba(105,108,255,0.04)); }
.stat-tile h4 { margin-bottom: 0.15rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Dashboard', 'url' => route('dashboard')],
  ['label' => 'Hospitals', 'url' => route('super-admin.hospitals.index')],
  ['label' => $hospital->name],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
  <i class="icon-base ti tabler-alert-triangle me-2"></i>{{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($hospital->trashed())
<div class="alert alert-secondary alert-dismissible mb-4">
  <i class="icon-base ti tabler-archive me-2"></i>This hospital is archived. Restore it to resume normal management.
  <button type="button" class="btn btn-sm btn-label-success ms-3" onclick="restoreHospital({{ $hospital->id }})">
    <i class="icon-base ti tabler-refresh me-1"></i>Restore
  </button>
</div>
@endif

<div class="row">
  <div class="col-xl-4 col-lg-5">
    <div class="card mb-4">
      <div class="card-body pt-8">
        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-xl mb-3">
            @if($hospital->logo_url)
            <img src="{{ $hospital->logo_url }}" alt="{{ $hospital->name }}" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;">
            @else
            <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:2rem;width:72px;height:72px;">
              <i class="icon-base ti tabler-building-hospital"></i>
            </span>
            @endif
          </div>
          <h5 class="mb-1">{{ $hospital->name }}</h5>
          <span class="text-muted small mb-2">{{ $hospital->code }}</span>
          <div class="d-flex gap-1 flex-wrap justify-content-center">
            <span class="badge bg-label-primary text-capitalize">{{ $hospital->plan }}</span>
            <span class="badge bg-label-{{ $hospital->trashed() ? 'secondary' : $statusColors[$hospital->status] }}">
              {{ $hospital->trashed() ? 'Archived' : ucfirst($hospital->status) }}
            </span>
            @if($hospital->is_default)
            <span class="badge bg-label-info"><i class="icon-base ti tabler-star me-1"></i>This Install</span>
            @endif
          </div>
        </div>

        <div class="section-title" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--bs-secondary-color);padding:0.5rem 0;border-bottom:2px solid var(--bs-border-color);margin-bottom:0.75rem;">
          Hospital Profile
        </div>
        <div class="mb-4">
          @foreach([
            ['Contact Person', $hospital->contact_person ?? 'N/A'],
            ['Email', $hospital->email ?? 'N/A'],
            ['Phone', $hospital->phone ?? 'N/A'],
            ['City', $hospital->city ?? 'N/A'],
            ['Country', $hospital->country ?? 'N/A'],
            ['Monthly Fee', config('hospital.currency', 'LKR') . ' ' . number_format($hospital->monthly_fee, 2)],
            ['Trial Ends', optional($hospital->trial_ends_at)->format('d M Y') ?? 'N/A'],
            ['Registered On', $hospital->created_at->format('d M Y')],
          ] as [$label, $value])
          <div class="d-flex justify-content-between py-2 border-bottom">
            <span class="text-muted small">{{ $label }}</span>
            <span class="fw-medium small">{{ $value }}</span>
          </div>
          @endforeach
          @if($hospital->notes)
          <div class="pt-3">
            <span class="text-muted small d-block mb-1">Notes</span>
            <p class="small mb-0">{{ $hospital->notes }}</p>
          </div>
          @endif
        </div>

        @unless($hospital->trashed())
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('super-admin.hospitals.index') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-arrow-left me-1"></i> Back</a>
          <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasHospitalEdit"><i class="icon-base ti tabler-edit me-1"></i> Edit</button>
          @if($hospital->status === 'suspended')
          <button class="btn btn-label-success" onclick="setHospitalStatus({{ $hospital->id }}, 'active')"><i class="icon-base ti tabler-player-play me-1"></i> Activate</button>
          @else
          <button class="btn btn-label-warning" onclick="setHospitalStatus({{ $hospital->id }}, 'suspended')"><i class="icon-base ti tabler-player-pause me-1"></i> Suspend</button>
          @endif
          <button class="btn btn-label-danger" onclick="archiveHospital({{ $hospital->id }})"><i class="icon-base ti tabler-archive me-1"></i> Archive</button>
          @if($adminUser)
          <form action="{{ route('super-admin.hospitals.impersonate', $hospital) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-label-info" onclick="return confirm('Log in as {{ addslashes($adminUser->name) }} ({{ addslashes($hospital->name) }})? This will be recorded in the audit log.')">
              <i class="icon-base ti tabler-user-shield me-1"></i> Login as Hospital Admin
            </button>
          </form>
          @else
          <button class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#createAdminModal">
            <i class="icon-base ti tabler-user-plus me-1"></i> Create Hospital Admin
          </button>
          @endif
        </div>
        @else
        <a href="{{ route('super-admin.hospitals.index') }}" class="btn btn-label-secondary w-100"><i class="icon-base ti tabler-arrow-left me-1"></i> Back to Hospitals</a>
        @endunless
      </div>
    </div>
  </div>

  <div class="col-xl-8 col-lg-7">

    {{-- Subscription --}}
    <div class="card mb-4" id="subscription">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="icon-base ti tabler-crown me-2 text-primary"></i>Subscription</h6>
        @if($subscription)
        <button class="btn btn-sm btn-label-primary" onclick="openExtendModal({{ $subscription->id }}, '{{ addslashes($hospital->name) }}')">
          <i class="icon-base ti tabler-calendar-plus me-1"></i>Extend
        </button>
        @endif
      </div>
      <div class="card-body">
        @if($subscription)
        <div class="row g-3">
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Plan</small>
            <h6 class="mb-0 text-capitalize">{{ str_replace('_', ' ', $subscription->plan) }}</h6>
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Status</small>
            <span class="badge bg-label-{{ $subStatusColors[$subStatus] ?? 'secondary' }} text-capitalize">{{ $subStatus }}</span>
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Billing Cycle</small>
            <h6 class="mb-0 text-capitalize">{{ $subscription->billing_cycle }}</h6>
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <small class="text-muted d-block">Expires</small>
            <h6 class="mb-0">{{ optional($subscription->expires_at)->format('d M Y') ?? '—' }}</h6>
          </div>
        </div>

        {{-- "Recent Payments" substitute — no SaaS billing-payment ledger exists
             yet in this codebase, so this shows the subscription's own billing
             summary (real data) rather than a fabricated payment history. --}}
        <div class="mt-4 pt-3 border-top">
          <h6 class="mb-2"><i class="icon-base ti tabler-receipt me-2 text-body-secondary"></i>Billing Summary</h6>
          <div class="d-flex justify-content-between py-1"><span class="text-muted small">Amount</span><span class="fw-medium small">{{ $subscription->currency }} {{ number_format($subscription->total_amount, 2) }} / {{ $subscription->billing_cycle }}</span></div>
          <div class="d-flex justify-content-between py-1"><span class="text-muted small">Allowed Modules</span><span class="fw-medium small">{{ count($subscription->getAllowedModules()) }} modules</span></div>
          <div class="d-flex justify-content-between py-1"><span class="text-muted small">Last Updated</span><span class="fw-medium small">{{ $subscription->updated_at->diffForHumans() }}</span></div>
        </div>
        @else
        <div class="text-center py-6">
          <i class="icon-base ti tabler-crown-off icon-32px text-body-secondary mb-2 d-block"></i>
          <p class="text-body-secondary mb-3">No subscription linked to this hospital yet.</p>
          <a href="{{ route('subscription.index') }}" class="btn btn-sm btn-primary">
            <i class="icon-base ti tabler-plus me-1"></i>Create Subscription
          </a>
        </div>
        @endif
      </div>
    </div>

    {{-- Usage --}}
    <div class="card mb-4" id="usage">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-chart-bar me-2 text-success"></i>Usage Statistics</h6>
      </div>
      <div class="card-body">
        @if($tenantMetrics)
        <div class="row g-3">
          <div class="col-6 col-md-3 stat-tile">
            <h4>{{ $tenantMetrics['totalUsers'] }}{{ $usageSummary && $usageSummary['user']['limit'] !== null ? ' / ' . $usageSummary['user']['limit'] : '' }}</h4>
            <small class="text-muted">Total Users</small>
            @if($usageSummary && $usageSummary['user']['status'] === 'exceeded')<br><span class="badge bg-label-danger mt-1">Limit reached</span>
            @elseif($usageSummary && $usageSummary['user']['status'] === 'approaching')<br><span class="badge bg-label-warning mt-1">Approaching limit</span>@endif
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <h4>{{ $tenantMetrics['totalDoctors'] }}{{ $usageSummary && $usageSummary['doctor']['limit'] !== null ? ' / ' . $usageSummary['doctor']['limit'] : '' }}</h4>
            <small class="text-muted">Total Doctors</small>
            @if($usageSummary && $usageSummary['doctor']['status'] === 'exceeded')<br><span class="badge bg-label-danger mt-1">Limit reached</span>
            @elseif($usageSummary && $usageSummary['doctor']['status'] === 'approaching')<br><span class="badge bg-label-warning mt-1">Approaching limit</span>@endif
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <h4>{{ $tenantMetrics['totalPatients'] }}{{ $usageSummary && $usageSummary['patient']['limit'] !== null ? ' / ' . $usageSummary['patient']['limit'] : '' }}</h4>
            <small class="text-muted">Total Patients</small>
            @if($usageSummary && $usageSummary['patient']['status'] === 'exceeded')<br><span class="badge bg-label-danger mt-1">Limit reached</span>
            @elseif($usageSummary && $usageSummary['patient']['status'] === 'approaching')<br><span class="badge bg-label-warning mt-1">Approaching limit</span>@endif
          </div>
          <div class="col-6 col-md-3 stat-tile">
            <h4>{{ $tenantMetrics['storageUsage'] }}{{ $usageSummary && $usageSummary['storage']['limit'] !== null ? ' / ' . number_format($usageSummary['storage']['limit']) . ' MB' : '' }}</h4>
            <small class="text-muted">Storage Usage</small>
            @if($usageSummary && $usageSummary['storage']['status'] === 'exceeded')<br><span class="badge bg-label-danger mt-1">Limit reached</span>
            @elseif($usageSummary && $usageSummary['storage']['status'] === 'approaching')<br><span class="badge bg-label-warning mt-1">Approaching limit</span>@endif
          </div>
        </div>
        @else
        <div class="text-center py-6">
          <i class="icon-base ti tabler-database-off icon-32px text-body-secondary mb-2 d-block"></i>
          <p class="text-body-secondary mb-0">Usage tracking isn't available for this hospital yet.</p>
          <small class="text-body-secondary">Per-tenant data isolation is a planned future phase (see SUPER_ADMIN_MASTER_PLAN.md §4.2) — until then, only the platform's default hospital has real, non-fabricated usage data.</small>
        </div>
        @endif
      </div>
    </div>

    {{-- Active Users / Login History (default hospital only) --}}
    @if($hospital->is_default)
    <div class="card mb-4">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-users me-2 text-info"></i>Active Users &amp; Recent Logins</h6>
      </div>
      <div class="p-0">
        @forelse($recentUsers as $u)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="avatar avatar-sm">
            @if($u->profile_photo_url)
            <img src="{{ $u->profile_photo_url }}" alt="" class="rounded-circle">
            @else
            <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
            @endif
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium small">{{ $u->name }}</p>
            <small class="text-muted text-capitalize">{{ str_replace('_', ' ', $u->roles->first()?->name ?? 'user') }}</small>
          </div>
          <small class="text-muted">{{ $u->last_login ? $u->last_login->diffForHumans() : 'Never logged in' }}</small>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No users found</small></div>
        @endforelse
      </div>
    </div>
    @endif

    {{-- Audit Timeline --}}
    <div class="card" id="audit">
      <div class="card-header border-bottom">
        <h6 class="mb-0"><i class="icon-base ti tabler-history me-2 text-warning"></i>Audit Timeline</h6>
      </div>
      <div class="p-0 timeline-scroll">
        @forelse($fullActivityLog as $log)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <div class="avatar-initial rounded-circle bg-label-info" style="width:32px;height:32px;font-size:0.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="icon-base ti {{ $log->module === 'subscription' ? 'tabler-crown' : 'tabler-building-hospital' }}" style="font-size:0.9rem;"></i>
          </div>
          <div class="flex-grow-1">
            <p class="mb-0 fw-medium small">{{ $log->description }}</p>
            <small class="text-muted">{{ $log->user_name }} &middot; {{ $log->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No activity recorded yet</small></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- Edit Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasHospitalEdit" style="width:480px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Hospital</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('super-admin.hospitals.update', $hospital->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      @include('super-admin.hospitals._form-fields', ['hospital' => $hospital])
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Update</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>

@unless($adminUser)
{{-- Create Hospital Admin Modal --}}
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('super-admin.hospitals.create-admin', $hospital) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="icon-base ti tabler-user-plus me-2"></i>Create Admin — {{ $hospital->name }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">This creates the account a Super Admin can use to access this hospital's admin view for troubleshooting.</p>
          <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" required />
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" required />
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Password *</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required minlength="8" />
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-1">
            <label class="form-label">Confirm Password *</label>
            <input type="password" class="form-control" name="password_confirmation" required minlength="8" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Create Admin</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endunless

{{-- Extend Subscription Modal --}}
<div class="modal fade" id="extendSubscriptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="extendSubscriptionForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title"><i class="icon-base ti tabler-calendar-plus me-2"></i>Extend Subscription — <span id="extendHospitalName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">New Expiry Date *</label>
          <input type="date" class="form-control" name="expires_at" required min="{{ now()->addDay()->format('Y-m-d') }}" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Extend</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
function setHospitalStatus(id, status) {
  fetch(`/super-admin/hospitals/${id}/status`, {
    method: 'PATCH',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ status })
  })
  .then(res => res.json())
  .then(data => { if (data.success) window.location.reload(); });
}

function archiveHospital(id) {
  if (!confirm('Archive this hospital? It will be hidden from the active directory but can be restored later.')) return;
  fetch(`/super-admin/hospitals/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Accept': 'application/json'
    }
  })
  .then(res => res.json().then(data => ({ ok: res.ok, data })))
  .then(({ ok, data }) => {
    if (ok && data.success) { window.location.href = '{{ route('super-admin.hospitals.index') }}'; }
    else { alert(data.error || 'Unable to archive this hospital.'); }
  })
  .catch(() => alert('Unable to archive this hospital — please try again.'));
}

function restoreHospital(id) {
  fetch(`/super-admin/hospitals/${id}/restore`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => { if (data.success) window.location.reload(); });
}

function openExtendModal(subscriptionId, hospitalName) {
  document.getElementById('extendHospitalName').textContent = hospitalName;
  document.getElementById('extendSubscriptionForm').action = `/subscription/${subscriptionId}/extend`;
  new bootstrap.Modal(document.getElementById('extendSubscriptionModal')).show();
}
</script>
@endsection
