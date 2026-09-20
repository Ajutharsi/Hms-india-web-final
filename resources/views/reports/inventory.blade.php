@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Inventory Report - HMS')

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

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-6">
  <div>
    <h4 class="mb-1">Inventory Report</h4>
    <p class="text-muted mb-0">Drug stock levels & expiry alerts</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('reports.inventory.pdf') }}?{{ http_build_query(request()->all()) }}"
      class="btn btn-danger">
      <i class="icon-base ti tabler-file-type-pdf me-1"></i> Export PDF
    </a>
    <a href="{{ route('reports.index') }}" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

{{-- Filters --}}
<div class="card mb-6">
  <div class="card-header">
    <h5 class="mb-0"><i class="icon-base ti tabler-filter me-2 text-primary"></i>Filters</h5>
  </div>
  <div class="card-body">
    <form method="GET" action="{{ route('reports.inventory') }}">
      <div class="row g-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Filter By</label>
          <select class="form-select" name="filter">
            <option value="">All Drugs</option>
            <option value="low_stock" {{ request('filter') === 'low_stock' ? 'selected' : '' }}>
              Low Stock
            </option>
            <option value="expiry" {{ request('filter') === 'expiry' ? 'selected' : '' }}>
              Near Expiry (30 days)
            </option>
            <option value="expired" {{ request('filter') === 'expired' ? 'selected' : '' }}>
              Expired
            </option>
          </select>
        </div>
        <div class="col-md-4">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base ti tabler-search me-1"></i> Filter
            </button>
            <a href="{{ route('reports.inventory') }}" class="btn btn-label-secondary">
              <i class="icon-base ti tabler-x me-1"></i> Clear
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Quick Filter Buttons --}}
<div class="d-flex gap-2 mb-6">
  <a href="{{ route('reports.inventory') }}"
    class="btn btn-sm {{ !request('filter') ? 'btn-primary' : 'btn-label-secondary' }}">
    All Drugs ({{ $drugs->count() }})
  </a>
  <a href="{{ route('reports.inventory') }}?filter=low_stock"
    class="btn btn-sm {{ request('filter') === 'low_stock' ? 'btn-danger' : 'btn-label-danger' }}">
    <i class="icon-base ti tabler-alert-triangle me-1"></i>
    Low Stock ({{ \App\Models\Drug::whereColumn('current_stock', '<=', 'min_stock_level')->count() }})
  </a>
  <a href="{{ route('reports.inventory') }}?filter=expiry"
    class="btn btn-sm {{ request('filter') === 'expiry' ? 'btn-warning' : 'btn-label-warning' }}">
    <i class="icon-base ti tabler-clock me-1"></i>
    Near Expiry ({{ \App\Models\Drug::whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays($hospitalSettings->near_expiry_days ?: 30))->whereDate('expiry_date', '>=', now())->count() }})
  </a>
  <a href="{{ route('reports.inventory') }}?filter=expired"
    class="btn btn-sm {{ request('filter') === 'expired' ? 'btn-dark' : 'btn-label-secondary' }}">
    <i class="icon-base ti tabler-x me-1"></i>
    Expired ({{ \App\Models\Drug::whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->count() }})
  </a>
</div>

{{-- Stats --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Total Drugs</span>
            <h4 class="mb-0 mt-1">{{ $drugs->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-primary avatar">
            <i class="icon-base ti tabler-pill icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Low Stock</span>
            <h4 class="mb-0 mt-1 text-danger">{{ $drugs->filter(fn($d) => $d->isLowStock())->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-danger avatar">
            <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Near Expiry</span>
            <h4 class="mb-0 mt-1 text-warning">{{ $drugs->filter(fn($d) => $d->isNearExpiry())->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-warning avatar">
            <i class="icon-base ti tabler-clock icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Expired</span>
            <h4 class="mb-0 mt-1 text-dark">{{ $drugs->filter(fn($d) => $d->isExpired())->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-secondary avatar">
            <i class="icon-base ti tabler-x icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Drug Inventory</h5>
    <div class="d-flex gap-2">
      <button onclick="exportTable('copy')" class="btn btn-sm btn-label-secondary">
        <i class="icon-base ti tabler-copy me-1"></i> Copy
      </button>
      <button onclick="exportTable('csv')" class="btn btn-sm btn-label-info">
        <i class="icon-base ti tabler-file-text me-1"></i> CSV
      </button>
      <button onclick="exportTable('excel')" class="btn btn-sm btn-label-success">
        <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Excel
      </button>
      <button onclick="exportTable('print')" class="btn btn-sm btn-label-primary">
        <i class="icon-base ti tabler-printer me-1"></i> Print
      </button>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="inventoryReportTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Code</th>
          <th>Drug Name</th>
          <th>Category</th>
          <th>Form</th>
          <th>Current Stock</th>
          <th>Min Stock</th>
          <th>Unit Price</th>
          <th>Expiry Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($drugs as $index => $drug)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td><span class="badge bg-label-primary">{{ $drug->drug_code }}</span></td>
          <td>
            <div>
              <strong>{{ $drug->drug_name }}</strong>
              @if($drug->generic_name)
              <br><small class="text-muted">{{ $drug->generic_name }}</small>
              @endif
            </div>
          </td>
          <td><span class="badge bg-label-info">{{ $drug->category }}</span></td>
          <td>{{ $drug->form }}</td>
          <td>
            @if($drug->isExpired())
              <span class="text-dark fw-bold">{{ $drug->current_stock }} <small>(Expired)</small></span>
            @elseif($drug->isLowStock())
              <span class="text-danger fw-bold">
                {{ $drug->current_stock }}
                <i class="icon-base ti tabler-alert-triangle ms-1"></i>
              </span>
            @else
              <span class="text-success fw-bold">{{ $drug->current_stock }}</span>
            @endif
          </td>
          <td>{{ $drug->min_stock_level }}</td>
          <td>LKR {{ number_format($drug->unit_price, 2) }}</td>
          <td>
            @if($drug->expiry_date)
              @if($drug->isExpired())
                <span class="badge bg-label-danger">{{ $drug->expiry_date->format('d M Y') }}</span>
              @elseif($drug->isNearExpiry())
                <span class="badge bg-label-warning">{{ $drug->expiry_date->format('d M Y') }}</span>
              @else
                {{ $drug->expiry_date->format('d M Y') }}
              @endif
            @else
              <span class="text-muted">N/A</span>
            @endif
          </td>
          <td>
            <span class="badge bg-label-{{ $drug->status === 'active' ? 'success' : 'danger' }}">
              {{ ucfirst($drug->status) }}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@section('page-script')
<script>
let dtTable;

document.addEventListener('DOMContentLoaded', function () {
  dtTable = $('#inventoryReportTable').DataTable({
    pageLength: 25,
    order: [[0, 'asc']],
    dom: 'Bfrtip',
    buttons: [
      { extend: 'copy',  className: 'd-none' },
      { extend: 'csv',   className: 'd-none', filename: 'inventory-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'excel', className: 'd-none', filename: 'inventory-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'print', className: 'd-none', title: 'HMS — Inventory Report {{ now()->format("d M Y") }}' },
    ],
  });
});

function exportTable(type) {
  dtTable.button('.buttons-' + type).trigger();
}
</script>
@endsection

@endsection