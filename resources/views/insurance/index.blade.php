@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Insurance Claims - HMS')

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
        <div class="col-sm-6 col-lg-2p4" style="flex:0 0 20%;max-width:20%;">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $totalClaims }}</h4>
              <p class="mb-0">Total Claims</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-shield-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-2p4" style="flex:0 0 20%;max-width:20%;">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $pendingClaims }}</h4>
              <p class="mb-0">Pending</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-2p4" style="flex:0 0 20%;max-width:20%;">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $approvedClaims }}</h4>
              <p class="mb-0">Approved</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-2p4" style="flex:0 0 20%;max-width:20%;">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $rejectedClaims }}</h4>
              <p class="mb-0">Rejected</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-x icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-2p4" style="flex:0 0 20%;max-width:20%;">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format($totalApproved, 2) }}</h4>
              <p class="mb-0">Total Approved</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-currency-dollar icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Claims Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Insurance / TPA Claims</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasClaimAdd">
      <i class="icon-base ti tabler-plus me-1"></i> New Claim
    </button>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="claimsTable">
      <thead>
        <tr>
          <th></th>
          <th>Claim No</th>
          <th>Patient</th>
          <th>Insurance Company</th>
          <th>Claim Amount</th>
          <th>Approved Amount</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Claim Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasClaimAdd" data-bs-scroll="true">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Insurance Claim</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('insurance.store') }}" method="POST">
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
          <select class="form-select @error('invoice_id') is-invalid @enderror" name="invoice_id" id="claimInvoiceSelect">
            <option value="">None</option>
            @foreach ($invoices as $inv)
            <option value="{{ $inv->id }}" data-total="{{ $inv->total }}" @selected(old('invoice_id')==$inv->id)>{{ $inv->invoice_no }} ({{ number_format($inv->total, 2) }})</option>
            @endforeach
          </select>
          <small class="text-muted">Selecting an invoice auto-fills the claim amount below (still editable).</small>
          @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Insurance Company *</label>
            <input type="text" class="form-control @error('insurance_company') is-invalid @enderror" name="insurance_company" value="{{ old('insurance_company') }}" required />
            @error('insurance_company') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">TPA Name</label>
            <input type="text" class="form-control @error('tpa_name') is-invalid @enderror" name="tpa_name" value="{{ old('tpa_name') }}" />
            @error('tpa_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Policy Number *</label>
            <input type="text" class="form-control @error('policy_number') is-invalid @enderror" name="policy_number" value="{{ old('policy_number') }}" required />
            @error('policy_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Member ID</label>
            <input type="text" class="form-control @error('member_id') is-invalid @enderror" name="member_id" value="{{ old('member_id') }}" />
            @error('member_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Claim Amount ({{ $currency }}) *</label>
            <input type="number" step="0.01" class="form-control @error('claim_amount') is-invalid @enderror" name="claim_amount" id="claimAmountInput" value="{{ old('claim_amount') }}" required />
            @error('claim_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">ICD-10 Diagnosis Code</label>
            <input type="text" class="form-control @error('icd10_code') is-invalid @enderror" name="icd10_code" value="{{ old('icd10_code') }}" placeholder="e.g. J18.9" />
            @error('icd10_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Claim Type *</label>
            <select class="form-select @error('claim_type') is-invalid @enderror" name="claim_type" required>
              <option value="cashless" @selected(old('claim_type','cashless')=='cashless')>Cashless</option>
              <option value="reimbursement" @selected(old('claim_type')=='reimbursement')>Reimbursement</option>
            </select>
            @error('claim_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Admission Date</label>
            <input type="date" class="form-control @error('admission_date') is-invalid @enderror" name="admission_date" value="{{ old('admission_date') }}" />
            @error('admission_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Discharge Date</label>
            <input type="date" class="form-control @error('discharge_date') is-invalid @enderror" name="discharge_date" value="{{ old('discharge_date') }}" />
            @error('discharge_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Diagnosis</label>
          <textarea class="form-control @error('diagnosis') is-invalid @enderror" name="diagnosis" rows="2">{{ old('diagnosis') }}</textarea>
          @error('diagnosis') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Treatment Details</label>
          <textarea class="form-control @error('treatment_details') is-invalid @enderror" name="treatment_details" rows="2">{{ old('treatment_details') }}</textarea>
          @error('treatment_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <input type="hidden" id="status_claim_id" />
            <div class="mb-4">
              <label class="form-label">Status *</label>
              <select class="form-select" id="status_value" required>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="pre_auth_pending">Pre-Auth Pending</option>
                <option value="pre_auth_approved">Pre-Auth Approved</option>
                <option value="pre_auth_rejected">Pre-Auth Rejected</option>
                <option value="claim_submitted">Claim Submitted</option>
                <option value="approved">Approved</option>
                <option value="partially_approved">Partially Approved</option>
                <option value="rejected">Rejected</option>
                <option value="settled">Settled</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Approved Amount ({{ $currency }})</label>
              <input type="number" step="0.01" class="form-control" id="status_approved_amount" />
            </div>
            <div class="mb-4">
              <label class="form-label">Rejection / Notes</label>
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
let claimsTable;
const currency = '{{ $currency }}';

function statusColor(s) {
  const map = {
    draft: 'secondary', submitted: 'info', pre_auth_pending: 'warning',
    pre_auth_approved: 'primary', pre_auth_rejected: 'danger',
    claim_submitted: 'info', approved: 'success', partially_approved: 'warning',
    rejected: 'danger', settled: 'success',
  };
  return map[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  const claimInvoiceSelect = document.getElementById('claimInvoiceSelect');
  if (claimInvoiceSelect) {
    claimInvoiceSelect.addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      const total = opt ? opt.getAttribute('data-total') : null;
      if (total) {
        document.getElementById('claimAmountInput').value = parseFloat(total).toFixed(2);
      }
    });
  }

  claimsTable = $('#claimsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('insurance.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'claim_no', name: 'claim_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'insurance_company', name: 'insurance_company', render: (d, t, r) => `${d}${r.tpa_name ? '<br><small class="text-muted">TPA: '+r.tpa_name+'</small>' : ''}` },
      { data: 'claim_amount', name: 'claim_amount', render: d => currency + ' ' + parseFloat(d).toLocaleString() },
      { data: 'approved_amount', name: 'approved_amount', render: d => currency + ' ' + parseFloat(d).toLocaleString() },
      { data: 'status', name: 'status', render: d => `<span class="badge bg-label-${statusColor(d)}">${d.replace(/_/g,' ')}</span>` },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });
});

function openStatus(id) {
  fetch(`/insurance/${id}`)
    .then(res => res.json())
    .then(d => {
      document.getElementById('status_claim_id').value = id;
      document.getElementById('status_value').value = d.status;
      document.getElementById('status_approved_amount').value = d.approved_amount;
      document.getElementById('status_rejection_reason').value = d.rejection_reason || '';
      new bootstrap.Modal(document.getElementById('statusModal')).show();
    });
}

document.getElementById('statusForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('status_claim_id').value;
  fetch(`/insurance/${id}/status`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      status: document.getElementById('status_value').value,
      approved_amount: document.getElementById('status_approved_amount').value,
      rejection_reason: document.getElementById('status_rejection_reason').value,
    })
  }).then(res => res.json()).then(() => {
    bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
    claimsTable.ajax.reload(null, false);
  });
});

function deleteClaim(id) {
  confirmDelete(`/insurance/${id}`, 'Claim', function() {
    claimsTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasClaimAdd')).show();
});
@endif
</script>
@endsection

@endsection
