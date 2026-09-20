@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice-print.scss')
@endsection

@section('page-script')
@vite('resources/assets/js/app-invoice-print.js')
@endsection

@section('content')
<div class="invoice-print p-12">

  {{-- Header --}}
  <div class="d-flex justify-content-between flex-row mb-6">
    <div>
      @if($hospitalSettings->logo_url)
        <img src="{{ $hospitalSettings->logo_url }}"
          alt="Logo" style="height:60px;object-fit:contain;" class="mb-2 d-block" />
      @else
        <div class="d-flex align-items-center mb-2 gap-2">
          <i class="icon-base ti tabler-building-hospital text-primary" style="font-size:2rem;"></i>
        </div>
      @endif
      <span class="fw-bold fs-4">{{ $hospitalSettings->hospital_name }}</span>
      @if($hospitalSettings->address)
      <p class="mb-1">{{ $hospitalSettings->address }}{{ $hospitalSettings->city ? ', ' . $hospitalSettings->city : '' }}</p>
      @endif
      @if($hospitalSettings->country)
      <p class="mb-1">{{ $hospitalSettings->country }}</p>
      @endif
      <p class="mb-0">
        @if($hospitalSettings->phone) {{ $hospitalSettings->phone }} @endif
        @if($hospitalSettings->phone && $hospitalSettings->email) | @endif
        @if($hospitalSettings->email) {{ $hospitalSettings->email }} @endif
      </p>
      @if($hospitalSettings->tax_number)
      <p class="mb-0 small text-muted">Tax No: {{ $hospitalSettings->tax_number }}</p>
      @endif
      @if($hospitalSettings->registration_number)
      <p class="mb-0 small text-muted">Reg No: {{ $hospitalSettings->registration_number }}</p>
      @endif
    </div>
    <div class="text-end">
      <h5 class="mb-2">INVOICE</h5>
      <p class="mb-1 fw-medium fs-5">{{ $invoice->invoice_no }}</p>
      <p class="mb-1">Date: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
      @if($invoice->due_date)
      <p class="mb-0">Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</p>
      @endif
    </div>
  </div>

  <hr class="mb-6" />

  {{-- Patient --}}
  <div class="row d-flex justify-content-between mb-6">
    <div class="col-sm-6 w-50">
      <h6>Bill To:</h6>
      <p class="mb-1 fw-medium">{{ $invoice->patient->name }}</p>
      <p class="mb-1">MRN: {{ $invoice->patient->mrn }}</p>
      <p class="mb-1">Phone: {{ $invoice->patient->phone ?? 'N/A' }}</p>
      <p class="mb-0">Blood Group: {{ $invoice->patient->blood_group ?? 'N/A' }}</p>
    </div>
    @if($invoice->appointment)
    <div class="col-sm-6 w-50">
      <h6>Appointment:</h6>
      <p class="mb-1 fw-medium">{{ $invoice->appointment->appointment_id }}</p>
      <p class="mb-1">Dr. {{ $invoice->appointment->doctor->name }}</p>
      <p class="mb-0">{{ \Carbon\Carbon::parse($invoice->appointment->appointment_date)->format('d M Y') }}</p>
    </div>
    @endif
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
          <td class="fw-medium">{{ $item->item_name }}</td>
          <td>{{ $item->item_type }}</td>
          <td><small>{{ $item->description ?? '—' }}</small></td>
          <td>{{ $item->quantity }}</td>
          <td>{{ $hospitalSettings->currency }} {{ number_format($item->unit_price, 2) }}</td>
          <td>{{ $item->discount > 0 ? $hospitalSettings->currency . ' ' . number_format($item->discount, 2) : '—' }}</td>
          <td class="fw-medium">{{ $hospitalSettings->currency }} {{ number_format($item->total, 2) }}</td>
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
          <td class="align-top px-0 py-6">
            @if($invoice->notes)
            <p class="mb-0"><span class="fw-medium">Notes:</span> {{ $invoice->notes }}</p>
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
            <p class="mb-2 border-bottom pb-2">Total:</p>
            <p class="mb-2">Paid:</p>
            <p class="mb-0 fw-bold">Balance:</p>
          </td>
          <td class="text-end px-0 py-6 w-px-150 fw-medium">
            <p class="mb-2">{{ $hospitalSettings->currency }} {{ number_format($invoice->subtotal, 2) }}</p>
            @if($invoice->discount > 0)
            <p class="mb-2">- {{ $hospitalSettings->currency }} {{ number_format($invoice->discount, 2) }}</p>
            @endif
            @if($invoice->tax > 0)
            <p class="mb-2 border-bottom pb-2">+ {{ $hospitalSettings->currency }} {{ number_format($invoice->tax, 2) }}</p>
            @endif
            <p class="mb-2 border-bottom pb-2 fw-bold">{{ $hospitalSettings->currency }} {{ number_format($invoice->total, 2) }}</p>
            <p class="mb-2">{{ $hospitalSettings->currency }} {{ number_format($invoice->paid_amount, 2) }}</p>
            <p class="mb-0 fw-bold">{{ $hospitalSettings->currency }} {{ number_format($invoice->balance, 2) }}</p>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- Payment History --}}
  @if($invoice->payments->count() > 0)
  <hr class="mt-0 mb-4" />
  <h6 class="mb-3">Payment History</h6>
  <table class="table table-sm table-bordered mb-6">
    <thead class="table-light">
      <tr>
        <th>Payment No</th>
        <th>Date</th>
        <th>Method</th>
        <th>Reference</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->payments as $payment)
      <tr>
        <td>{{ $payment->payment_no }}</td>
        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
        <td class="text-capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
        <td>{{ $payment->reference_no ?? '—' }}</td>
        <td class="fw-medium">{{ $hospitalSettings->currency }} {{ number_format($payment->amount, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  {{-- Footer --}}
  <hr class="mt-0 mb-4" />
  <div class="d-flex justify-content-between align-items-end">
    <div>
      @if($hospitalSettings->footer_note)
      <p class="mb-1 small text-muted">{{ $hospitalSettings->footer_note }}</p>
      @endif
      <p class="mb-0 small text-muted">Generated by {{ $hospitalSettings->hospital_name }} — {{ now()->format('d M Y H:i') }}</p>
    </div>
    <div class="text-center">
      <div style="border-top: 2px solid #333; width: 180px; margin-bottom: 8px;"></div>
      <p class="mb-0 fw-medium small">Authorized Signature</p>
    </div>
  </div>

</div>
@endsection