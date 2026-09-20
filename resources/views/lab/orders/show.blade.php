@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Lab Order - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')
<div class="row invoice-preview">

  {{-- Lab Order Preview --}}
  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column align-items-xl-center align-items-md-start align-items-sm-center align-items-start">
          <div class="mb-xl-0 mb-6 text-heading">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-flask text-primary" style="font-size:2rem;"></i>
              <span class="fw-bold fs-4">Laboratory</span>
            </div>
            <p class="mb-1">Hospital MS — Lab Department</p>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div>
            <h5 class="mb-4">{{ $labOrder->order_no }}</h5>
            <div class="mb-1 text-heading">
              <span>Order Date: </span>
              <span class="fw-medium">{{ \Carbon\Carbon::parse($labOrder->order_date)->format('d M Y') }}</span>
            </div>
            <div class="text-heading">
              <span>Status: </span>
              @php
                $statusColors = [
                  'pending'    => 'warning',
                  'processing' => 'info',
                  'completed'  => 'success',
                  'cancelled'  => 'danger',
                ];
              @endphp
              <span class="badge bg-label-{{ $statusColors[$labOrder->status] ?? 'secondary' }}">
                {{ ucfirst($labOrder->status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- Patient & Doctor --}}
      <div class="card-body px-0">
        <div class="row">
          <div class="col-md-6 mb-6 mb-md-0">
            <h6>Patient:</h6>
            <p class="mb-1 fw-medium text-heading">{{ $labOrder->patient->name }}</p>
            <p class="mb-1">MRN: {{ $labOrder->patient->mrn }}</p>
            <p class="mb-1">Phone: {{ $labOrder->patient->phone ?? 'N/A' }}</p>
            <p class="mb-0">Blood Group: {{ $labOrder->patient->blood_group ?? 'N/A' }}</p>
          </div>
          <div class="col-md-6">
            <h6>Referring Doctor:</h6>
            <p class="mb-1 fw-medium text-heading">Dr. {{ $labOrder->doctor->name }}</p>
            <p class="mb-1">{{ $labOrder->doctor->specialization }}</p>
            <p class="mb-0">{{ $labOrder->doctor->qualification ?? '' }}</p>
          </div>
        </div>
      </div>

      {{-- Tests Table --}}
      <div class="table-responsive border border-bottom-0 border-top-0 rounded">
        <table class="table m-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Test Name</th>
              <th>Category</th>
              <th>Sample</th>
              <th>Status</th>
              <th>Result</th>
              <th>Flag</th>
            </tr>
          </thead>
          <tbody>
            @foreach($labOrder->items as $index => $item)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td class="fw-medium text-heading">{{ $item->labTest->test_name }}</td>
              <td><span class="badge bg-label-primary">{{ $item->labTest->category }}</span></td>
              <td>{{ $item->labTest->sample_type }}</td>
              <td>
                <span class="badge bg-label-{{ $item->status === 'completed' ? 'success' : 'warning' }}">
                  {{ ucfirst($item->status) }}
                </span>
              </td>
              <td>
                @if($item->result)
                  <strong>{{ $item->result->result_value }}</strong>
                  {{ $item->result->unit ?? $item->labTest->unit ?? '' }}
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td>
                @if($item->result)
                  @php
                    $flagColors = [
                      'normal'   => 'success',
                      'high'     => 'danger',
                      'low'      => 'warning',
                      'critical' => 'danger',
                    ];
                  @endphp
                  <span class="badge bg-label-{{ $flagColors[$item->result->flag] ?? 'secondary' }}">
                    {{ ucfirst($item->result->flag) }}
                  </span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Enter Results Form --}}
      @if($labOrder->status !== 'completed')
      <div class="card-body px-0 mt-6">
        <h6 class="mb-4">
          <i class="icon-base ti tabler-edit me-2 text-primary"></i>Enter Results
        </h6>
        <form action="{{ route('lab.orders.results', $labOrder->id) }}" method="POST">
          @csrf
          @foreach($labOrder->items as $item)
          <div class="border rounded p-4 mb-4">
            <h6 class="mb-3 text-primary">{{ $item->labTest->test_name }}</h6>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Result Value *</label>
                <input type="text" class="form-control" name="results[{{ $item->id }}][result_value]"
                  placeholder="e.g. 120" required />
              </div>
              <div class="col-md-2">
                <label class="form-label">Unit</label>
                <input type="text" class="form-control" name="results[{{ $item->id }}][unit]"
                  value="{{ $item->labTest->unit ?? '' }}" placeholder="mg/dL" />
              </div>
              <div class="col-md-3">
                <label class="form-label">Normal Range</label>
                <input type="text" class="form-control" name="results[{{ $item->id }}][normal_range]"
                  value="{{ $item->labTest->normal_range_male ?? '' }}" placeholder="70-110" />
              </div>
              <div class="col-md-4">
                <label class="form-label">Remarks</label>
                <input type="text" class="form-control" name="results[{{ $item->id }}][remarks]"
                  placeholder="Optional remarks" />
              </div>
            </div>
          </div>
          @endforeach
          <button type="submit" class="btn btn-success">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save Results
          </button>
        </form>
      </div>
      @endif

      {{-- Notes --}}
      @if($labOrder->notes)
      <hr class="mt-6 mb-4" />
      <div class="card-body p-0">
        <span class="fw-medium text-heading">Notes: </span>
        <span>{{ $labOrder->notes }}</span>
      </div>
      @endif

    </div>
  </div>
  {{-- /Lab Order --}}

  {{-- Actions --}}
  <div class="col-xl-3 col-md-4 col-12 invoice-actions">
    <div class="card mb-6">
      <div class="card-body">
        <a href="{{ route('lab.orders.print', $labOrder->id) }}"
          target="_blank"
          class="btn btn-primary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-printer icon-xs me-2"></i>Print Report
          </span>
        </a>
        <a href="{{ route('lab.orders.index') }}"
          class="btn btn-label-secondary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-arrow-left icon-xs me-2"></i>Back
          </span>
        </a>
        <a href="{{ route('patients.show', $labOrder->patient_id) }}"
          class="btn btn-label-info d-grid w-100">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-user icon-xs me-2"></i>View Patient
          </span>
        </a>
      </div>
    </div>

    {{-- Order Info --}}
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Order Info</h6>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <small class="text-muted d-block">Order No</small>
          <p class="mb-0 fw-medium">{{ $labOrder->order_no }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted d-block">Date</small>
          <p class="mb-0">{{ \Carbon\Carbon::parse($labOrder->order_date)->format('d M Y') }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted d-block">Total Tests</small>
          <p class="mb-0">{{ $labOrder->items->count() }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted d-block">Completed</small>
          <p class="mb-0">{{ $labOrder->items->where('status','completed')->count() }} / {{ $labOrder->items->count() }}</p>
        </div>
        <div>
          <small class="text-muted d-block">Status</small>
          <span class="badge bg-label-{{ $statusColors[$labOrder->status] ?? 'secondary' }}">
            {{ ucfirst($labOrder->status) }}
          </span>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection