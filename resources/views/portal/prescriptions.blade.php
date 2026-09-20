@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'My Prescriptions - HMS')

@section('content')

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0"><i class="icon-base ti tabler-prescription me-2 text-primary"></i>My Prescriptions</h5>
  </div>
  <div class="card-body">
    @forelse ($prescriptions as $rx)
    <div class="border rounded p-4 mb-4">
      <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
          <strong>{{ $rx->prescription_no }}</strong>
          <div class="text-muted small">Dr. {{ $rx->doctor->name ?? 'N/A' }} — {{ \Carbon\Carbon::parse($rx->prescription_date)->format('d M Y') }}</div>
        </div>
        <span class="badge bg-label-{{ $rx->status === 'dispensed' ? 'success' : 'warning' }}">{{ ucfirst($rx->status ?? 'pending') }}</span>
      </div>
      @if ($rx->items->count() > 0)
      <div class="table-responsive">
        <table class="table table-sm border">
          <thead>
            <tr>
              <th>Drug</th>
              <th>Dosage</th>
              <th>Frequency</th>
              <th>Duration</th>
              <th>Instructions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rx->items as $item)
            <tr>
              <td>{{ $item->medicine_name ?? 'N/A' }}</td>
              <td>{{ $item->dosage ?? '—' }}</td>
              <td>{{ $item->frequency ?? '—' }}</td>
              <td>{{ $item->duration ?? '—' }}</td>
              <td>{{ $item->instructions ?? '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
      @if ($rx->notes)
      <p class="text-muted small mb-0 mt-2">Notes: {{ $rx->notes }}</p>
      @endif
    </div>
    @empty
    <p class="text-muted text-center py-6 mb-0">No prescriptions found.</p>
    @endforelse
  </div>
</div>

@endsection
