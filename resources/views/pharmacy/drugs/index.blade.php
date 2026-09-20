@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Drug Master - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('page-style')
<style>
#drugsTable tbody tr:hover {
  background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
  cursor: pointer;
}
.stock-low { color: var(--bs-danger); font-weight: 700; }
.stock-ok  { color: var(--bs-success); font-weight: 600; }
</style>
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Alerts --}}
@if($lowStockCount > 0 || $expiryCount > 0)
<div class="row g-4 mb-6">
  @if($lowStockCount > 0)
  <div class="col-md-6">
    <div class="alert alert-danger d-flex align-items-center mb-0">
      <i class="icon-base ti tabler-alert-triangle me-3" style="font-size:1.5rem;"></i>
      <div>
        <strong>Low Stock Alert!</strong>
        <p class="mb-0">{{ $lowStockCount }} drug(s) below minimum stock level.</p>
      </div>
    </div>
  </div>
  @endif
  @if($expiryCount > 0)
  <div class="col-md-6">
    <div class="alert alert-warning d-flex align-items-center mb-0">
      <i class="icon-base ti tabler-clock me-3" style="font-size:1.5rem;"></i>
      <div>
        <strong>Expiry Alert!</strong>
        <p class="mb-0">{{ $expiryCount }} drug(s) expiring within 30 days.</p>
      </div>
    </div>
  </div>
  @endif
</div>
@endif

