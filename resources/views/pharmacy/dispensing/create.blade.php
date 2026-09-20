@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'New Dispensing - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')

<form action="{{ route('pharmacy.dispensing.store') }}" method="POST">
@csrf

@if ($errors->any())
<div class="alert alert-danger">
  <strong>Please fix the following:</strong>
  <ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul>
</div>
@endif

@if(isset($prescription))
  <input type="hidden" name="prescription_id" value="{{ $prescription->id }}" />
@endif

<div class="row invoice-add">

  {{-- Dispensing Form --}}
  <div class="col-lg-9 col-12 mb-lg-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
          <div class="mb-md-0 mb-6">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-building-hospital text-primary" style="font-size:2rem;"></i>
              <span class="app-brand-text fw-bold fs-4">Pharmacy</span>
            </div>
            <p class="mb-1">Hospital MS — Pharmacy Department</p>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
            <dl class="row mb-0 gx-4">
              <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                <span class="h5 text-capitalize mb-0 text-nowrap">Dispensing</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="DIS-{{ date('Y') }}-AUTO" />
              </dd>
              <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Date:</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="{{ now()->format('d M Y') }}" />
              </dd>
              @if(isset($prescription))
              <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Prescription:</span>
              </dt>
              <dd class="col-sm-7 mb-0">
                <input type="text" class="form-control" disabled value="{{ $prescription->prescription_no }}" />
              </dd>
              @endif
            </dl>
          </div>
        </div>
      </div>

      {{-- Patient --}}
      <div class="card-body px-0">
        <div class="row g-4">
          <div class="col-md-6">
            <h6 class="mb-3">Patient:</h6>
            @if(isset($prescription))
              <p class="mb-1 fw-medium">{{ $prescription->patient->name }}</p>
              <p class="mb-1 text-muted">MRN: {{ $prescription->patient->mrn }}</p>
              <input type="hidden" name="patient_id" value="{{ $prescription->patient_id }}" />
            @else
              <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
                <option value="">Select Patient</option>
                @foreach($patients as $patient)
                <option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->name }} ({{ $patient->mrn }})</option>
                @endforeach
              </select>
              @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @endif
          </div>
          @if(isset($prescription))
          <div class="col-md-6">
            <h6 class="mb-3">Doctor:</h6>
            <p class="mb-1 fw-medium">Dr. {{ $prescription->doctor->name }}</p>
            <p class="mb-1 text-muted">{{ $prescription->doctor->specialization }}</p>
          </div>
          @endif
        </div>
      </div>

      <hr class="mt-0 mb-6" />

      {{-- Medicines --}}
      <div class="card-body pt-0 px-0">
        <h6 class="mb-4">Medicines to Dispense</h6>

        @if(isset($prescription))
        {{-- From Prescription --}}
        <div class="alert alert-info mb-4">
          <i class="icon-base ti tabler-info-circle me-2"></i>
          Dispensing from Prescription <strong>{{ $prescription->prescription_no }}</strong>
        </div>

        @foreach($prescription->items as $index => $item)
        <div class="border rounded p-4 mb-4">
          <div class="row g-3 align-items-center">
            <div class="col-md-4">
              <label class="form-label fw-medium">{{ $item->medicine_name }}</label>
              <p class="text-muted small mb-0">{{ $item->dosage }} — {{ $item->frequency }} — {{ $item->duration }}</p>
            </div>
            <div class="col-md-3">
              <label class="form-label">Select Drug *</label>
              <select class="form-select drug-select @error('items.'.$index.'.drug_id') is-invalid @enderror" name="items[{{ $index }}][drug_id]"
                onchange="updatePrice(this, {{ $index }})" required>
                <option value="">Select Drug</option>
                @foreach($drugs as $drug)
                <option value="{{ $drug->id }}"
                  data-price="{{ $drug->unit_price }}"
                  data-stock="{{ $drug->current_stock }}"
                  {{ strtolower($drug->drug_name) === strtolower($item->medicine_name) ? 'selected' : '' }}>
                  {{ $drug->drug_name }} ({{ $drug->current_stock }} in stock)
                </option>
                @endforeach
              </select>
              @error('items.'.$index.'.drug_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <input type="hidden" name="items[{{ $index }}][quantity_prescribed]" value="{{ $item->quantity_dispensed ?? 1 }}" />
            <div class="col-md-2">
              <label class="form-label">Qty to Dispense *</label>
              <input type="number" class="form-control qty-input @error('items.'.$index.'.quantity_dispensed') is-invalid @enderror" name="items[{{ $index }}][quantity_dispensed]"
                value="1" min="1" onchange="updateTotal()" required />
              @error('items.'.$index.'.quantity_dispensed') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
              <label class="form-label">Unit Price</label>
              <div class="input-group">
                <span class="input-group-text">LKR</span>
                <input type="text" class="form-control price-display" id="price_{{ $index }}" disabled value="0.00" />
              </div>
            </div>
          </div>
        </div>
        @endforeach

        @else
        {{-- Manual Dispensing --}}
        <div id="medicinesContainer">
          <div class="border rounded p-4 mb-4" id="medicine_0">
            <div class="row g-3 align-items-center">
              <div class="col-md-4">
                <label class="form-label">Drug *</label>
                <select class="form-select @error('items.0.drug_id') is-invalid @enderror" name="items[0][drug_id]"
                  onchange="updatePrice(this, 0)" required>
                  <option value="">Select Drug</option>
                  @foreach($drugs as $drug)
                  <option value="{{ $drug->id }}"
                    data-price="{{ $drug->unit_price }}"
                    data-stock="{{ $drug->current_stock }}">
                    {{ $drug->drug_name }} {{ $drug->strength ? '('.$drug->strength.')' : '' }}
                    ({{ $drug->current_stock }} in stock)
                  </option>
                  @endforeach
                </select>
                @error('items.0.drug_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <input type="hidden" name="items[0][quantity_prescribed]" value="1" />
              <div class="col-md-3">
                <label class="form-label">Quantity *</label>
                <input type="number" class="form-control qty-input @error('items.0.quantity_dispensed') is-invalid @enderror" name="items[0][quantity_dispensed]"
                  value="1" min="1" onchange="updateTotal()" required />
                @error('items.0.quantity_dispensed') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-3">
                <label class="form-label">Unit Price</label>
                <div class="input-group">
                  <span class="input-group-text">LKR</span>
                  <input type="text" class="form-control price-display" id="price_0" disabled value="0.00" />
                </div>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-label-danger w-100" onclick="removeItem(0)">
                  <i class="icon-base ti tabler-trash me-1"></i> Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-sm btn-primary mb-4" onclick="addMedicine()">
          <i class="icon-base ti tabler-plus me-1"></i> Add Drug
        </button>
        @endif

        {{-- Total --}}
        <div class="d-flex justify-content-end mt-4">
          <div class="border rounded p-4" style="min-width:250px;">
            <div class="d-flex justify-content-between mb-2">
              <span class="fw-medium">Total Amount:</span>
              <span class="fw-bold text-primary" id="grandTotal">LKR 0.00</span>
            </div>
          </div>
        </div>

      </div>

      <hr class="my-6" />

      {{-- Notes --}}
      <div class="card-body px-0 pb-0">
        <label class="text-heading mb-2 fw-medium">Notes:</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
          placeholder="Additional notes...">{{ old('notes') }}</textarea>
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
            Dispense
          </span>
        </button>
        <a href="{{ route('pharmacy.dispensing.index') }}" class="btn btn-label-secondary d-grid w-100">
          Cancel
        </a>
      </div>
    </div>

    {{-- Summary --}}
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Summary</h6>
      </div>
      <div class="card-body" id="summaryList">
        <p class="text-muted small mb-0">No items selected</p>
      </div>
    </div>
  </div>

</div>
</form>

@section('page-script')
<script>
let itemCount = 1;

const drugs = {
  @foreach($drugs as $drug)
  {{ $drug->id }}: { name: '{{ addslashes($drug->drug_name) }}', price: {{ $drug->unit_price }}, stock: {{ $drug->current_stock }} },
  @endforeach
};

function updatePrice(select, index) {
  const drugId = select.value;
  const priceEl = document.getElementById(`price_${index}`);
  if (drugId && drugs[drugId]) {
    priceEl.value = parseFloat(drugs[drugId].price).toFixed(2);
  } else {
    priceEl.value = '0.00';
  }
  updateTotal();
}

function updateTotal() {
  let total = 0;
  let summaryHtml = '';

  document.querySelectorAll('.drug-select, select[name^="items"]').forEach((select, i) => {
    const drugId = select.value;
    if (!drugId) return;

    const qtyEl = select.closest('.row, .border').querySelector('.qty-input');
    const qty   = qtyEl ? parseInt(qtyEl.value) || 1 : 1;

    if (drugs[drugId]) {
      const subtotal = drugs[drugId].price * qty;
      total += subtotal;
      summaryHtml += `
        <div class="d-flex justify-content-between mb-2">
          <small>${drugs[drugId].name} x${qty}</small>
          <small>LKR ${subtotal.toLocaleString('en', {minimumFractionDigits:2})}</small>
        </div>
      `;
    }
  });

  document.getElementById('grandTotal').textContent = 'LKR ' + total.toLocaleString('en', {minimumFractionDigits: 2});
  document.getElementById('summaryList').innerHTML  = summaryHtml || '<p class="text-muted small mb-0">No items selected</p>';
}

function addMedicine() {
  const container = document.getElementById('medicinesContainer');
  const i = itemCount++;

  const options = Object.entries(drugs).map(([id, d]) =>
    `<option value="${id}" data-price="${d.price}" data-stock="${d.stock}">
      ${d.name} (${d.stock} in stock)
    </option>`
  ).join('');

  container.insertAdjacentHTML('beforeend', `
    <div class="border rounded p-4 mb-4" id="medicine_${i}">
      <div class="row g-3 align-items-center">
        <div class="col-md-4">
          <label class="form-label">Drug *</label>
          <select class="form-select" name="items[${i}][drug_id]"
            onchange="updatePrice(this, ${i})" required>
            <option value="">Select Drug</option>
            ${options}
          </select>
        </div>
        <input type="hidden" name="items[${i}][quantity_prescribed]" value="1" />
        <div class="col-md-3">
          <label class="form-label">Quantity *</label>
          <input type="number" class="form-control qty-input" name="items[${i}][quantity_dispensed]"
            value="1" min="1" onchange="updateTotal()" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Unit Price</label>
          <div class="input-group">
            <span class="input-group-text">LKR</span>
            <input type="text" class="form-control price-display" id="price_${i}" disabled value="0.00" />
          </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="button" class="btn btn-label-danger w-100" onclick="removeItem(${i})">
            <i class="icon-base ti tabler-trash me-1"></i> Remove
          </button>
        </div>
      </div>
    </div>
  `);
}

function removeItem(i) {
  document.getElementById(`medicine_${i}`)?.remove();
  updateTotal();
}
</script>
@endsection

@endsection