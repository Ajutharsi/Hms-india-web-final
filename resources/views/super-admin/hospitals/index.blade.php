@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Hospitals - HMS')

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

@section('page-style')
<style>
#hospitalsTable tbody tr.hospital-row-clickable { cursor: pointer; transition: background-color 0.15s ease; }
#hospitalsTable tbody tr.hospital-row-clickable:hover { background-color: var(--bs-secondary-bg, rgba(105,108,255,0.06)); }
#hospitalsTable_wrapper .table-responsive { overflow-x: auto; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Dashboard', 'url' => route('dashboard')],
  ['label' => 'Hospitals'],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats — same reusable card used on the Super Admin dashboard --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Total Hospitals" :value="$platformStats['total']" sub="All registered tenants"
      icon="tabler-building-hospital" color="primary" :href="route('super-admin.hospitals.index')" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Active" :value="$platformStats['active']" sub="Paying subscribers"
      icon="tabler-circle-check" color="success" :href="route('super-admin.hospitals.index', ['status' => 'active'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Trial" :value="$platformStats['trial']" sub="Evaluating the platform"
      icon="tabler-clock-hour-4" color="warning" :href="route('super-admin.hospitals.index', ['status' => 'trial'])" />
  </div>
  <div class="col-sm-6 col-xl-3">
    <x-kpi-card label="Suspended" :value="$platformStats['suspended']" sub="Needs follow-up"
      icon="tabler-ban" color="warning" :href="route('super-admin.hospitals.index', ['status' => 'suspended'])" />
  </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="searchInput" placeholder="Search name, code, contact, email..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All Status</option>
          <option value="trial" @selected($statusFilter==='trial')>Trial</option>
          <option value="active" @selected($statusFilter==='active')>Active</option>
          <option value="suspended" @selected($statusFilter==='suspended')>Suspended</option>
          <option value="cancelled" @selected($statusFilter==='cancelled')>Cancelled</option>
          <option value="archived" @selected($statusFilter==='archived')>Archived</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterPlan">
          <option value="">All Plans</option>
          <option value="basic" @selected($planFilter==='basic')>Basic</option>
          <option value="professional" @selected($planFilter==='professional')>Professional</option>
          <option value="enterprise" @selected($planFilter==='enterprise')>Enterprise</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterCountry">
          <option value="">All Countries</option>
          @foreach($countries as $country)
          <option value="{{ $country }}" @selected($countryFilter===$country)>{{ $country }}</option>
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
      <div class="col-md-1 d-flex gap-2">
        <button class="btn btn-success w-100" data-bs-toggle="offcanvas" data-bs-target="#offcanvasHospitalAdd" title="Add Hospital">
          <i class="icon-base ti tabler-plus"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Hospitals Directory</h5>
    <small class="text-body-secondary">Scroll horizontally to see every column</small>
  </div>
  <div class="table-responsive">
    <table class="table" id="hospitalsTable" style="width:100%">
      <thead>
        <tr>
          <th></th>
          <th>Logo</th>
          <th>Name</th>
          <th>Code</th>
          <th>Contact Person</th>
          <th>Email</th>
          <th>Phone</th>
          <th>City</th>
          <th>Country</th>
          <th>Sub. Plan</th>
          <th>Sub. Status</th>
          <th>Expiry</th>
          <th>Users</th>
          <th>Doctors</th>
          <th>Patients</th>
          <th>Storage</th>
          <th>Last Login</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- Add Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasHospitalAdd" style="width:480px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Add Hospital</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('super-admin.hospitals.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      @include('super-admin.hospitals._form-fields')
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>

{{-- Edit Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasHospitalEdit" style="width:480px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Hospital</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form id="editHospitalForm" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      @include('super-admin.hospitals._form-fields')
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Update</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>

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
let hospitalsTable;

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('DOMContentLoaded', function () {
  hospitalsTable = $('#hospitalsTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    rowId: 'id',
    ajax: {
      url: '{{ route('super-admin.hospitals.data') }}',
      type: 'GET',
      data: function (d) {
        d.status = document.getElementById('filterStatus').value;
        d.plan = document.getElementById('filterPlan').value;
        d.country = document.getElementById('filterCountry').value;
        d.expiry = document.getElementById('filterExpiry').value;
        // Supports the dashboard's "Expiring Subscriptions" KPI card deep link.
        if (new URLSearchParams(window.location.search).get('expiring') === '1') {
          d.expiring = '1';
        }
      }
    },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'logo', name: 'logo', orderable: false, searchable: false },
      { data: 'name', name: 'name', render: (d) => `<span class="fw-medium">${escapeHtml(d)}</span>` },
      { data: 'code', name: 'code' },
      { data: 'contact_person', name: 'contact_person', render: (d) => escapeHtml(d || '—') },
      { data: 'email', name: 'email', render: (d) => escapeHtml(d || '—') },
      { data: 'phone', name: 'phone', render: (d) => escapeHtml(d || '—') },
      { data: 'city', name: 'city', render: (d) => escapeHtml(d || '—') },
      { data: 'country', name: 'country', render: (d) => escapeHtml(d || '—') },
      { data: 'plan_badge', name: 'plan', orderable: false },
      { data: 'sub_status_badge', name: 'sub_status_badge', orderable: false, searchable: false },
      { data: 'expiry_display', name: 'expiry_display', orderable: false, searchable: false },
      { data: 'users_display', name: 'users_display', orderable: false, searchable: false },
      { data: 'doctors_display', name: 'doctors_display', orderable: false, searchable: false },
      { data: 'patients_display', name: 'patients_display', orderable: false, searchable: false },
      { data: 'storage_display', name: 'storage_display', orderable: false, searchable: false },
      { data: 'last_login_display', name: 'last_login_display', orderable: false, searchable: false },
      { data: 'status_badge', name: 'status', orderable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    pageLength: 25,
    order: [[2, 'asc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>' },
    createdRow: function (row, data) {
      row.classList.add('hospital-row-clickable');
      row.dataset.hospitalId = data.id;
    }
  });

  $('#hospitalsTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('a, button, select, input').length) return;
    const id = this.dataset.hospitalId;
    if (id) window.location.href = `/super-admin/hospitals/${id}`;
  });

  ['filterStatus', 'filterPlan', 'filterCountry', 'filterExpiry'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => hospitalsTable.draw());
  });

  let searchDebounce;
  document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => hospitalsTable.search(this.value).draw(), 300);
  });

  // Supports the dashboard's "Add Hospital" quick action deep link.
  if (new URLSearchParams(window.location.search).get('new') === '1') {
    new bootstrap.Offcanvas(document.getElementById('offcanvasHospitalAdd')).show();
  }
});

