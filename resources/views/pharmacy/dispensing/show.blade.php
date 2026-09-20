@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Dispensing Details - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')

{{-- Header --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
  <div>
    <div class="mb-1">
      <span class="h5">{{ $dispensing->dispensing_no }}</span>
      @php
        $statusColors = ['dispensed'=>'success','pending'=>'warning','partial'=>'info'];
      @endphp
      <span class="badge bg-label-{{ $statusColors[$dispensing->status] ?? 'secondary' }} ms-2">
        {{ ucfirst($dispensing->status) }}
      </span>
    </div>
    <p class="mb-0 text-muted">
      {{ \Carbon\Carbon::parse($dispensing->dispensed_date)->format('d M Y') }}
      @if($dispensing->dispensedBy)
        — Dispensed by {{ $dispensing->dispensedBy->name }}
      @endif
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('pharmacy.dispensing.index') }}" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

<div class="row">

  {{-- Left — Items --}}
  <div class="col-12 col-lg-8">

    {{-- Dispensed Items --}}
    <div class="card mb-6">
      <div class="card-header">
        <h5 class="mb-0">Dispensed Items</h5>
      </div>
      <div class="table-responsive">
        <table class="table m-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Drug</th>
              <th>Qty Prescribed</th>
              <th>Qty Dispensed</th>
              <th>Unit Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($dispensing->items as $index => $item)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td>
                <strong>{{ $item->medicine_name }}</strong>
                @if($item->drug)
                <br><small class="text-muted">{{ $item->drug->form }} — {{ $item->drug->strength }}</small>
                @endif
              </td>
              <td>{{ $item->quantity_prescribed }}</td>
              <td><span class="badge bg-label-primary">{{ $item->quantity_dispensed }}</span></td>
              <td>LKR {{ number_format($item->unit_price, 2) }}</td>
              <td class="fw-medium">LKR {{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end p-6">
        <div>
          <div class="d-flex justify-content-between mb-2 gap-6">
            <span class="text-heading">Total Amount:</span>
            <h5 class="mb-0 text-primary">LKR {{ number_format($dispensing->total_amount, 2) }}</h5>
          </div>
        </div>
      </div>
    </div>

    {{-- Prescription Details --}}
    @if($dispensing->prescription)
    <div class="card mb-6">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Prescription Details</h5>
        <a href="{{ route('prescriptions.show', $dispensing->prescription->id) }}"
          class="btn btn-sm btn-label-primary">
          <i class="icon-base ti tabler-eye me-1"></i> View Prescription
        </a>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p class="mb-1"><span class="fw-medium">Prescription No:</span> {{ $dispensing->prescription->prescription_no }}</p>
            <p class="mb-1"><span class="fw-medium">Date:</span> {{ \Carbon\Carbon::parse($dispensing->prescription->prescription_date)->format('d M Y') }}</p>
          </div>
          <div class="col-md-6">
            <p class="mb-1"><span class="fw-medium">Doctor:</span> Dr. {{ $dispensing->prescription->doctor->name }}</p>
            <p class="mb-0"><span class="fw-medium">Diagnosis:</span> {{ $dispensing->prescription->consultation->diagnosis ?? 'N/A' }}</p>
          </div>
        </div>
      </div>
    </div>
    @endif

  </div>

  {{-- Right — Patient & Summary --}}
  <div class="col-12 col-lg-4">

    {{-- Patient Card --}}
    <div class="card mb-6">
      <div class="card-header">
        <h5 class="mb-0">Patient Details</h5>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center mb-4">
          <div class="avatar me-3">
            <span class="avatar-initial rounded-circle bg-label-primary">
              {{ strtoupper(substr($dispensing->patient->name, 0, 1)) }}
            </span>
          </div>
          <div>
            <h6 class="mb-0">{{ $dispensing->patient->name }}</h6>
            <small class="text-muted">{{ $dispensing->patient->mrn }}</small>
          </div>
        </div>
        <p class="mb-1">
          <i class="icon-base ti tabler-phone me-2 text-muted"></i>
          {{ $dispensing->patient->phone ?? 'N/A' }}
        </p>
        <p class="mb-1">
          <i class="icon-base ti tabler-droplet me-2 text-muted"></i>
          Blood Group: {{ $dispensing->patient->blood_group ?? 'N/A' }}
        </p>
        <div class="mt-4">
          <a href="{{ route('patients.show', $dispensing->patient_id) }}"
            class="btn btn-label-info btn-sm w-100">
            <i class="icon-base ti tabler-user me-1"></i> View Patient
          </a>
        </div>
      </div>
    </div>

    {{-- Dispensing Summary --}}
    <div class="card mb-6">
      <div class="card-header">
        <h5 class="mb-0">Dispensing Info</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <small class="text-muted d-block">Dispensing No</small>
          <p class="mb-0 fw-medium">{{ $dispensing->dispensing_no }}</p>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Date</small>
          <p class="mb-0">{{ \Carbon\Carbon::parse($dispensing->dispensed_date)->format('d M Y') }}</p>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Total Items</small>
          <p class="mb-0">{{ $dispensing->items->count() }}</p>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Total Amount</small>
          <p class="mb-0 fw-bold text-primary">LKR {{ number_format($dispensing->total_amount, 2) }}</p>
        </div>
        <div class="mb-3">
          <small class="text-muted d-block">Status</small>
          <span class="badge bg-label-{{ $statusColors[$dispensing->status] ?? 'secondary' }}">
            {{ ucfirst($dispensing->status) }}
          </span>
        </div>
        @if($dispensing->notes)
        <div>
          <small class="text-muted d-block">Notes</small>
          <p class="mb-0">{{ $dispensing->notes }}</p>
        </div>
        @endif
      </div>
    </div>

  </div>

</div>
@endsection