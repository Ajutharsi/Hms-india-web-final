@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Inventory - HMS')

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

{{-- Stats --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ \App\Models\InventoryItem::count() }}</h4>
              <p class="mb-0">Total Items</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary text-heading">
                <i class="icon-base ti tabler-package icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ \App\Models\InventoryItem::whereColumn('current_stock', '<=', 'min_stock_level')->count() }}</h4>
              <p class="mb-0">Low Stock</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-alert-triangle icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $currency }} {{ number_format(\App\Models\InventoryItem::selectRaw('SUM(current_stock * unit_price) as total')->value('total') ?? 0, 2) }}</h4>
              <p class="mb-0">Stock Value</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-currency-dollar icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ \App\Models\InventoryItem::where('status','active')->count() }}</h4>
              <p class="mb-0">Active Items</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-circle-check icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Items Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Inventory Items</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('inventory.po.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-shopping-cart me-1"></i> Purchase Orders
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasItemAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Item
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="itemsTable">
      <thead>
        <tr>
          <th></th>
          <th>Item Code</th>
          <th>Item Name</th>
          <th>Category</th>
          <th>Stock</th>
          <th>Unit Price</th>
          <th>Supplier</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Item Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasItemAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Inventory Item</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('inventory.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Item Name *</label>
          <input type="text" class="form-control @error('item_name') is-invalid @enderror" name="item_name" value="{{ old('item_name') }}" required />
          @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Category *</label>
            <select class="form-select @error('category') is-invalid @enderror" name="category" required>
              <option value="medical_supplies" @selected(old('category','medical_supplies')=='medical_supplies')>Medical Supplies</option>
              <option value="equipment" @selected(old('category')=='equipment')>Equipment</option>
              <option value="stationery" @selected(old('category')=='stationery')>Stationery</option>
              <option value="housekeeping" @selected(old('category')=='housekeeping')>Housekeeping</option>
            </select>
            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Unit *</label>
            <input type="text" class="form-control @error('unit') is-invalid @enderror" name="unit" placeholder="pieces, boxes, liters" value="{{ old('unit') }}" required />
            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Current Stock *</label>
            <input type="number" min="0" class="form-control @error('current_stock') is-invalid @enderror" name="current_stock" value="{{ old('current_stock', 0) }}" required />
            @error('current_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Min Stock Level *</label>
            <input type="number" min="0" class="form-control @error('min_stock_level') is-invalid @enderror" name="min_stock_level" value="{{ old('min_stock_level', 5) }}" required />
            @error('min_stock_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Unit Price ({{ $currency }}) *</label>
            <input type="number" step="0.01" min="0" class="form-control @error('unit_price') is-invalid @enderror" name="unit_price" value="{{ old('unit_price') }}" required />
            @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Location</label>
            <input type="text" class="form-control @error('location') is-invalid @enderror" name="location" placeholder="Store Room, Ward A" value="{{ old('location') }}" />
            @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="mb-6">
          <label class="form-label">Supplier</label>
          <input type="text" class="form-control @error('supplier') is-invalid @enderror" name="supplier" value="{{ old('supplier') }}" />
          @error('supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Edit Item Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasItemEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Inventory Item</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editItemForm" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
          <label class="form-label">Item Name *</label>
          <input type="text" class="form-control" name="item_name" id="edit_item_name" required />
        </div>
        <div class="row">
          <div class="col-6 mb-4">
            <label class="form-label">Category *</label>
            <select class="form-select" name="category" id="edit_category" required>
              <option value="medical_supplies">Medical Supplies</option>
              <option value="equipment">Equipment</option>
              <option value="stationery">Stationery</option>
              <option value="housekeeping">Housekeeping</option>
            </select>
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Unit *</label>
            <input type="text" class="form-control" name="unit" id="edit_unit" required />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Current Stock *</label>
            <input type="number" min="0" class="form-control" name="current_stock" id="edit_current_stock" required />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Min Stock Level *</label>
            <input type="number" min="0" class="form-control" name="min_stock_level" id="edit_min_stock_level" required />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Unit Price ({{ $currency }}) *</label>
            <input type="number" step="0.01" min="0" class="form-control" name="unit_price" id="edit_unit_price" required />
          </div>
          <div class="col-6 mb-4">
            <label class="form-label">Location</label>
            <input type="text" class="form-control" name="location" id="edit_location" />
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Supplier</label>
          <input type="text" class="form-control" name="supplier" id="edit_supplier" />
        </div>
        <div class="mb-6">
          <label class="form-label">Status</label>
          <select class="form-select" name="status" id="edit_status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Update
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('page-script')
<script>
let itemsTable;
const currency = '{{ $currency }}';

document.addEventListener('DOMContentLoaded', function () {
  itemsTable = $('#itemsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('inventory.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'item_code', name: 'item_code' },
      { data: 'item_name', name: 'item_name' },
      { data: 'category_badge', name: 'category' },
      { data: 'stock_display', name: 'current_stock' },
      { data: 'unit_price_fmt', name: 'unit_price' },
      { data: 'supplier', name: 'supplier', defaultContent: '—' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[2, 'asc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });
});

function editItem(id) {
  fetch(`/inventory/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editItemForm');
      f.action = `/inventory/${id}`;
      document.getElementById('edit_item_name').value = d.item_name;
      document.getElementById('edit_category').value = d.category;
      document.getElementById('edit_unit').value = d.unit;
      document.getElementById('edit_current_stock').value = d.current_stock;
      document.getElementById('edit_min_stock_level').value = d.min_stock_level;
      document.getElementById('edit_unit_price').value = d.unit_price;
      document.getElementById('edit_location').value = d.location || '';
      document.getElementById('edit_supplier').value = d.supplier || '';
      document.getElementById('edit_status').value = d.status;
      new bootstrap.Offcanvas(document.getElementById('offcanvasItemEdit')).show();
    });
}

function deleteItem(id) {
  confirmDelete(`/inventory/${id}`, 'Item', function() {
    itemsTable.ajax.reload(null, false);
  });
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasItemAdd')).show();
});
@endif
</script>
@endsection

@endsection
