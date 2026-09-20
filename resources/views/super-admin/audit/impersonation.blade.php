@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Impersonation History - Audit Centre - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Audit & Activity Centre', 'url' => route('super-admin.audit.index')], ['label' => 'Impersonation History']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Impersonation History</h4>
    <p class="mb-0 text-body-secondary">Every "Login as Hospital Admin" session — who started it, which hospital/user, and when it ended.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('super-admin.audit.export-csv', 'impersonation') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export CSV</a>
    <a href="{{ route('super-admin.audit.print', 'impersonation') }}" target="_blank" class="btn btn-label-secondary"><i class="icon-base ti tabler-printer me-1"></i> Print</a>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-4">
        <select class="form-select" id="filterHospital">
          <option value="">All Hospitals</option>
          @foreach($hospitals as $h)
          <option value="{{ $h->id }}">{{ $h->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterFrom" title="From date" />
      </div>
      <div class="col-md-3">
        <input type="date" class="form-control" id="filterTo" title="To date" />
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom"><h5 class="mb-0">Sessions</h5></div>
  <div class="table-responsive">
    <table class="table" id="impersonationTable" style="width:100%">
      <thead><tr>@foreach($columns as $c)<th>{{ $c['title'] }}</th>@endforeach<th>Actions</th></tr></thead>
    </table>
  </div>
</div>

{{-- View Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Impersonation Session Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detailsModalBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
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
  dtColumns.push({ data: 'actions', name: 'actions', orderable: false, searchable: false });

  const table = $('#impersonationTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    ajax: {
      url: '{{ route('super-admin.audit.impersonation.data') }}',
      type: 'GET',
      data: function (d) {
        d.hospital_id = document.getElementById('filterHospital').value;
        d.from_date = document.getElementById('filterFrom').value;
        d.to_date = document.getElementById('filterTo').value;
      }
    },
    columns: dtColumns,
    pageLength: 25,
    order: [[3, 'desc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>', emptyTable: 'Not tracked yet.', zeroRecords: 'Not tracked yet.' },
  });

  ['filterHospital', 'filterFrom', 'filterTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => table.draw());
  });

  const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
  $('#impersonationTable tbody').on('click', 'a[data-view-details]', function (e) {
    e.preventDefault();
    modal.show();
    fetch(this.href).then(r => r.json()).then(d => {
      document.getElementById('detailsModalBody').innerHTML = `
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>Super Admin</span><strong>${d.super_admin}</strong></div>
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>Target Hospital</span><strong>${d.hospital}</strong></div>
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>Target User</span><strong>${d.impersonated_user}</strong></div>
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>Start Time</span><strong>${d.started_at}</strong></div>
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>End Time</span><strong>${d.ended_at}</strong></div>
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>Duration</span><strong>${d.duration}</strong></div>
        <div class="info-row d-flex justify-content-between border-bottom py-2"><span>IP Address</span><strong>${d.ip_address}</strong></div>
        <div class="info-row d-flex justify-content-between py-2"><span>User Agent</span><strong class="text-end" style="max-width:60%;word-break:break-word;">${d.user_agent}</strong></div>
      `;
    });
  });
});
</script>
@endsection
