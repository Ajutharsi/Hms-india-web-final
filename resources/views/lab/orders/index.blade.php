@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Lab Orders - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('page-style')
<style>
#labOrdersTable tbody tr:hover {
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
              <h4 class="mb-0">{{ $totalLabOrders }}</h4>
              <p class="mb-0">Total Orders</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-clipboard-list icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $pendingLabOrders }}</h4>
              <p class="mb-0">Pending</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $processingLabOrders }}</h4>
              <p class="mb-0">Processing</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-loader icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $completedLabOrders }}</h4>
              <p class="mb-0">Completed</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Lab Orders Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Lab Orders</h5>
    <a href="{{ route('lab.orders.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> New Order
    </a>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="labOrdersTable">
      <thead>
        <tr>
          <th></th>
          <th>Order No</th>
          <th>Patient</th>
          <th>Doctor</th>
          <th>Tests</th>
          <th>Order Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const table = $('#labOrdersTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('lab.orders.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'order_no', name: 'order_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'doctor_name', name: 'doctor_name' },
      { data: 'tests_badge', name: 'tests_badge', orderable: false },
      { data: 'order_date', name: 'order_date' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[5, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    },
    createdRow: function(row, data) {
      $(row).css('cursor', 'pointer');
      $(row).on('click', function(e) {
        if ($(e.target).closest('.d-flex').length) return;
        window.location.href = '/lab/orders/' + data.id;
      });
    }
  });
});
</script>
@endsection

@endsection