@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Scheme Billing - HMS')

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
              <h4 class="mb-0">{{ $totalClaims }}</h4>
              <p class="mb-0">Total Claims</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-building-bank icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $pendingClaims }}</h4>
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
              <h4 class="mb-0">{{ $currency }} {{ number_format($approvedAmount, 2) }}</h4>
              <p class="mb-0">Approved Amount</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $settledClaims }}</h4>
              <p class="mb-0">Settled</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-receipt icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Scheme Bills Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Government Scheme Billing</h5>
    <div class="d-flex gap-2 align-items-center">
      <select id="schemeFilter" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Schemes</option>
        <option value="pmjay">PMJAY</option>
        <option value="ayushman">Ayushman Bharat</option>
        <option value="cghs">CGHS</option>
        <option value="esi">ESI</option>
        <option value="state_scheme">State Scheme</option>
        <option value="other">Other</option>
      </select>
      <select id="statusFilter" class="form-select form-select-sm" style="width:140px;">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="submitted">Submitted</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="settled">Settled</option>
      </select>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSchemeAdd">
        <i class="icon-base ti tabler-plus me-1"></i> New Scheme Bill
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="schemesTable">
      <thead>
        <tr>
          <th></th>
          <th>Bill No</th>
          <th>Patient</th>
          <th>Scheme</th>
          <th>Package</th>
          <th>Package Rate</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Scheme Bill Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSchemeAdd" data-bs-scroll="true">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Scheme Bill</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('billing.schemes.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
            <option value="">Select Patient</option>
            @foreach ($patients as $p)
            <option value="{{ $p->id }}" @selected(old('patient_id')==$p->id)>{{ $p->name }} ({{ $p->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Linked Invoice</label>
          <select class="form-select @error('invoice_id') is-invalid @enderror" name="invoice_id">
            <option value="">None</option>
            @foreach ($invoices as $inv)
            <option value="{{ $inv->id }}" @selected(old('invoice_id')==$inv->id)>{{ $inv->invoice_no }}</option>
            @endforeach
          </select>
          @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Scheme *</label>
            <select class="form-select @error('scheme') is-invalid @enderror" name="scheme" required>
              <option value="pmjay" @selected(old('scheme','pmjay')=='pmjay')>PMJAY</option>
              <option value="ayushman" @selected(old('scheme')=='ayushman')>Ayushman Bharat</option>
              <option value="cghs" @selected(old('scheme')=='cghs')>CGHS</option>
              <option value="esi" @selected(old('scheme')=='esi')>ESI</option>
              <option value="state_scheme" @selected(old('scheme')=='state_scheme')>State Scheme</option>
              <option value="other" @selected(old('scheme')=='other')>Other</option>
            </select>
            @error('scheme') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Custom Scheme Name</label>
            <input type="text" class="form-control @error('scheme_name') is-invalid @enderror" name="scheme_name" value="{{ old('scheme_name') }}" />
            @error('scheme_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Beneficiary ID</label>
            <input type="text" class="form-control @error('beneficiary_id') is-invalid @enderror" name="beneficiary_id" placeholder="PMJAY ID / ABHA" value="{{ old('beneficiary_id') }}" />
            @error('beneficiary_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Package Code</label>
            <input type="text" class="form-control @error('package_code') is-invalid @enderror" name="package_code" value="{{ old('package_code') }}" />
            @error('package_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-12 mb-4">
            <label class="form-label">Package Name</label>
            <input type="text" class="form-control @error('package_name') is-invalid @enderror" name="package_name" value="{{ old('package_name') }}" />
            @error('package_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-12 mb-4">
            <label class="form-label">ICD-10 Diagnosis Code</label>
            <input type="text" class="form-control @error('icd10_code') is-invalid @enderror" name="icd10_code" value="{{ old('icd10_code') }}" placeholder="e.g. J18.9" />
            @error('icd10_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Package Rate ({{ $currency }}) *</label>
            <input type="number" step="0.01" min="0" class="form-control @error('package_rate') is-invalid @enderror" name="package_rate" value="{{ old('package_rate') }}" required />
            @error('package_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Actual Bill ({{ $currency }})</label>
            <input type="number" step="0.01" min="0" class="form-control @error('actual_bill') is-invalid @enderror" name="actual_bill" value="{{ old('actual_bill') }}" />
            @error('actual_bill') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Status Update Modal --}}
  <div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Claim Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="statusForm">
            <input type="hidden" id="status_scheme_id" />
            <div class="mb-4">
              <label class="form-label">Status *</label>
              <select class="form-select" id="status_value" required>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="settled">Settled</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Scheme Payment ({{ $currency }})</label>
              <input type="number" step="0.01" class="form-control" id="status_scheme_payment" />
            </div>
            <div class="mb-4">
              <label class="form-label">Rejection Reason / Notes</label>
              <textarea class="form-control" id="status_rejection_reason" rows="3"></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ti tabler-check me-1"></i> Update
              </button>
              <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
let schemesTable;
const currency = '{{ $currency }}';

function statusColor(s) {
  return { draft: 'secondary', submitted: 'info', approved: 'success', rejected: 'danger', settled: 'success' }[s] || 'secondary';
}

function schemeLabel(s) {
  return { pmjay: 'PMJAY', ayushman: 'Ayushman', cghs: 'CGHS', esi: 'ESI', state_scheme: 'State Scheme', other: 'Other' }[s] || s;
}

document.addEventListener('DOMContentLoaded', function () {
  schemesTable = $('#schemesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('billing.schemes.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'scheme_bill_no', name: 'scheme_bill_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'scheme', name: 'scheme', render: d => `<span class="badge bg-label-primary">${schemeLabel(d)}</span>` },
      { data: 'package_name', name: 'package_name', defaultContent: '—' },
      { data: 'package_rate', name: 'package_rate', render: d => currency + ' ' + parseFloat(d).toLocaleString() },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColor(d)}">${d}</span>` },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  document.getElementById('schemeFilter').addEventListener('change', function () {
    schemesTable.column(3).search(this.value).draw();
  });
  document.getElementById('statusFilter').addEventListener('change', function () {
    schemesTable.column(6).search(this.value).draw();
  });
});

function openStatus(id) {
  fetch(`/billing/schemes/${id}`)
    .then(res => res.json())
    .then(d => {
      document.getElementById('status_scheme_id').value = id;
      document.getElementById('status_value').value = d.status;
      document.getElementById('status_scheme_payment').value = d.scheme_payment;
      document.getElementById('status_rejection_reason').value = d.rejection_reason || '';
      new bootstrap.Modal(document.getElementById('statusModal')).show();
    });
}

document.getElementById('statusForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('status_scheme_id').value;
  fetch(`/billing/schemes/${id}/status`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      status: document.getElementById('status_value').value,
      scheme_payment: document.getElementById('status_scheme_payment').value,
      rejection_reason: document.getElementById('status_rejection_reason').value,
    })
  }).then(res => res.json()).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
    schemesTable.ajax.reload(null, false);
  });
});

function deleteScheme(id) {
  confirmDelete(`/billing/schemes/${id}`, 'Scheme Bill', function() {
    schemesTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasSchemeAdd')).show();
});
@endif
</script>
@endsection

@endsection