{{-- Stats --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <p class="mb-1">Total Drugs</p>
              <h4 class="mb-1">{{ \App\Models\Drug::count() }}</h4>
              <p class="mb-0">In drug master</p>
            </div>
            <span class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-pill icon-28px"></i>
              </span>
            </span>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <p class="mb-1">Active Drugs</p>
              <h4 class="mb-1">{{ \App\Models\Drug::where('status','active')->count() }}</h4>
              <p class="mb-0">Currently active</p>
            </div>
            <span class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-circle-check icon-28px"></i>
              </span>
            </span>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <p class="mb-1">Low Stock</p>
              <h4 class="mb-1 text-danger">{{ $lowStockCount }}</h4>
              <p class="mb-0">Need restock</p>
            </div>
            <span class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="icon-base ti tabler-alert-triangle icon-28px"></i>
              </span>
            </span>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="mb-1">Near Expiry</p>
              <h4 class="mb-1 text-warning">{{ $expiryCount }}</h4>
              <p class="mb-0">Within 30 days</p>
            </div>
            <span class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-clock icon-28px"></i>
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Drug List Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Drug Master</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('pharmacy.dispensing.index') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-clipboard-list me-1"></i> Dispensing
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDrugAdd">
        <i class="icon-base ti tabler-plus me-1"></i> Add Drug
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="drugsTable">
      <thead>
        <tr>
          <th></th>
          <th>Code</th>
          <th>Drug Name</th>
          <th>Category</th>
          <th>Form</th>
          <th>Strength</th>
          <th>Stock</th>
          <th>Price (LKR)</th>
          <th>Expiry</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Drug Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDrugAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Drug</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('pharmacy.drugs.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Drug Name *</label>
          <input type="text" class="form-control @error('drug_name') is-invalid @enderror" name="drug_name" placeholder="e.g. Paracetamol" value="{{ old('drug_name') }}" required />
          @error('drug_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Generic Name</label>
          <input type="text" class="form-control @error('generic_name') is-invalid @enderror" name="generic_name" placeholder="e.g. Acetaminophen" value="{{ old('generic_name') }}" />
          @error('generic_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Linked Inventory Item</label>
          <select class="form-select @error('inventory_item_id') is-invalid @enderror" name="inventory_item_id">
            <option value="">None</option>
            @foreach ($inventoryItems as $item)
            <option value="{{ $item->id }}" @selected(old('inventory_item_id')==$item->id)>{{ $item->item_name }} ({{ $item->item_code }})</option>
            @endforeach
          </select>
          @error('inventory_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category *</label>
          <select class="form-select @error('category') is-invalid @enderror" name="category" required>
            <option value="">Select</option>
            @foreach (['Analgesic','Antibiotic','Antiviral','Antifungal','Antihypertensive','Antidiabetic','Antihistamine','Antacid','Vitamin','Other'] as $cat)
            <option value="{{ $cat }}" @selected(old('category')==$cat)>{{ $cat=='Vitamin' ? 'Vitamin/Supplement' : $cat }}</option>
            @endforeach
          </select>
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Form *</label>
          <select class="form-select @error('form') is-invalid @enderror" name="form" required>
            <option value="">Select</option>
            @foreach (['Tablet','Capsule','Syrup','Injection','Cream','Drops','Inhaler','Powder','Other'] as $frm)
            <option value="{{ $frm }}" @selected(old('form')==$frm)>{{ $frm }}</option>
            @endforeach
          </select>
          @error('form') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Strength</label>
          <input type="text" class="form-control @error('strength') is-invalid @enderror" name="strength" placeholder="e.g. 500mg, 10ml" value="{{ old('strength') }}" />
          @error('strength') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Manufacturer</label>
          <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" name="manufacturer" value="{{ old('manufacturer') }}" />
          @error('manufacturer') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Unit Price (LKR) *</label>
          <input type="number" class="form-control @error('unit_price') is-invalid @enderror" name="unit_price" placeholder="0.00" step="0.01" min="0" value="{{ old('unit_price') }}" required />
          @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Initial Stock</label>
          <input type="number" min="0" class="form-control @error('current_stock') is-invalid @enderror" name="current_stock" placeholder="0" value="{{ old('current_stock') }}" />
          @error('current_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Min Stock Level</label>
          <input type="number" min="0" class="form-control @error('min_stock_level') is-invalid @enderror" name="min_stock_level" value="{{ old('min_stock_level', 10) }}" />
          @error('min_stock_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Expiry Date</label>
          <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" name="expiry_date" value="{{ old('expiry_date') }}" />
          @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status">
            <option value="active" @selected(old('status', 'active')=='active')>Active</option>
            <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Edit Drug Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDrugEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Drug</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="editDrugForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_drug_id" id="edit_drug_id" value="{{ old('_edit_drug_id') }}" />
        <div class="mb-4">
          <label class="form-label">Drug Name *</label>
          <input type="text" class="form-control @error('drug_name') is-invalid @enderror" name="drug_name" id="edit_drug_name" value="{{ old('drug_name') }}" required />
          @error('drug_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Generic Name</label>
          <input type="text" class="form-control @error('generic_name') is-invalid @enderror" name="generic_name" id="edit_generic_name" value="{{ old('generic_name') }}" />
          @error('generic_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Linked Inventory Item</label>
          <select class="form-select @error('inventory_item_id') is-invalid @enderror" name="inventory_item_id" id="edit_inventory_item_id">
            <option value="">None</option>
            @foreach ($inventoryItems as $item)
            <option value="{{ $item->id }}">{{ $item->item_name }} ({{ $item->item_code }})</option>
            @endforeach
          </select>
          @error('inventory_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Category *</label>
          <select class="form-select @error('category') is-invalid @enderror" name="category" id="edit_category" required>
            <option value="">Select</option>
            @foreach (['Analgesic','Antibiotic','Antiviral','Antifungal','Antihypertensive','Antidiabetic','Antihistamine','Antacid','Vitamin','Other'] as $cat)
            <option value="{{ $cat }}" @selected(old('category')==$cat)>{{ $cat=='Vitamin' ? 'Vitamin/Supplement' : $cat }}</option>
            @endforeach
          </select>
          @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Form *</label>
          <select class="form-select @error('form') is-invalid @enderror" name="form" id="edit_form" required>
            <option value="">Select</option>
            @foreach (['Tablet','Capsule','Syrup','Injection','Cream','Drops','Inhaler','Powder','Other'] as $frm)
            <option value="{{ $frm }}" @selected(old('form')==$frm)>{{ $frm }}</option>
            @endforeach
          </select>
          @error('form') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Strength</label>
          <input type="text" class="form-control @error('strength') is-invalid @enderror" name="strength" id="edit_strength" value="{{ old('strength') }}" />
          @error('strength') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Manufacturer</label>
          <input type="text" class="form-control @error('manufacturer') is-invalid @enderror" name="manufacturer" id="edit_manufacturer" value="{{ old('manufacturer') }}" />
          @error('manufacturer') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Unit Price (LKR) *</label>
          <input type="number" class="form-control @error('unit_price') is-invalid @enderror" name="unit_price" id="edit_unit_price" step="0.01" min="0" value="{{ old('unit_price') }}" required />
          @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Min Stock Level</label>
          <input type="number" min="0" class="form-control @error('min_stock_level') is-invalid @enderror" name="min_stock_level" id="edit_min_stock_level" value="{{ old('min_stock_level') }}" />
          @error('min_stock_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Expiry Date</label>
          <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" name="expiry_date" id="edit_expiry_date" value="{{ old('expiry_date') }}" />
          @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status" id="edit_status">
            <option value="active" @selected(old('status')=='active')>Active</option>
            <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

  {{-- Add Stock Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDrugStock">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Add Stock</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="addStockForm" method="POST">
        @csrf
        <input type="hidden" name="_stock_drug_id" id="stock_drug_id" value="{{ old('_stock_drug_id') }}" />
        <div class="mb-4">
          <label class="form-label">Drug</label>
          <input type="text" class="form-control" id="stock_drug_name" disabled />
        </div>
        <div class="mb-4">
          <label class="form-label">Current Stock</label>
          <input type="text" class="form-control" id="stock_current" disabled />
        </div>
        <div class="mb-4">
          <label class="form-label">Quantity to Add *</label>
          <input type="number" class="form-control @error('quantity') is-invalid @enderror" name="quantity" placeholder="0" min="1" value="{{ old('quantity') }}" required />
          @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Reason *</label>
          <select class="form-select @error('reason') is-invalid @enderror" name="reason" required>
            <option value="Purchase" @selected(old('reason', 'Purchase')=='Purchase')>Purchase</option>
            <option value="Return" @selected(old('reason')=='Return')>Return</option>
            <option value="Adjustment" @selected(old('reason')=='Adjustment')>Adjustment</option>
            <option value="Transfer" @selected(old('reason')=='Transfer')>Transfer</option>
          </select>
          @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Reference (PO/Invoice No)</label>
          <input type="text" class="form-control @error('reference') is-invalid @enderror" name="reference" placeholder="PO-2026-001" value="{{ old('reference') }}" />
          @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-success">
            <i class="icon-base ti tabler-plus me-1"></i> Add Stock
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

</div>

@section('page-script')
<script>
@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_stock_drug_id'))
    document.getElementById('addStockForm').action = '/pharmacy/drugs/{{ old('_stock_drug_id') }}/stock';
    addStock({{ old('_stock_drug_id') }});
  @elseif(old('_edit_drug_id'))
    document.getElementById('editDrugForm').action = '/pharmacy/drugs/{{ old('_edit_drug_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasDrugEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasDrugAdd')).show();
  @endif
});
@endif
document.addEventListener('DOMContentLoaded', function () {
  $('#drugsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('pharmacy.drugs.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'drug_code', name: 'drug_code' },
      { data: 'name_info', name: 'drug_name' },
      { data: 'category_badge', name: 'category' },
      { data: 'form_badge', name: 'form' },
      { data: 'strength', name: 'strength', defaultContent: 'N/A' },
      { data: 'stock_display', name: 'current_stock' },
      { data: 'unit_price_fmt', name: 'unit_price' },
      { data: 'expiry_display', name: 'expiry_date' },
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

function editDrug(id) {
  fetch(`/pharmacy/drugs/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('editDrugForm');
      f.action = `/pharmacy/drugs/${id}`;
      f.querySelector('[name="_edit_drug_id"]').value  = id;
      f.querySelector('[name="drug_name"]').value      = d.drug_name || '';
      f.querySelector('[name="generic_name"]').value   = d.generic_name || '';
      f.querySelector('[name="inventory_item_id"]').value = d.inventory_item_id || '';
      f.querySelector('[name="category"]').value       = d.category || '';
      f.querySelector('[name="form"]').value           = d.form || '';
      f.querySelector('[name="strength"]').value       = d.strength || '';
      f.querySelector('[name="manufacturer"]').value   = d.manufacturer || '';
      f.querySelector('[name="unit_price"]').value     = d.unit_price || '';
      f.querySelector('[name="min_stock_level"]').value = d.min_stock_level || '';
      f.querySelector('[name="expiry_date"]').value    = d.expiry_date ? d.expiry_date.split('T')[0] : '';
      f.querySelector('[name="status"]').value         = d.status || '';
      new bootstrap.Offcanvas(document.getElementById('offcanvasDrugEdit')).show();
    });
}

function addStock(id) {
  fetch(`/pharmacy/drugs/${id}/json`)
    .then(res => res.json())
    .then(d => {
      const f = document.getElementById('addStockForm');
      f.action = `/pharmacy/drugs/${id}/stock`;
      document.getElementById('stock_drug_id').value    = id;
      document.getElementById('stock_drug_name').value = d.drug_name;
      document.getElementById('stock_current').value   = d.current_stock;
      new bootstrap.Offcanvas(document.getElementById('offcanvasDrugStock')).show();
    });
}

function deleteDrug(id) {
  confirmDelete(`/pharmacy/drugs/${id}`, 'Drug', function() {
    $('#drugsTable').DataTable().ajax.reload(null, false);
  });
}
</script>
@endsection

@endsection