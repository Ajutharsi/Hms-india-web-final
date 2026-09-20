@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Invoice - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')

@php
$statusColors = [
  'draft'     => 'secondary',
  'sent'      => 'info',
  'paid'      => 'success',
  'partial'   => 'warning',
  'overdue'   => 'danger',
  'cancelled' => 'secondary',
];
$currency = $hospitalSettings->currency ?? 'LKR';
@endphp

<div class="row invoice-preview">

  {{-- Invoice Preview --}}
  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column align-items-xl-center align-items-md-start align-items-sm-center align-items-start">
          <div class="mb-xl-0 mb-6 text-heading">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-building-hospital text-primary" style="font-size:2rem;"></i>
              <span class="fw-bold fs-4">Hospital MS</span>
            </div>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-1">Sri Lanka 10001</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div class="text-end">
            <h5 class="mb-2">{{ $invoice->invoice_no }}</h5>
            <span class="badge bg-label-{{ $statusColors[$invoice->status] ?? 'secondary' }} mb-2">
              {{ ucfirst($invoice->status) }}
            </span>
            <div class="mb-1 text-heading">
              <span>Date: </span>
              <span class="fw-medium">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span>
            </div>
            @if($invoice->due_date)
            <div class="text-heading">
              <span>Due: </span>
              <span class="fw-medium">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span>
            </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Patient & Appointment --}}
      <div class="card-body px-0">
        <div class="row">
          <div class="col-md-6 mb-6 mb-md-0">
            <h6>Bill To:</h6>
            <p class="mb-1 fw-medium text-heading">{{ $invoice->patient->name }}</p>
            <p class="mb-1">MRN: {{ $invoice->patient->mrn }}</p>
            <p class="mb-1">Phone: {{ $invoice->patient->phone ?? 'N/A' }}</p>
            <p class="mb-0">Blood Group: {{ $invoice->patient->blood_group ?? 'N/A' }}</p>
          </div>
          @if($invoice->appointment)
          <div class="col-md-6">
            <h6>Appointment:</h6>
            <p class="mb-1 fw-medium text-heading">{{ $invoice->appointment->appointment_id }}</p>
            <p class="mb-1">Dr. {{ $invoice->appointment->doctor->name }}</p>
            <p class="mb-0">{{ \Carbon\Carbon::parse($invoice->appointment->appointment_date)->format('d M Y') }}</p>
          </div>
          @endif
        </div>
      </div>

      {{-- Items Table --}}
      <div class="table-responsive border border-bottom-0 border-top-0 rounded">
        <table class="table m-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Item</th>
              <th>Type</th>
              <th>Description</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Discount</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td class="fw-medium text-heading">{{ $item->item_name }}</td>
              <td><span class="badge bg-label-primary">{{ $item->item_type }}</span></td>
              <td><small class="text-muted">{{ $item->description ?? '—' }}</small></td>
              <td>{{ $item->quantity }}</td>
              <td>{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
              <td>{{ $item->discount > 0 ? $currency.' '.number_format($item->discount, 2) : '—' }}</td>
              <td class="fw-medium">{{ $currency }} {{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Totals --}}
      <div class="table-responsive">
        <table class="table m-0 table-borderless">
          <tbody>
            <tr>
              <td class="align-top px-0 py-6 text-body">
                @if($invoice->notes)
                <p class="mb-1">
                  <span class="fw-medium">Notes:</span>
                  {{ $invoice->notes }}
                </p>
                @endif
              </td>
              <td class="px-0 py-6 w-px-150">
                <p class="mb-2">Subtotal:</p>
                @if($invoice->discount > 0)
                <p class="mb-2">Discount:</p>
                @endif
                @if($invoice->tax > 0)
                <p class="mb-2 border-bottom pb-2">Tax:</p>
                @endif
                <p class="mb-0 fw-bold">Total:</p>
              </td>
              <td class="text-end px-0 py-6 w-px-150 fw-medium text-heading">
                <p class="mb-2">{{ $currency }} {{ number_format($invoice->subtotal, 2) }}</p>
                @if($invoice->discount > 0)
                <p class="mb-2 text-danger">- {{ $currency }} {{ number_format($invoice->discount, 2) }}</p>
                @endif
                @if($invoice->tax > 0)
                <p class="mb-2 border-bottom pb-2">+ {{ $currency }} {{ number_format($invoice->tax, 2) }}</p>
                @endif
                <p class="mb-0 fw-bold text-primary">{{ $currency }} {{ number_format($invoice->total, 2) }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      {{-- Payment History --}}
      @if($invoice->payments->count() > 0)
      <hr class="mt-0 mb-6" />
      <div class="card-body p-0">
        <h6 class="mb-3">Payment History</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Payment No</th>
                <th>Date</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Received By</th>
              </tr>
            </thead>
            <tbody>
              @foreach($invoice->payments as $payment)
              <tr>
                <td>{{ $payment->payment_no }}</td>
                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                <td><span class="badge bg-label-info text-capitalize">{{ str_replace('_',' ',$payment->payment_method) }}</span></td>
                <td class="fw-medium text-success">{{ $currency }} {{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->reference_no ?? '—' }}</td>
                <td>{{ $payment->receivedBy->name ?? 'N/A' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-end mt-3 gap-4">
          <div class="text-end">
            <p class="mb-1">Paid: <span class="text-success fw-bold">{{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</span></p>
            <p class="mb-0">Balance: <span class="text-{{ $invoice->balance > 0 ? 'danger' : 'success' }} fw-bold">{{ $currency }} {{ number_format($invoice->balance, 2) }}</span></p>
          </div>
        </div>
      </div>
      @endif

    </div>
  </div>

  {{-- Actions --}}
  <div class="col-xl-3 col-md-4 col-12 invoice-actions">

    {{-- Action Buttons --}}
    <div class="card mb-6">
      <div class="card-body">
        <a href="{{ route('billing.invoices.print', $invoice->id) }}"
          target="_blank"
          class="btn btn-primary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-printer icon-xs me-2"></i>Print Invoice
          </span>
        </a>
        <a href="{{ route('billing.invoices.index') }}"
          class="btn btn-label-secondary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-arrow-left icon-xs me-2"></i>Back
          </span>
        </a>
        <a href="{{ route('patients.show', $invoice->patient_id) }}"
          class="btn btn-label-info d-grid w-100">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-user icon-xs me-2"></i>View Patient
          </span>
        </a>
      </div>
    </div>

    {{-- Invoice Info --}}
    <div class="card mb-6">
      <div class="card-header">
        <h6 class="mb-0">Invoice Info</h6>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <small class="text-muted d-block">Invoice No</small>
          <p class="mb-0 fw-medium">{{ $invoice->invoice_no }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted d-block">Status</small>
          <span class="badge bg-label-{{ $statusColors[$invoice->status] ?? 'secondary' }}">
            {{ ucfirst($invoice->status) }}
          </span>
        </div>
        <div class="mb-2">
          <small class="text-muted d-block">Total</small>
          <p class="mb-0 fw-bold text-primary">{{ $currency }} {{ number_format($invoice->total, 2) }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted d-block">Paid</small>
          <p class="mb-0 text-success fw-medium">{{ $currency }} {{ number_format($invoice->paid_amount, 2) }}</p>
        </div>
        <div>
          <small class="text-muted d-block">Balance</small>
          <p class="mb-0 {{ $invoice->balance > 0 ? 'text-danger' : 'text-success' }} fw-medium">
            {{ $currency }} {{ number_format($invoice->balance, 2) }}
          </p>
        </div>
      </div>
    </div>

    {{-- Add Payment --}}
    @if($invoice->balance > 0 && $invoice->status !== 'cancelled')
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="icon-base ti tabler-currency-dollar me-2 text-success"></i>Add Payment</h6>
      </div>
      <div class="card-body">
        <form action="{{ route('billing.invoices.payment', $invoice->id) }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label">Amount ({{ $currency }}) *</label>
            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount"
              value="{{ old('amount', $invoice->balance) }}" max="{{ $invoice->balance }}"
              step="0.01" min="0.01" required />
            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Method *</label>
            <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
              <option value="cash" @selected(old('payment_method', 'cash')=='cash')>Cash</option>
              <option value="card" @selected(old('payment_method')=='card')>Card</option>
              <option value="bank_transfer" @selected(old('payment_method')=='bank_transfer')>Bank Transfer</option>
              <option value="cheque" @selected(old('payment_method')=='cheque')>Cheque</option>
              <option value="online" @selected(old('payment_method')=='online')>Online</option>
            </select>
            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Reference No</label>
            <input type="text" class="form-control @error('reference_no') is-invalid @enderror" name="reference_no" placeholder="Optional" value="{{ old('reference_no') }}" />
            @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <button type="submit" class="btn btn-success w-100">
            <i class="icon-base ti tabler-check me-1"></i> Record Payment
          </button>
        </form>
      </div>
    </div>
    @endif

  </div>

</div>
@endsection