@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'My Lab Results - HMS')

@section('content')

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0"><i class="icon-base ti tabler-flask me-2 text-primary"></i>My Lab Results</h5>
  </div>
  <div class="table-responsive">
    <table class="table border-top">
      <thead>
        <tr>
          <th>Order No</th>
          <th>Date</th>
          <th>Tests</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($labOrders as $order)
        <tr>
          <td>{{ $order->order_no }}</td>
          <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</td>
          <td>{{ $order->items->pluck('labTest.test_name')->filter()->implode(', ') ?: '—' }}</td>
          <td><span class="badge bg-label-success">Completed</span></td>
          <td>
            <a href="{{ route('portal.lab-results.download', $order->id) }}" target="_blank" class="btn btn-sm btn-label-primary">
              <i class="icon-base ti tabler-download me-1"></i> Download
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-6">No completed lab results found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
