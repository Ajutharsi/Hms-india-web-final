{{-- Shared Create/Edit Plan offcanvas. --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="{{ $id }}" style="width:520px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">{{ $title }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form id="{{ $formId }}" method="POST" @if($action) action="{{ $action }}" @endif>
      @csrf
      @if($method) @method($method) @endif

      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="form-label">Plan Key *</label>
          <input type="text" class="form-control" name="key" placeholder="e.g. growth" pattern="[A-Za-z0-9_\-]+" required
            @if($method) {{-- key is immutable once other subscriptions may reference it --}} readonly @endif />
        </div>
        <div class="col-6">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Plan Name *</label>
          <input type="text" class="form-control" name="name" placeholder="e.g. Growth" required />
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="2" placeholder="What this plan is for..."></textarea>
        </div>
        <div class="col-6">
          <label class="form-label">Price *</label>
          <div class="input-group">
            <span class="input-group-text">{{ config('hospital.currency', 'LKR') }}</span>
            <input type="number" step="0.01" min="0" class="form-control" name="price" required />
          </div>
        </div>
        <div class="col-6">
          <label class="form-label">Billing Cycle *</label>
          <select class="form-select" name="billing_cycle" required>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Trial Period (days)</label>
          <input type="number" min="0" class="form-control" name="trial_days" placeholder="Leave blank for no trial" />
        </div>
      </div>

      <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Limits <small class="text-body-secondary normal-case">(blank = unlimited)</small></p>
      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="form-label">User Limit</label>
          <input type="number" min="0" class="form-control" name="user_limit" />
        </div>
        <div class="col-6">
          <label class="form-label">Doctor Limit</label>
          <input type="number" min="0" class="form-control" name="doctor_limit" />
        </div>
        <div class="col-6">
          <label class="form-label">Patient Limit</label>
          <input type="number" min="0" class="form-control" name="patient_limit" />
        </div>
        <div class="col-6">
          <label class="form-label">Storage Limit (MB)</label>
          <input type="number" min="0" class="form-control" name="storage_limit_mb" />
        </div>
      </div>

      <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:0.5px;">Enabled Modules</p>
      <div class="row g-2 mb-6">
        @foreach(array_keys($allModules) as $key)
        <div class="col-6">
          <div class="form-check">
            <input class="form-check-input plan-module-check" type="checkbox" name="modules[]" value="{{ $key }}" id="{{ $formId }}_{{ $key }}" checked />
            <label class="form-check-label small" for="{{ $formId }}_{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
          </div>
        </div>
        @endforeach
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save Plan</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>
