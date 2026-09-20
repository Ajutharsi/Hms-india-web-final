@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Feature Catalog - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Feature Management', 'url' => route('super-admin.features.index')], ['label' => 'Feature Catalog']]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Feature Catalog</h4>
    <p class="mb-0 text-body-secondary">Every platform module and feature, its status, category, and dependencies.</p>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Search feature or key..." />
        </div>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="filterCategory">
          <option value="">All Categories</option>
          @foreach($categories as $category)
          <option value="{{ $category }}">{{ $category }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterTier">
          <option value="">All Tiers</option>
          <option value="core">Core</option>
          <option value="optional">Optional</option>
          <option value="experimental">Experimental</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All Status</option>
          <option value="enabled">Enabled</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="button" class="btn btn-primary w-100" id="applyBtn"><i class="icon-base ti tabler-filter"></i> Filter</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom"><h5 class="mb-0">Features</h5></div>
  <div class="table-responsive">
    <table class="table" id="catalogTable" style="width:100%">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Category</th>
          <th>Tier</th>
          <th>Status</th>
          <th>Dependency</th>
          <th>Last Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- Feature Detail Modal --}}
<div class="modal fade" id="featureDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="featureDetailTitle">Feature Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="featureDetailBody">
        <div class="text-center py-4 text-body-secondary">Loading…</div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
  function csrf() { return document.querySelector('meta[name="csrf-token"]')?.content; }

  function buildQuery() {
    const p = new URLSearchParams();
    const search = document.getElementById('filterSearch').value;
    const category = document.getElementById('filterCategory').value;
    const tier = document.getElementById('filterTier').value;
    const status = document.getElementById('filterStatus').value;
    if (search) p.set('search', search);
    if (category) p.set('category', category);
    if (tier) p.set('tier', tier);
    if (status) p.set('status', status);
    return p.toString();
  }

  // Pre-fill filters from query string (KPI cards link here with ?status=enabled etc.)
  const initialParams = new URLSearchParams(window.location.search);
  if (initialParams.get('status')) document.getElementById('filterStatus').value = initialParams.get('status');
  if (initialParams.get('tier')) document.getElementById('filterTier').value = initialParams.get('tier');
  if (initialParams.get('category')) document.getElementById('filterCategory').value = initialParams.get('category');

  let table;

  function loadTable() {
    const q = buildQuery();
    if (table) { table.destroy(); }
    table = $('#catalogTable').DataTable({
      ajax: { url: '{{ route('super-admin.features.catalog.data') }}' + (q ? '?' + q : ''), dataSrc: 'data' },
      columns: [
        { data: 'name', render: (d, t, row) => `<a href="#" class="feature-detail-link fw-medium" data-key="${row.key}">${escapeHtml(d)}</a><div class="small text-body-secondary">${escapeHtml(row.key)}</div>` },
        { data: 'category' },
        { data: 'tier', render: (d) => {
            const colors = { core: 'primary', optional: 'info', experimental: 'warning' };
            return `<span class="badge bg-label-${colors[d] || 'secondary'}">${d.charAt(0).toUpperCase() + d.slice(1)}</span>`;
          } },
        { data: 'enabled', render: (d) => d ? '<span class="badge bg-label-success">Enabled</span>' : '<span class="badge bg-label-danger">Disabled</span>' },
        { data: 'depends_on', render: (d) => d && d.length ? d.map(escapeHtml).join(', ') : '<span class="text-body-secondary">None</span>' },
        { data: 'updated_at' },
        {
          data: 'key', orderable: false,
          render: (key, t, row) => {
            if (row.is_core) {
              return '<span class="text-body-secondary small">Core — always on</span>';
            }
            if (!row.independently_toggleable) {
              return `<span class="text-body-secondary small">Follows ${escapeHtml((row.depends_on || []).join(', '))}</span>`;
            }
            return row.enabled
              ? `<button type="button" class="btn btn-sm btn-label-danger disable-btn" data-key="${key}" data-name="${escapeHtml(row.name)}">Disable</button>`
              : `<button type="button" class="btn btn-sm btn-label-success enable-btn" data-key="${key}" data-name="${escapeHtml(row.name)}">Enable</button>`;
          },
        },
      ],
      order: [[0, 'asc']],
      pageLength: 25,
      language: { emptyTable: 'No features match.', zeroRecords: 'No features match.' },
    });
  }

  document.getElementById('catalogTable').addEventListener('click', function (e) {
    const detailLink = e.target.closest('.feature-detail-link');
    if (detailLink) {
      e.preventDefault();
      openFeatureDetail(detailLink.dataset.key);
      return;
    }

    const enableBtn = e.target.closest('.enable-btn');
    if (enableBtn) {
      if (!confirm(`Enable "${enableBtn.dataset.name}"? This may affect dependent features.`)) return;
      submitAction(enableBtn.dataset.key, 'enable');
    }

    const disableBtn = e.target.closest('.disable-btn');
    if (disableBtn) {
      if (!confirm(`Disable "${disableBtn.dataset.name}"? Features that depend on it will be blocked from being disabled if they are still enabled.`)) return;
      submitAction(disableBtn.dataset.key, 'disable');
    }
  });

  function submitAction(key, action) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/super-admin/features/${key}/${action}`;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrf();
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  }

  function hospitalRowHtml(h, moduleKey) {
    const planLabel = h.has_subscription ? escapeHtml(h.plan_name || 'no plan') : 'no subscription';
    let overrideControl;
    if (!h.overridable) {
      overrideControl = h.plan_allows
        ? '<span class="text-body-secondary small">No override — plan-only</span>'
        : '<span class="text-body-secondary small">Restricted by plan</span>';
    } else {
      const current = h.override === true ? 'enable' : (h.override === false ? 'disable' : 'inherit');
      overrideControl = `
        <select class="form-select form-select-sm hospital-override-select" style="width:auto;display:inline-block;" data-hospital-id="${h.hospital_id}" data-module-key="${escapeHtml(moduleKey)}">
          <option value="inherit" ${current === 'inherit' ? 'selected' : ''}>Inherit from plan</option>
          <option value="enable" ${current === 'enable' ? 'selected' : ''}>Force enabled</option>
          <option value="disable" ${current === 'disable' ? 'selected' : ''}>Force disabled</option>
        </select>`;
    }

    return `<li class="d-flex justify-content-between align-items-center py-1">
      <span>${escapeHtml(h.hospital_name)} <span class="text-body-secondary small">(${planLabel})</span></span>
      ${overrideControl}
    </li>`;
  }

  function openFeatureDetail(key) {
    const modalEl = document.getElementById('featureDetailModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    document.getElementById('featureDetailBody').innerHTML = '<div class="text-center py-4 text-body-secondary">Loading…</div>';
    modal.show();

    fetch(`/super-admin/features/${key}`)
      .then(res => res.json())
      .then(data => {
        document.getElementById('featureDetailTitle').textContent = data.module.name;
        const dependsOnHtml = data.dependsOn.length
          ? data.dependsOn.map(d => `<span class="badge bg-label-secondary me-1">${escapeHtml(d.name)}</span>`).join('')
          : '<span class="text-body-secondary">None</span>';
        const dependentsHtml = data.dependents.length
          ? data.dependents.map(d => `<span class="badge bg-label-secondary me-1">${escapeHtml(d.name)}</span>`).join('')
          : '<span class="text-body-secondary">None</span>';
        const assignedHtml = data.hospitals.assigned.length
          ? data.hospitals.assigned.map(h => hospitalRowHtml(h, key)).join('')
          : '<li class="text-body-secondary">None</li>';
        const restrictedHtml = data.hospitals.restricted.length
          ? data.hospitals.restricted.map(h => hospitalRowHtml(h, key)).join('')
          : '<li class="text-body-secondary">None</li>';

        document.getElementById('featureDetailBody').innerHTML = `
          <p class="text-body-secondary">${escapeHtml(data.module.description || '')}</p>
          <div class="row g-4">
            <div class="col-md-5">
              <h6 class="mb-2">Depends On</h6>
              <div class="mb-4">${dependsOnHtml}</div>
              <h6 class="mb-2">Required By (Dependents)</h6>
              <div>${dependentsHtml}</div>
            </div>
            <div class="col-md-7">
              <h6 class="mb-2">Hospitals With Access</h6>
              <ul class="list-unstyled mb-4">${assignedHtml}</ul>
              <h6 class="mb-2">Hospitals Restricted</h6>
              <ul class="list-unstyled mb-0">${restrictedHtml}</ul>
            </div>
          </div>
        `;
      })
      .catch(() => {
        document.getElementById('featureDetailBody').innerHTML = '<div class="text-center py-4 text-danger">Failed to load feature detail.</div>';
      });
  }

  document.getElementById('featureDetailBody').addEventListener('change', function (e) {
    const select = e.target.closest('.hospital-override-select');
    if (!select) return;

    const hospitalId = select.dataset.hospitalId;
    const moduleKey = select.dataset.moduleKey;
    const value = select.value;

    fetch(`/super-admin/features/${moduleKey}/hospitals/${hospitalId}/override`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ value }),
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          openFeatureDetail(moduleKey);
        } else {
          alert(result.message || 'Could not update the override.');
          openFeatureDetail(moduleKey);
        }
      })
      .catch(() => alert('Could not update the override.'));
  });

  loadTable();
  document.getElementById('applyBtn').addEventListener('click', loadTable);
  document.getElementById('filterSearch').addEventListener('keypress', function (e) { if (e.key === 'Enter') loadTable(); });
});
</script>
@endsection
