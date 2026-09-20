@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Invoices - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('page-style')
<style>
#invoicesTable tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
  cursor: pointer;
}
</style>
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ \App\Models\Invoice::count() }}</h4>
              <p class="mb-0">Total Invoices</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-file-invoice icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format(\App\Models\Invoice::where('status','paid')->sum('total'), 2) }}</h4>
              <p class="mb-0">Paid</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-checks icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format(\App\Models\Invoice::whereIn('status',['sent','partial','overdue'])->sum('balance'), 2) }}</h4>
              <p class="mb-0">Outstanding</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ \App\Models\Invoice::where('status','overdue')->count() }}</h4>
              <p class="mb-0">Overdue</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-alert-circle icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Invoices Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Invoices</h5>
    <a href="{{ route('billing.invoices.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> New Invoice
    </a>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="invoicesTable">
      <thead>
        <tr>
          <th></th>
          <th>Invoice No</th>
          <th>Patient</th>
          <th>Date</th>
          <th>Due Date</th>
          <th>Total ({{ $currency }})</th>
          <th>Paid ({{ $currency }})</th>
          <th>Balance ({{ $currency }})</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@section('page-script')
<script>
const currency = '{{ $currency }}';
document.addEventListener('DOMContentLoaded', function () {
  $('#invoicesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('billing.invoices.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'invoice_no', name: 'invoice_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'invoice_date', name: 'invoice_date' },
      { data: 'due_date', name: 'due_date', defaultContent: '—' },
      { data: 'total_fmt', name: 'total' },
      { data: 'paid_amount_fmt', name: 'paid_amount' },
      { data: 'balance_fmt', name: 'balance' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[3, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    },
    createdRow: function(row, data) {
      $(row).css('cursor', 'pointer');
      $(row).on('click', function(e) {
        if ($(e.target).closest('.d-flex').length) return;
        window.location.href = '/billing/invoices/' + data.id;
      });
    }
  });
});
</script>
@endsection

@endsection