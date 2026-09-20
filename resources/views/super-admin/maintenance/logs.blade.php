@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Log Viewer - Platform Maintenance - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-style')
<style>
  .log-message { white-space: pre-wrap; font-family: monospace; font-size: 0.8125rem; max-width: 600px; }
  .not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Platform Maintenance', 'url' => route('super-admin.maintenance.index')], ['label' => 'Log Viewer']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Application Log Viewer</h4>
    <p class="mb-0 text-body-secondary">The most recent portion of <code>storage/logs/laravel.log</code>. Sensitive values (passwords, tokens, secrets) are masked.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('super-admin.maintenance.logs.export-csv') }}" id="exportCsvLink" class="btn btn-label-secondary"><i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export CSV</a>
    <a href="{{ route('super-admin.maintenance.logs.print') }}" id="printLink" target="_blank" class="btn btn-label-secondary"><i class="icon-base ti tabler-printer me-1"></i> Print</a>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Search message or channel..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterLevel">
          <option value="">All Levels</option>
          @foreach(['EMERGENCY','ALERT','CRITICAL','ERROR','WARNING','NOTICE','INFO','DEBUG'] as $lvl)
          <option value="{{ $lvl }}">{{ ucfirst(strtolower($lvl)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterFrom" title="From date" />
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterTo" title="To date" />
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-primary w-100" id="applyBtn"><i class="icon-base ti tabler-filter"></i></button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom"><h5 class="mb-0">Log Entries</h5></div>
  <div class="table-responsive">
    <table class="table table-sm" id="logsTable" style="width:100%">
      <thead><tr><th>Timestamp</th><th>Level</th><th>Channel</th><th>Message</th></tr></thead>
    </table>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
  function buildQuery() {
    const p = new URLSearchParams();
    const level = document.getElementById('filterLevel').value;
    const from = document.getElementById('filterFrom').value;
    const to = document.getElementById('filterTo').value;
    const search = document.getElementById('filterSearch').value;
    if (level) p.set('level', level);
    if (from) p.set('from_date', from);
    if (to) p.set('to_date', to);
    if (search) p.set('search', search);
    return p.toString();
  }

  let table;

  function loadTable() {
    const q = buildQuery();
    if (table) { table.destroy(); }
    table = $('#logsTable').DataTable({
      ajax: { url: '{{ route('super-admin.maintenance.logs.data') }}' + (q ? '?' + q : ''), dataSrc: 'data' },
      columns: [
        { data: 'timestamp' },
        { data: 'level', render: (d) => {
            const colors = { EMERGENCY:'danger', ALERT:'danger', CRITICAL:'danger', ERROR:'danger', WARNING:'warning', NOTICE:'info', INFO:'info', DEBUG:'secondary' };
            return `<span class="badge bg-label-${colors[d] || 'secondary'}">${escapeHtml(d)}</span>`;
          }, orderable: false },
        { data: 'channel' },
        { data: 'message', render: (d) => `<div class="log-message">${escapeHtml(d)}</div>`, orderable: false },
      ],
      order: [[0, 'desc']],
      pageLength: 25,
      language: { emptyTable: 'Not tracked yet — no log entries match.', zeroRecords: 'Not tracked yet — no log entries match.' },
    });

    const csvLink = document.getElementById('exportCsvLink');
    const printLink = document.getElementById('printLink');
    const base = '{{ route('super-admin.maintenance.logs.export-csv') }}';
    const baseP = '{{ route('super-admin.maintenance.logs.print') }}';
    csvLink.href = base + (q ? '?' + q : '');
    printLink.href = baseP + (q ? '?' + q : '');
  }

  loadTable();
  document.getElementById('applyBtn').addEventListener('click', loadTable);
  document.getElementById('filterSearch').addEventListener('keypress', function (e) { if (e.key === 'Enter') loadTable(); });
});
</script>
@endsection
