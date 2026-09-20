@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Purchase Orders - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

@php $currency = $hospitalSettings->currency ?? 'LKR'; @endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <div>
      <h5 class="card-title mb-0">Purchase Orders</h5>
      <small class="text-muted">Total: {{ \App\Models\PurchaseOrder::count() }} | Pending Receipt: {{ \App\Models\PurchaseOrder::whereIn('status',['draft','submitted','approved'])->count() }}</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('inventory.index') }}" class="btn btn-label-secondary">
        <i class="icon-base ti tabler-arrow-left me-1"></i> Inventory
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasPoAdd">
        <i class="icon-base ti tabler-plus me-1"></i> New Purchase Order
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="poTable">
      <thead>
        <tr>
          <th></th>
          <th>PO No</th>
          <th>Supplier</th>
          <th>Items</th>
          <th>Total Amount</th>
          <th>Status</th>
          <th>Order Date</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add PO Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPoAdd" data-bs-scroll="true" style="width:500px;max-width:92vw;">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">New Purchase Order</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('inventory.po.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Supplier *</label>
          <input type="text" class="form-control @error('supplier') is-invalid @enderror" name="supplier" value="{{ old('supplier') }}" required />
          @error('supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Expected Date</label>
          <input type="date" class="form-control @error('expected_date') is-invalid @enderror" name="expected_date" value="{{ old('expected_date') }}" />
          @error('expected_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <label class="form-label">Items *</label>
        <div id="poItemsWrap"></div>
        <button type="button" class="btn btn-sm btn-label-primary mb-4" onclick="addPoItemRow()">
          <i class="icon-base ti tabler-plus me-1"></i> Add Item
        </button>

        <div class="mb-4">
          <strong>Total: <span id="poTotalDisplay">{{ $currency }} 0.00</span></strong>
        </div>

        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Save
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('page-script')
<script>
let poTable;
const currency = '{{ $currency }}';
const inventoryItems = @json($items);
let poRowIndex = 0;

function addPoItemRow() {
  const wrap = document.getElementById('poItemsWrap');
  const idx = poRowIndex++;
  const row = document.createElement('div');
  row.className = 'row g-2 mb-2 align-items-end po-item-row';
  row.innerHTML = `
    <div class="col-5">
      <select class="form-select form-select-sm po-item-select" name="items[${idx}][inventory_item_id]" required>
        <option value="">Select Item</option>
        ${inventoryItems.map(i => `<option value="${i.id}" data-price="${i.unit_price}">${i.item_name}</option>`).join('')}
      </select>
    </div>
    <div class="col-3">
      <input type="number" min="1" class="form-control form-control-sm po-item-qty" name="items[${idx}][quantity]" placeholder="Qty" required />
    </div>
    <div class="col-3">
      <input type="number" step="0.01" min="0" class="form-control form-control-sm po-item-price" name="items[${idx}][unit_price]" placeholder="Unit Price" required />
    </div>
    <div class="col-1">
      <button type="button" class="btn btn-sm btn-icon btn-label-danger" onclick="this.closest('.po-item-row').remove(); recalcPoTotal();"><i class="icon-base ti tabler-x"></i></button>
    </div>
  `;
  wrap.appendChild(row);

  row.querySelector('.po-item-select').addEventListener('change', function () {
    const price = this.selectedOptions[0]?.dataset.price || 0;
    row.querySelector('.po-item-price').value = price;
    recalcPoTotal();
  });
  row.querySelector('.po-item-qty').addEventListener('input', recalcPoTotal);
  row.querySelector('.po-item-price').addEventListener('input', recalcPoTotal);
}

function recalcPoTotal() {
  let total = 0;
  document.querySelectorAll('.po-item-row').forEach(row => {
    const qty = parseFloat(row.querySelector('.po-item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.po-item-price').value) || 0;
    total += qty * price;
  });
  document.getElementById('poTotalDisplay').textContent = currency + ' ' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}

function statusColor(s) {
  return { draft: 'secondary', submitted: 'info', approved: 'primary', received: 'success', cancelled: 'danger' }[s] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  addPoItemRow();

  poTable = $('#poTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('inventory.po.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'po_no', name: 'po_no' },
      { data: 'supplier', name: 'supplier' },
      { data: 'items_count', name: 'items_count', orderable: false },
      { data: 'total_amount_fmt', name: 'total_amount' },
      { data: 'status_badge', name: 'status' },
      { data: 'order_date', name: 'order_date' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[6, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });
});

function receivePo(id) {
  fetch(`/inventory/purchase-orders/${id}/receive`, {
    method: 'PUT',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
  }).then(res => res.json()).then(res => {
    if (res.error) { alert(res.error); return; }
    poTable.ajax.reload(null, false);
  });
}

function deletePo(id) {
  confirmDelete(`/inventory/purchase-orders/${id}`, 'Purchase Order', function() {
    poTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasPoAdd')).show();
});
@endif
</script>
@endsection

@endsection
