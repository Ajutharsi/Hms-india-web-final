@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'My Bills - HMS')

@section('content')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0"><i class="icon-base ti tabler-file-invoice me-2 text-primary"></i>My Bills</h5>
  </div>
  <div class="table-responsive">
    <table class="table border-top">
      <thead>
        <tr>
          <th>Invoice No</th>
          <th>Date</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($invoices as $inv)
        <tr>
          <td>{{ $inv->invoice_no }}</td>
          <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
          <td>{{ $currency }} {{ number_format($inv->total, 2) }}</td>
          <td>{{ $currency }} {{ number_format($inv->paid_amount, 2) }}</td>
          <td>{{ $currency }} {{ number_format($inv->balance, 2) }}</td>
          <td>
            <span class="badge bg-label-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'overdue' ? 'danger' : 'warning') }}">
              {{ ucfirst($inv->status) }}
            </span>
          </td>
          <td>
            @if ($inv->balance > 0)
            <form action="{{ route('portal.invoices.request-payment', $inv->id) }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-sm btn-label-primary">
                <i class="icon-base ti tabler-credit-card me-1"></i> Request Payment Help
              </button>
            </form>
            @else
            <span class="text-success"><i class="icon-base ti tabler-check"></i> Settled</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-6">No invoices found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
