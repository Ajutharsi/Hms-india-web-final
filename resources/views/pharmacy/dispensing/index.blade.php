@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Dispensing - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('page-style')
<style>
#dispensingTable tbody tr:hover {
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
          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ \App\Models\DrugDispensing::count() }}</h4>
              <p class="mb-0">Total Dispensing</p>
            </div>
            <span class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-clipboard-list icon-26px"></i>
              </span>
            </span>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ \App\Models\DrugDispensing::where('status','dispensed')->count() }}</h4>
              <p class="mb-0">Dispensed</p>
            </div>
            <span class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-checks icon-26px"></i>
              </span>
            </span>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ \App\Models\DrugDispensing::where('status','pending')->count() }}</h4>
              <p class="mb-0">Pending</p>
            </div>
            <span class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </span>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h4 class="mb-0">LKR {{ number_format(\App\Models\DrugDispensing::whereDate('dispensed_date', today())->sum('total_amount'), 2) }}</h4>
              <p class="mb-0">Today's Revenue</p>
            </div>
            <span class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="icon-base ti tabler-currency-dollar icon-26px"></i>
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Dispensing Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Dispensing Records</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('pharmacy.drugs.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-pill me-1"></i> Drug Master
      </a>
      <a href="{{ route('pharmacy.dispensing.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i> New Dispensing
      </a>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table border-top" id="dispensingTable">
      <thead>
        <tr>
          <th></th>
          <th>Dispensing No</th>
          <th>Patient</th>
          <th>Prescription</th>
          <th>Items</th>
          <th>Date</th>
          <th>Total (LKR)</th>
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
  const table = $('#dispensingTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('pharmacy.dispensing.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'dispensing_no', name: 'dispensing_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'prescription_badge', name: 'prescription_badge', orderable: false },
      { data: 'items_badge', name: 'items_badge', orderable: false },
      { data: 'dispensed_date', name: 'dispensed_date' },
      { data: 'total_amount_fmt', name: 'total_amount' },
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
        window.location.href = '/pharmacy/dispensing/' + data.id;
      });
    }
  });
});
</script>
@endsection

@endsection