function editHospital(id) {
  fetch(`/super-admin/hospitals/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editHospitalForm');
      f.action = `/super-admin/hospitals/${id}`;
      f.querySelector('[name="name"]').value = d.name || '';
      f.querySelector('[name="contact_person"]').value = d.contact_person || '';
      f.querySelector('[name="email"]').value = d.email || '';
      f.querySelector('[name="phone"]').value = d.phone || '';
      f.querySelector('[name="city"]').value = d.city || '';
      f.querySelector('[name="country"]').value = d.country || '';
      f.querySelector('[name="status"]').value = d.status || 'trial';
      f.querySelector('[name="plan"]').value = d.plan || 'basic';
      f.querySelector('[name="monthly_fee"]').value = d.monthly_fee || '';
      f.querySelector('[name="trial_ends_at"]').value = (d.trial_ends_at || '').substring(0, 10);
      f.querySelector('[name="subscription_starts_at"]').value = (d.subscription_starts_at || '').substring(0, 10);
      f.querySelector('[name="subscription_ends_at"]').value = (d.subscription_ends_at || '').substring(0, 10);
      f.querySelector('[name="notes"]').value = d.notes || '';

      const img = f.querySelector('#logoPreview');
      const fallback = f.querySelector('#logoPreviewFallback');
      if (d.logo_url) {
        img.src = d.logo_url;
        img.classList.remove('d-none');
        fallback.classList.add('d-none');
      } else {
        img.classList.add('d-none');
        fallback.classList.remove('d-none');
      }

      new bootstrap.Offcanvas(document.getElementById('offcanvasHospitalEdit')).show();
    });
}

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
  .then(data => { if (data.success) hospitalsTable.ajax.reload(null, false); });
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
    if (ok && data.success) { hospitalsTable.ajax.reload(null, false); }
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
  .then(data => { if (data.success) hospitalsTable.ajax.reload(null, false); });
}

function openExtendModal(subscriptionId, hospitalName) {
  document.getElementById('extendHospitalName').textContent = hospitalName;
  document.getElementById('extendSubscriptionForm').action = `/subscription/${subscriptionId}/extend`;
  new bootstrap.Modal(document.getElementById('extendSubscriptionModal')).show();
}
</script>
@endsection
