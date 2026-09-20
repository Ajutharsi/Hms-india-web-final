@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

@section('title', 'Billing Report - HMS')

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
    <h4 class="mb-1">Billing Report</h4>
    <p class="text-muted mb-0">Invoice & payment summary</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('reports.billing.pdf') }}?{{ http_build_query(request()->all()) }}"
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
    <form method="GET" action="{{ route('reports.billing') }}">
      <div class="row g-4">
        <div class="col-md-3">
          <label class="form-label">From Date</label>
          <input type="date" class="form-control" name="from_date"
            value="{{ request('from_date') }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label">To Date</label>
          <input type="date" class="form-control" name="to_date"
            value="{{ request('to_date') }}" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="">All</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="d-flex gap-2 w-100">
            <button type="submit" class="btn btn-primary flex-grow-1">
              <i class="icon-base ti tabler-search me-1"></i> Filter
            </button>
            <a href="{{ route('reports.billing') }}" class="btn btn-label-secondary">
              <i class="icon-base ti tabler-x"></i>
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Stats --}}
<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <span class="text-heading">Total Invoices</span>
            <h4 class="mb-0 mt-1">{{ $invoices->count() }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-primary avatar">
            <i class="icon-base ti tabler-file-invoice icon-26px"></i>
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
            <span class="text-heading">Total Amount</span>
            <h4 class="mb-0 mt-1">{{ $currency }} {{ number_format($totalAmount, 2) }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-info avatar">
            <i class="icon-base ti tabler-currency-dollar icon-26px"></i>
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
            <span class="text-heading">Total Paid</span>
            <h4 class="mb-0 mt-1 text-success">{{ $currency }} {{ number_format($totalPaid, 2) }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-success avatar">
            <i class="icon-base ti tabler-checks icon-26px"></i>
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
            <span class="text-heading">Outstanding</span>
            <h4 class="mb-0 mt-1 text-danger">{{ $currency }} {{ number_format($totalBalance, 2) }}</h4>
          </div>
          <span class="avatar-initial rounded bg-label-danger avatar">
            <i class="icon-base ti tabler-alert-circle icon-26px"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Invoice List</h5>
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
    <table class="table border-top" id="billingReportTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Invoice No</th>
          <th>Patient</th>
          <th>Date</th>
          <th>Total ({{ $currency }})</th>
          <th>Paid ({{ $currency }})</th>
          <th>Balance ({{ $currency }})</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoices as $index => $invoice)
        @php
          $statusColors = [
            'draft'     => 'secondary',
            'sent'      => 'info',
            'paid'      => 'success',
            'partial'   => 'warning',
            'overdue'   => 'danger',
            'cancelled' => 'secondary',
          ];
        @endphp
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>
            <a href="{{ route('billing.invoices.show', $invoice->id) }}"
              class="fw-medium text-primary">
              {{ $invoice->invoice_no }}
            </a>
          </td>
          <td>
            <div>
              <strong>{{ $invoice->patient->name }}</strong>
              <br><small class="text-muted">{{ $invoice->patient->mrn }}</small>
            </div>
          </td>
          <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
          <td class="fw-medium">{{ number_format($invoice->total, 2) }}</td>
          <td class="text-success fw-medium">{{ number_format($invoice->paid_amount, 2) }}</td>
          <td class="{{ $invoice->balance > 0 ? 'text-danger' : 'text-success' }} fw-medium">
            {{ number_format($invoice->balance, 2) }}
          </td>
          <td>
            <span class="badge bg-label-{{ $statusColors[$invoice->status] ?? 'secondary' }}">
              {{ ucfirst($invoice->status) }}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="fw-bold">
          <td colspan="4" class="text-end">Totals:</td>
          <td>{{ number_format($totalAmount, 2) }}</td>
          <td class="text-success">{{ number_format($totalPaid, 2) }}</td>
          <td class="text-danger">{{ number_format($totalBalance, 2) }}</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@section('page-script')
<script>
let dtTable;

document.addEventListener('DOMContentLoaded', function () {
  dtTable = $('#billingReportTable').DataTable({
    pageLength: 25,
    order: [[0, 'asc']],
    dom: 'Bfrtip',
    buttons: [
      { extend: 'copy',  className: 'd-none' },
      { extend: 'csv',   className: 'd-none', filename: 'billing-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'excel', className: 'd-none', filename: 'billing-report-{{ now()->format("Y-m-d") }}' },
      { extend: 'print', className: 'd-none', title: 'HMS — Billing Report {{ now()->format("d M Y") }}' },
    ],
  });
});

function exportTable(type) {
  dtTable.button('.buttons-' + type).trigger();
}
</script>
@endsection

@endsection