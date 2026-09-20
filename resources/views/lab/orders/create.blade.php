@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'New Lab Order - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')

<form action="{{ route('lab.orders.store') }}" method="POST">
@csrf

<div class="row invoice-add">

  {{-- Lab Order Form --}}
  <div class="col-lg-9 col-12 mb-lg-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
          <div class="mb-md-0 mb-6">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-flask text-primary" style="font-size:2rem;"></i>
              <span class="app-brand-text fw-bold fs-4">Laboratory</span>
            </div>
            <p class="mb-1">Hospital MS — Lab Department</p>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
            <dl class="row mb-0 gx-4">
              <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                <span class="h5 text-capitalize mb-0 text-nowrap">Lab Order</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="LAB-{{ date('Y') }}-AUTO" />
              </dd>
              <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Date:</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="{{ now()->format('d M Y') }}" />
              </dd>
            </dl>
          </div>
        </div>
      </div>

      {{-- Patient & Doctor --}}
      <div class="card-body px-0">
        <div class="row g-4">
          <div class="col-md-6">
            <h6 class="mb-3">Patient:</h6>
            <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
              <option value="">Select Patient</option>
              @foreach($patients as $patient)
              <option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->name }} ({{ $patient->mrn }})</option>
              @endforeach
            </select>
            @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <h6 class="mb-3">Referring Doctor:</h6>
            <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id" required>
              <option value="">Select Doctor</option>
              @foreach($doctors as $doctor)
              <option value="{{ $doctor->id }}" data-photo="{{ $doctor->photo_url }}" @selected(old('doctor_id')==$doctor->id)>Dr. {{ $doctor->name }} — {{ $doctor->specialization }}</option>
              @endforeach
            </select>
            @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>

      <hr class="mt-0 mb-6" />

      {{-- Tests Selection --}}
      <div class="card-body pt-0 px-0">
        <h6 class="mb-4">Select Tests</h6>

        @error('tests') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
        <div class="row g-3 mb-4">
          @foreach($tests->groupBy('category') as $category => $categoryTests)
          <div class="col-12">
            <div class="border rounded p-4">
              <h6 class="mb-3 text-primary">
                <i class="icon-base ti tabler-category me-2"></i>{{ $category }}
              </h6>
              <div class="row g-2">
                @foreach($categoryTests as $test)
                <div class="col-md-4 col-6">
                  <div class="form-check">
                    <input class="form-check-input @error('tests') is-invalid @enderror" type="checkbox"
                      name="tests[]" value="{{ $test->id }}"
                      id="test_{{ $test->id }}" @checked(in_array($test->id, old('tests', []))) />
                    <label class="form-check-label" for="test_{{ $test->id }}">
                      <span class="fw-medium">{{ $test->test_name }}</span>
                      <br>
                      <small class="text-muted">{{ $test->sample_type }} | LKR {{ number_format($test->price, 2) }}</small>
                    </label>
                  </div>
                </div>
                @endforeach
              </div>
            </div>
          </div>
          @endforeach
        </div>

        {{-- Total --}}
        <div class="d-flex justify-content-end mt-4">
          <div class="border rounded p-4" style="min-width:250px;">
            <div class="d-flex justify-content-between mb-2">
              <span>Selected Tests:</span>
              <span class="fw-medium" id="selectedCount">0</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Total Amount:</span>
              <span class="fw-medium" id="totalAmount">LKR 0.00</span>
            </div>
          </div>
        </div>

      </div>

      <hr class="my-6" />

      {{-- Notes --}}
      <div class="card-body px-0 pb-0">
        <label class="text-heading mb-2 fw-medium">Notes:</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
          placeholder="Additional instructions...">{{ old('notes') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

    </div>
  </div>

  {{-- Actions --}}
  <div class="col-lg-3 col-12 invoice-actions">
    <div class="card mb-6">
      <div class="card-body">
        <button type="submit" class="btn btn-primary d-grid w-100 mb-4">
          <span class="d-flex align-items-center justify-content-center">
            <i class="icon-base ti tabler-device-floppy icon-xs me-2"></i>
            Save Order
          </span>
        </button>
        <a href="{{ route('lab.orders.index') }}" class="btn btn-label-secondary d-grid w-100">
          Cancel
        </a>
      </div>
    </div>

    {{-- Selected Tests Summary --}}
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Selected Tests</h6>
      </div>
      <div class="card-body p-3">
        <div id="selectedTestsList">
          <p class="text-muted small mb-0">No tests selected</p>
        </div>
      </div>
    </div>

  </div>

</div>

</form>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const checkboxes = document.querySelectorAll('input[name="tests[]"]');
  const selectedCount  = document.getElementById('selectedCount');
  const totalAmount    = document.getElementById('totalAmount');
  const selectedList   = document.getElementById('selectedTestsList');

  // Test prices
  const testPrices = {
    @foreach($tests as $test)
    {{ $test->id }}: { name: '{{ $test->test_name }}', price: {{ $test->price }} },
    @endforeach
  };

  function updateSummary() {
    let count  = 0;
    let total  = 0;
    let listHtml = '';

    checkboxes.forEach(cb => {
      if (cb.checked) {
        count++;
        const t = testPrices[cb.value];
        if (t) {
          total += parseFloat(t.price);
          listHtml += `
            <div class="d-flex justify-content-between mb-2">
              <small>${t.name}</small>
              <small class="text-muted">LKR ${parseFloat(t.price).toLocaleString()}</small>
            </div>
          `;
        }
      }
    });

    selectedCount.textContent  = count;
    totalAmount.textContent    = 'LKR ' + total.toLocaleString('en', {minimumFractionDigits: 2});
    selectedList.innerHTML     = count > 0 ? listHtml : '<p class="text-muted small mb-0">No tests selected</p>';
  }

  checkboxes.forEach(cb => cb.addEventListener('change', updateSummary));
});
</script>
@endsection

@endsection