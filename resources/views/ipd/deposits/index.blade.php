@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'IPD Deposits - HMS')

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

<div id="alertPlaceholder"></div>

{{-- Stats --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format($totalCollected, 2) }}</h4>
              <p class="mb-0">Total Collected</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-cash icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format($totalRefunded, 2) }}</h4>
              <p class="mb-0">Total Refunded</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-receipt-refund icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format($netBalance, 2) }}</h4>
              <p class="mb-0">Net Balance</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-wallet icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format($todayCollection, 2) }}</h4>
              <p class="mb-0">Today's Collection</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-calendar icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Deposits Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">IPD Advance / Deposits</h5>
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDepositAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Add Deposit
    </button>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="depositsTable">
      <thead>
        <tr>
          <th></th>
          <th>Deposit No</th>
          <th>Patient</th>
          <th>Amount</th>
          <th>Type</th>
          <th>Payment Method</th>
          <th>Balance</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Deposit Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDepositAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Deposit</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('ipd.deposits.store') }}" method="POST">
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
          <label class="form-label">Amount ({{ $currency }}) *</label>
          <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" required />
          @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Payment Method *</label>
          <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
            <option value="cash" @selected(old('payment_method','cash')=='cash')>Cash</option>
            <option value="card" @selected(old('payment_method')=='card')>Card</option>
            <option value="upi" @selected(old('payment_method')=='upi')>UPI</option>
            <option value="bank_transfer" @selected(old('payment_method')=='bank_transfer')>Bank Transfer</option>
            <option value="cheque" @selected(old('payment_method')=='cheque')>Cheque</option>
          </select>
          @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Reference No</label>
          <input type="text" class="form-control @error('reference_no') is-invalid @enderror" name="reference_no" value="{{ old('reference_no') }}" />
          @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Refund Modal --}}
  <div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Refund Deposit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-4">Available balance: <strong id="refund_balance_display">{{ $currency }} 0.00</strong></p>
          <form id="refundForm">
            <input type="hidden" id="refund_deposit_id" />
            <div class="mb-4">
              <label class="form-label">Refund Amount ({{ $currency }}) *</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="refund_amount" required />
            </div>
            <div class="mb-4">
              <label class="form-label">Payment Method *</label>
              <select class="form-select" id="refund_payment_method" required>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="upi">UPI</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Notes</label>
              <textarea class="form-control" id="refund_notes" rows="2"></textarea>
            </div>
            <div id="refundError" class="alert alert-danger d-none"></div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ti tabler-receipt-refund me-1"></i> Process Refund
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
let depositsTable;
const currency = '{{ $currency }}';

function typeColor(t) {
  return { advance: 'success', refund: 'danger', adjustment: 'warning' }[t] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  depositsTable = $('#depositsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('ipd.deposits.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'deposit_no', name: 'deposit_no' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'amount', name: 'amount', render: d => currency + ' ' + parseFloat(d).toLocaleString() },
      { data: 'type', name: 'type', render: d => `<span class="badge bg-label-${typeColor(d)}">${d}</span>` },
      { data: 'payment_method', name: 'payment_method', render: d => d.replace('_',' ').toUpperCase() },
      { data: 'balance', name: 'balance', orderable: false, render: d => currency + ' ' + parseFloat(d).toLocaleString() },
      { data: 'deposited_at', name: 'deposited_at' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[7, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });
});

function openRefund(depositId, patientId) {
  fetch(`/ipd/deposits/${depositId}`)
    .then(res => res.json())
    .then(d => {
      document.getElementById('refund_deposit_id').value = depositId;
      document.getElementById('refund_balance_display').textContent = currency + ' ' + parseFloat(d.balance).toLocaleString();
      document.getElementById('refund_amount').value = '';
      document.getElementById('refund_notes').value = '';
      document.getElementById('refundError').classList.add('d-none');
      new bootstrap.Modal(document.getElementById('refundModal')).show();
    });
}

document.getElementById('refundForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const id = document.getElementById('refund_deposit_id').value;
  fetch(`/ipd/deposits/${id}/refund`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      amount: document.getElementById('refund_amount').value,
      payment_method: document.getElementById('refund_payment_method').value,
      notes: document.getElementById('refund_notes').value,
    })
  }).then(res => res.json()).then(res => {
    if (res.error) {
      const el = document.getElementById('refundError');
      el.textContent = res.error;
      el.classList.remove('d-none');
      return;
    }
    bootstrap.Modal.getInstance(document.getElementById('refundModal')).hide();
    depositsTable.ajax.reload(null, false);
  });
});

function deleteDeposit(id) {
  confirmDelete(`/ipd/deposits/${id}`, 'Deposit', function() {
    depositsTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasDepositAdd')).show();
});
@endif
</script>
@endsection

@endsection
