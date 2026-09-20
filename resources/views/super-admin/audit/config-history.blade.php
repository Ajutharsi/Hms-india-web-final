@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Configuration History - Audit Centre - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Audit & Activity Centre', 'url' => route('super-admin.audit.index')], ['label' => 'Configuration History']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Configuration History</h4>
    <p class="mb-0 text-body-secondary">Global Settings, Plan, and Subscription changes — previous value, new value, who, and when. Sensitive values are masked.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('super-admin.audit.export-csv', 'config-history') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export CSV</a>
    <a href="{{ route('super-admin.audit.print', 'config-history') }}" target="_blank" class="btn btn-label-secondary"><i class="icon-base ti tabler-printer me-1"></i> Print</a>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <select class="form-select" id="filterModule">
          <option value="">All Modules</option>
          <option value="platform_settings">Platform Settings</option>
          <option value="subscription">Subscription</option>
          <option value="plan">Plan</option>
        </select>
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterFrom" title="From date" />
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterTo" title="To date" />
      </div>
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Search..." />
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom"><h5 class="mb-0">Changes</h5></div>
  <div class="table-responsive">
    <table class="table" id="configHistoryTable" style="width:100%">
      <thead><tr>@foreach($columns as $c)<th>{{ $c['title'] }}</th>@endforeach</tr></thead>
    </table>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const columns = @json($columns);
  const orderableColumns = @json($orderableColumns);
  const isOrderable = (key) => orderableColumns.length === 0 || orderableColumns.includes(key);
  const dtColumns = columns.map(c => ({ data: c.data, name: c.data, orderable: isOrderable(c.data) }));

  const table = $('#configHistoryTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    ajax: {
      url: '{{ route('super-admin.audit.config-history.data') }}',
      type: 'GET',
      data: function (d) {
        d.module = document.getElementById('filterModule').value;
        d.from_date = document.getElementById('filterFrom').value;
        d.to_date = document.getElementById('filterTo').value;
        d.search = document.getElementById('filterSearch').value;
      }
    },
    columns: dtColumns,
    pageLength: 25,
    order: [[0, 'desc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>', emptyTable: 'Not tracked yet.', zeroRecords: 'Not tracked yet.' },
  });

  ['filterModule', 'filterFrom', 'filterTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => table.draw());
  });
  let searchDebounce;
  document.getElementById('filterSearch').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => table.draw(), 300);
  });
});
</script>
@endsection
