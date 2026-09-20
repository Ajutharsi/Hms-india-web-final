@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Notification Centre - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-style')
<style>
  .notif-message { white-space: normal; max-width: 480px; }
  .notif-row-unread { font-weight: 600; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Notification Centre']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Notification Centre</h4>
    <p class="mb-0 text-body-secondary">In-app platform alerts — subscriptions expiring, trials ending, new support tickets, and failed-job thresholds.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-label-primary" id="markAllReadBtn"><i class="icon-base ti tabler-checks me-1"></i> Mark All Read</button>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Search title or message..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All</option>
          <option value="unread">Unread</option>
          <option value="read">Read</option>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="filterType">
          <option value="">All Types</option>
          <option value="App\Notifications\SubscriptionExpiringSoon">Subscription Expiring</option>
          <option value="App\Notifications\HospitalTrialEnding">Trial Ending</option>
          <option value="App\Notifications\NewSupportTicketCreated">New Support Ticket</option>
          <option value="App\Notifications\FailedJobThresholdExceeded">Failed Job Threshold</option>
        </select>
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterFrom" title="From date" />
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-primary w-100" id="applyBtn"><i class="icon-base ti tabler-filter"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom"><h5 class="mb-0">Notifications</h5></div>
  <div class="table-responsive">
    <table class="table" id="notificationsTable" style="width:100%">
      <thead><tr><th>Status</th><th>Title</th><th>Message</th><th>Received</th><th>Actions</th></tr></thead>
    </table>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
  const levelColors = { info: 'info', warning: 'warning', critical: 'danger' };

  function buildQuery() {
    const p = new URLSearchParams();
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    const from = document.getElementById('filterFrom').value;
    const search = document.getElementById('filterSearch').value;
    if (status) p.set('status', status);
    if (type) p.set('type', type);
    if (from) p.set('from_date', from);
    if (search) p.set('search', search);
    return p.toString();
  }

  let table;

  function loadTable() {
    const q = buildQuery();
    if (table) { table.destroy(); }
    table = $('#notificationsTable').DataTable({
      ajax: { url: '{{ route('super-admin.notifications.data') }}' + (q ? '?' + q : ''), dataSrc: 'data' },
      columns: [
        { data: 'read', render: (d) => d ? '<span class="badge bg-label-secondary">Read</span>' : '<span class="badge bg-label-primary">Unread</span>', orderable: false },
        { data: 'title', render: (d, t, row) => `<span class="badge bg-label-${levelColors[row.level] || 'secondary'} me-1"><i class="icon-base ti tabler-bell"></i></span> ${escapeHtml(d)}` },
        { data: 'message', render: (d, t, row) => row.url ? `<a href="${row.url}" class="notif-message">${escapeHtml(d)}</a>` : `<span class="notif-message">${escapeHtml(d)}</span>` },
        { data: 'created_at' },
        {
          data: 'id', orderable: false,
          render: (id, t, row) => `
            <div class="d-flex gap-2">
              ${row.read ? '' : `<button type="button" class="btn btn-sm btn-icon btn-label-primary mark-read-btn" data-id="${id}" title="Mark read"><i class="icon-base ti tabler-check"></i></button>`}
              <button type="button" class="btn btn-sm btn-icon btn-label-danger delete-btn" data-id="${id}" title="Remove"><i class="icon-base ti tabler-trash"></i></button>
            </div>`,
        },
      ],
      order: [[3, 'desc']],
      pageLength: 25,
      language: { emptyTable: 'No notifications match.', zeroRecords: 'No notifications match.' },
      rowCallback: (row, data) => { if (!data.read) row.classList.add('notif-row-unread'); },
    });
  }

  document.getElementById('notificationsTable').addEventListener('click', function (e) {
    const markBtn = e.target.closest('.mark-read-btn');
    const delBtn = e.target.closest('.delete-btn');

    if (markBtn) {
      fetch(`/super-admin/notifications/${markBtn.dataset.id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
      }).then(() => loadTable());
    }

    if (delBtn) {
      fetch(`/super-admin/notifications/${delBtn.dataset.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
      }).then(() => loadTable());
    }
  });

  document.getElementById('markAllReadBtn').addEventListener('click', function () {
    fetch('{{ route('super-admin.notifications.mark-all-read') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
    }).then(() => loadTable());
  });

  loadTable();
  document.getElementById('applyBtn').addEventListener('click', loadTable);
  document.getElementById('filterSearch').addEventListener('keypress', function (e) { if (e.key === 'Enter') loadTable(); });
});
</script>
@endsection
