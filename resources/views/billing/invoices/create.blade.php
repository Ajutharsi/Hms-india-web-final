@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'New Invoice - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice.scss')
@endsection

@section('content')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

<form action="{{ route('billing.invoices.store') }}" method="POST">
@csrf

@if ($errors->any())
<div class="alert alert-danger">
  <strong>Please fix the following:</strong>
  <ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul>
</div>
@endif

@if(isset($appointment))
  <input type="hidden" name="appointment_id" value="{{ $appointment->id }}" />
@endif

<div class="row invoice-add">

  {{-- Invoice Form --}}
  <div class="col-lg-9 col-12 mb-lg-0 mb-6">
    <div class="card invoice-preview-card p-sm-12 p-6">

      {{-- Header --}}
      <div class="card-body invoice-preview-header rounded">
        <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
          <div class="mb-md-0 mb-6">
            <div class="d-flex align-items-center mb-4 gap-2">
              <i class="icon-base ti tabler-building-hospital text-primary" style="font-size:2rem;"></i>
              <span class="app-brand-text fw-bold fs-4">Hospital MS</span>
            </div>
            <p class="mb-1">123 Hospital Street, Colombo</p>
            <p class="mb-1">Sri Lanka 10001</p>
            <p class="mb-0">+94 11 234 5678</p>
          </div>
          <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
            <dl class="row mb-0 gx-4">
              <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                <span class="h5 text-capitalize mb-0 text-nowrap">Invoice</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="INV-{{ date('Y') }}-AUTO" />
              </dd>
              <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Date:</span>
              </dt>
              <dd class="col-sm-7">
                <input type="text" class="form-control" disabled value="{{ now()->format('d M Y') }}" />
              </dd>
              <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                <span class="fw-normal">Due Date:</span>
              </dt>
              <dd class="col-sm-7 mb-0">
                <input type="date" class="form-control @error('due_date') is-invalid @enderror" name="due_date"
                  value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" />
                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </dd>
            </dl>
          </div>
        </div>
      </div>

      {{-- Patient --}}
      <div class="card-body px-0">
        <div class="row g-4">
          <div class="col-md-6">
            <h6 class="mb-3">Bill To: *</h6>
            @if(isset($appointment))
              <p class="mb-1 fw-medium">{{ $appointment->patient->name }}</p>
              <p class="mb-1 text-muted">MRN: {{ $appointment->patient->mrn }}</p>
              <p class="mb-1 text-muted">Phone: {{ $appointment->patient->phone ?? 'N/A' }}</p>
              <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}" />
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
          @if(isset($appointment))
          <div class="col-md-6">
            <h6 class="mb-3">Appointment:</h6>
            <p class="mb-1 fw-medium">{{ $appointment->appointment_id }}</p>
            <p class="mb-1 text-muted">Dr. {{ $appointment->doctor->name }}</p>
            <p class="mb-1 text-muted">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}</p>
          </div>
          @endif
        </div>
      </div>

      <hr class="mt-0 mb-6" />

      {{-- Items --}}
      <div class="card-body pt-0 px-0">
        <h6 class="mb-4">Invoice Items</h6>

        <div id="itemsContainer">
          <div class="border rounded p-4 mb-4" id="item_0">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Item Name *</label>
                @if($services->count() > 0)
                <select class="form-select service-select mb-2" onchange="selectService(this, 0)">
                  <option value="">-- Select Service --</option>
                  @foreach($services->groupBy('category') as $category => $categoryServices)
                  <optgroup label="{{ $category }}">
                    @foreach($categoryServices as $service)
                    <option value="{{ $service->service_name }}"
                      data-price="{{ $service->price }}"
                      data-type="{{ $service->category }}">
                      {{ $service->service_name }} — {{ $currency }} {{ number_format($service->price, 2) }}
                    </option>
                    @endforeach
                  </optgroup>
                  @endforeach
                </select>
                @endif
                <input type="text" class="form-control @error('items.0.item_name') is-invalid @enderror" name="items[0][item_name]"
                  placeholder="e.g. Consultation Fee" required />
                <input type="hidden" name="items[0][item_type]" id="item_type_0" value="Service" />
                @error('items.0.item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-3">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="items[0][description]"
                  placeholder="Optional" />
              </div>
              <div class="col-md-1">
                <label class="form-label">Qty</label>
                <input type="number" class="form-control qty-input @error('items.0.quantity') is-invalid @enderror" name="items[0][quantity]"
                  value="1" min="1" onchange="calculateTotal()" required />
                @error('items.0.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Unit Price ({{ $currency }})</label>
                <input type="number" class="form-control price-input @error('items.0.unit_price') is-invalid @enderror" name="items[0][unit_price]"
                  id="unit_price_0" value="0" step="0.01" min="0" onchange="calculateTotal()" required />
                @error('items.0.unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-2">
                <label class="form-label">Discount ({{ $currency }})</label>
                <input type="number" class="form-control discount-input" name="items[0][discount]"
                  value="0" step="0.01" min="0" onchange="calculateTotal()" />
              </div>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-sm btn-primary mb-6" onclick="addItem()">
          <i class="icon-base ti tabler-plus me-1"></i> Add Item
        </button>

        {{-- Totals --}}
        <div class="d-flex justify-content-end">
          <div class="border rounded p-4" style="min-width:300px;">
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal:</span>
              <span class="fw-medium" id="subtotalDisplay">{{ $currency }} 0.00</span>
            </div>
            <div class="d-flex justify-content-between mb-2 align-items-center">
              <span>Discount ({{ $currency }}):</span>
              <input type="number" class="form-control form-control-sm @error('discount') is-invalid @enderror" name="discount"
                id="globalDiscount" value="0" step="0.01" min="0" style="width:120px;" onchange="calculateTotal()" />
            </div>
            <div class="d-flex justify-content-between mb-2 align-items-center">
              <span>Tax ({{ $currency }}):</span>
              <input type="number" class="form-control form-control-sm @error('tax') is-invalid @enderror" name="tax"
                id="globalTax" value="0" step="0.01" min="0" style="width:120px;" onchange="calculateTotal()" />
            </div>
            <hr class="my-2" />
            <div class="d-flex justify-content-between">
              <span class="fw-bold">Total:</span>
              <span class="fw-bold text-primary" id="totalDisplay">{{ $currency }} 0.00</span>
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
            Save Invoice
          </span>
        </button>
        <a href="{{ route('billing.invoices.index') }}" class="btn btn-label-secondary d-grid w-100">
          Cancel
        </a>
      </div>
    </div>

    {{-- Summary --}}
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Invoice Summary</h6>
      </div>
      <div class="card-body" id="summaryList">
        <p class="text-muted small mb-0">No items added</p>
      </div>
    </div>
  </div>

</div>
</form>

@section('page-script')
<script>
let itemCount = 1;
const currency = '{{ $currency }}';

const services = {
  @foreach($services as $service)
  '{{ addslashes($service->service_name) }}': {
    price: {{ $service->price }},
    type: '{{ $service->category }}'
  },
  @endforeach
};

const hasServices = {{ $services->count() > 0 ? 'true' : 'false' }};

function selectService(select, index) {
  const val     = select.value;
  const priceEl = document.getElementById(`unit_price_${index}`);
  const typeEl  = document.getElementById(`item_type_${index}`);
  const nameEl  = select.nextElementSibling;

  if (val && services[val]) {
    priceEl.value = services[val].price;
    typeEl.value  = services[val].type;
    nameEl.value  = val;
  }
  calculateTotal();
}

function calculateTotal() {
  let subtotal    = 0;
  let summaryHtml = '';

  document.querySelectorAll('#itemsContainer .border').forEach((row) => {
    const nameEl     = row.querySelector('input[type="text"].form-control');
    const qtyEl      = row.querySelector('.qty-input');
    const priceEl    = row.querySelector('.price-input');
    const discountEl = row.querySelector('.discount-input');

    const name     = nameEl?.value || '';
    const qty      = parseInt(qtyEl?.value) || 1;
    const price    = parseFloat(priceEl?.value) || 0;
    const discount = parseFloat(discountEl?.value) || 0;
    const total    = (price * qty) - discount;

    subtotal += total;

    if (name) {
      summaryHtml += `
        <div class="d-flex justify-content-between mb-2">
          <small>${name} x${qty}</small>
          <small>${currency} ${total.toLocaleString('en', {minimumFractionDigits:2})}</small>
        </div>
      `;
    }
  });

  const globalDiscount = parseFloat(document.getElementById('globalDiscount').value) || 0;
  const globalTax      = parseFloat(document.getElementById('globalTax').value) || 0;
  const total          = subtotal - globalDiscount + globalTax;

  document.getElementById('subtotalDisplay').textContent = currency + ' ' + subtotal.toLocaleString('en', {minimumFractionDigits:2});
  document.getElementById('totalDisplay').textContent    = currency + ' ' + total.toLocaleString('en', {minimumFractionDigits:2});
  document.getElementById('summaryList').innerHTML       = summaryHtml || '<p class="text-muted small mb-0">No items added</p>';
}

function addItem() {
  const container = document.getElementById('itemsContainer');
  const i         = itemCount++;

  const serviceOptions = hasServices
    ? `<select class="form-select service-select mb-2" onchange="selectService(this, ${i})">
        <option value="">-- Select Service --</option>
        ${Object.entries(services).map(([name, s]) =>
          `<option value="${name}" data-price="${s.price}" data-type="${s.type}">
            ${name} — ${currency} ${parseFloat(s.price).toLocaleString()}
          </option>`
        ).join('')}
      </select>`
    : '';

  container.insertAdjacentHTML('beforeend', `
    <div class="border rounded p-4 mb-4" id="item_${i}">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Item Name *</label>
          ${serviceOptions}
          <input type="text" class="form-control" name="items[${i}][item_name]"
            placeholder="e.g. Lab Fee" required />
          <input type="hidden" name="items[${i}][item_type]" id="item_type_${i}" value="Service" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Description</label>
          <input type="text" class="form-control" name="items[${i}][description]"
            placeholder="Optional" />
        </div>
        <div class="col-md-1">
          <label class="form-label">Qty</label>
          <input type="number" class="form-control qty-input" name="items[${i}][quantity]"
            value="1" min="1" onchange="calculateTotal()" required />
        </div>
        <div class="col-md-2">
          <label class="form-label">Unit Price (${currency})</label>
          <input type="number" class="form-control price-input" name="items[${i}][unit_price]"
            id="unit_price_${i}" value="0" step="0.01" min="0" onchange="calculateTotal()" required />
        </div>
        <div class="col-md-2">
          <label class="form-label">
            Discount
            <button type="button" class="btn btn-sm btn-label-danger ms-1 py-0 px-1"
              onclick="removeItem(${i})">
              <i class="icon-base ti tabler-trash icon-sm"></i>
            </button>
          </label>
          <input type="number" class="form-control discount-input" name="items[${i}][discount]"
            value="0" step="0.01" min="0" onchange="calculateTotal()" />
        </div>
      </div>
    </div>
  `);
}

function removeItem(i) {
  document.getElementById(`item_${i}`)?.remove();
  calculateTotal();
}
</script>
@endsection

@endsection