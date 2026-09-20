@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Food Intake - HMS')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

  {{-- Left Sidebar --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Patient Card --}}
    <div class="card mb-4">
      <div class="card-body pt-6">
        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-lg mb-3">
            <span class="avatar-initial rounded-circle bg-label-primary"
              style="font-size:1.5rem;width:64px;height:64px;">
              {{ strtoupper(substr($patient->name, 0, 1)) }}
            </span>
          </div>
          <h6 class="mb-1">{{ $patient->name }}</h6>
          <small class="text-muted mb-2">{{ $patient->mrn }}</small>
        </div>

        <div class="mb-4">
          <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span class="text-muted" style="font-size:0.8rem;">Ward</span>
            <span class="fw-medium" style="font-size:0.85rem;">{{ $patient->currentAdmission?->ward?->name ?? 'N/A' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span class="text-muted" style="font-size:0.8rem;">Bed</span>
            <span class="fw-medium" style="font-size:0.85rem;">{{ $patient->currentAdmission?->bed?->bed_no ?? 'N/A' }}</span>
          </div>
        </div>

        <a href="{{ route('food-intake.index') }}" class="btn btn-label-secondary btn-sm w-100">
          <i class="icon-base ti tabler-arrow-left me-1"></i> Back
        </a>
      </div>
    </div>

    {{-- Diet Plan Card --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-clipboard-list me-2 text-primary"></i>Diet Plan</h6>
      </div>
      <div class="card-body">
        @if($patient->dietPlan)
        <div class="mb-3">
          <span class="badge bg-label-primary text-capitalize mb-2">{{ str_replace('_', ' ', $patient->dietPlan->diet_type) }}</span>
          @if($patient->dietPlan->restrictions)
          <p class="mb-1 small"><strong>Restrictions:</strong> {{ $patient->dietPlan->restrictions }}</p>
          @endif
          @if($patient->dietPlan->notes)
          <p class="mb-0 small text-muted">{{ $patient->dietPlan->notes }}</p>
          @endif
        </div>
        @else
        <p class="text-muted small mb-3">No diet plan set.</p>
        @endif

        @can('edit patients')
        <button class="btn btn-sm btn-primary w-100" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDietPlan">
          <i class="icon-base ti tabler-edit me-1"></i> {{ $patient->dietPlan ? 'Update' : 'Set' }} Diet Plan
        </button>
        @endcan
      </div>
    </div>

  </div>

  {{-- Right Content --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    {{-- Today's Meals --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-calendar-event me-2 text-success"></i>Today's Meals — {{ now()->format('d M Y') }}</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @php
            $todayLogs = $foodLogs->get(today()->toDateString(), collect())->keyBy('meal_type');
            $mealsInfo = [
              'breakfast' => ['icon' => 'tabler-coffee', 'color' => 'warning', 'label' => 'Breakfast'],
              'lunch'     => ['icon' => 'tabler-soup', 'color' => 'info', 'label' => 'Lunch'],
              'snacks'    => ['icon' => 'tabler-apple', 'color' => 'success', 'label' => 'Snacks'],
              'dinner'    => ['icon' => 'tabler-tools-kitchen-2', 'color' => 'primary', 'label' => 'Dinner'],
            ];
          @endphp
          @foreach($mealsInfo as $mealKey => $info)
          @php $log = $todayLogs->get($mealKey); @endphp
          <div class="col-md-6">
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium d-flex align-items-center gap-2">
                  <i class="icon-base ti {{ $info['icon'] }} text-{{ $info['color'] }}"></i>
                  {{ $info['label'] }}
                </span>
                @if($log)
                <span class="badge bg-label-{{ $log->status === 'given' ? 'success' : ($log->status === 'refused' ? 'danger' : ($log->status === 'partial' ? 'warning' : 'secondary')) }} text-capitalize">
                  {{ $log->status }}
                </span>
                @else
                <span class="badge bg-label-secondary">Not logged</span>
                @endif
              </div>
              @if($log && $log->notes)
              <p class="mb-2 small text-muted">{{ $log->notes }}</p>
              @endif
              @can('edit patients')
              <button onclick="openMealModal('{{ $mealKey }}', '{{ $info['label'] }}', '{{ $log?->status }}', '{{ $log?->quantity }}', `{{ $log?->notes }}`)"
                class="btn btn-sm btn-label-primary w-100">
                <i class="icon-base ti tabler-edit me-1"></i> {{ $log ? 'Update' : 'Log' }} {{ $info['label'] }}
              </button>
              @endcan
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- History --}}
    <div class="card">
      <div class="card-header py-3">
        <h6 class="mb-0"><i class="icon-base ti tabler-history me-2 text-info"></i>Meal History</h6>
      </div>
      <div class="card-body p-0">
        @forelse($foodLogs as $date => $logs)
        <div class="px-4 pt-3 pb-1" style="background:var(--bs-body-bg);">
          <small class="fw-medium text-muted">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</small>
        </div>
        @foreach($logs as $log)
        <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="text-capitalize fw-medium small">{{ $log->meal_type }}</span>
            <span class="badge bg-label-{{ $log->status === 'given' ? 'success' : ($log->status === 'refused' ? 'danger' : ($log->status === 'partial' ? 'warning' : 'secondary')) }} text-capitalize" style="font-size:0.68rem;">
              {{ $log->status }}
            </span>
            @if($log->quantity)
            <small class="text-muted text-capitalize">({{ $log->quantity }})</small>
            @endif
          </div>
          <small class="text-muted">{{ $log->loggedBy->name ?? 'N/A' }} — {{ $log->created_at->format('h:i A') }}</small>
        </div>
        @endforeach
        @empty
        <p class="text-muted text-center py-4 mb-0">No meal history found.</p>
        @endforelse
      </div>
    </div>

  </div>

</div>

{{-- Diet Plan Offcanvas --}}
@can('edit patients')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDietPlan">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">{{ $patient->dietPlan ? 'Update' : 'Set' }} Diet Plan</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('food-intake.diet-plan.store', $patient->id) }}" method="POST">
      @csrf
      <div class="mb-4">
        <label class="form-label">Diet Type *</label>
        <select class="form-select @error('diet_type') is-invalid @enderror" name="diet_type" required>
          <option value="normal" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'normal' ? 'selected' : '' }}>Normal</option>
          <option value="diabetic" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'diabetic' ? 'selected' : '' }}>Diabetic</option>
          <option value="liquid" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'liquid' ? 'selected' : '' }}>Liquid</option>
          <option value="soft" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'soft' ? 'selected' : '' }}>Soft</option>
          <option value="low_salt" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'low_salt' ? 'selected' : '' }}>Low Salt</option>
          <option value="npo" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'npo' ? 'selected' : '' }}>NPO (Nil by Mouth)</option>
          <option value="other" {{ old('diet_type', $patient->dietPlan?->diet_type) === 'other' ? 'selected' : '' }}>Other</option>
        </select>
        @error('diet_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Restrictions</label>
        <textarea class="form-control @error('restrictions') is-invalid @enderror" name="restrictions" rows="2" placeholder="e.g. No sugar, low sodium">{{ old('restrictions', $patient->dietPlan?->restrictions) }}</textarea>
        @error('restrictions') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-6">
        <label class="form-label">Notes</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes', $patient->dietPlan?->notes) }}</textarea>
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
@endcan

{{-- Log Meal Offcanvas --}}
@can('edit patients')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasLogMeal">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title" id="mealModalTitle">Log Meal</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('food-intake.log-meal', $patient->id) }}" method="POST">
      @csrf
      <input type="hidden" name="meal_type" id="meal_type_input" value="{{ old('meal_type') }}" />
      <div class="mb-4">
        <label class="form-label">Status *</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status" id="meal_status_input" required>
          <option value="given" @selected(old('status', 'given')=='given')>Given</option>
          <option value="partial" @selected(old('status')=='partial')>Partial</option>
          <option value="refused" @selected(old('status')=='refused')>Refused</option>
          <option value="pending" @selected(old('status')=='pending')>Pending</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Quantity Consumed</label>
        <select class="form-select @error('quantity') is-invalid @enderror" name="quantity" id="meal_quantity_input">
          <option value="" @selected(old('quantity')=='')>N/A</option>
          <option value="full" @selected(old('quantity')=='full')>Full</option>
          <option value="half" @selected(old('quantity')=='half')>Half</option>
          <option value="none" @selected(old('quantity')=='none')>None</option>
        </select>
        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-6">
        <label class="form-label">Notes</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="meal_notes_input" rows="2" placeholder="e.g. Patient vomited after meal">{{ old('notes') }}</textarea>
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
@endcan

<script>
function openMealModal(mealType, mealLabel, status, quantity, notes) {
  document.getElementById('mealModalTitle').textContent = 'Log ' + mealLabel;
  document.getElementById('meal_type_input').value = mealType;
  document.getElementById('meal_status_input').value = status || 'given';
  document.getElementById('meal_quantity_input').value = quantity || '';
  document.getElementById('meal_notes_input').value = notes || '';
  new bootstrap.Offcanvas(document.getElementById('offcanvasLogMeal')).show();
}

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  @if(old('meal_type'))
    new bootstrap.Offcanvas(document.getElementById('offcanvasLogMeal')).show();
  @elseif(old('diet_type'))
    new bootstrap.Offcanvas(document.getElementById('offcanvasDietPlan')).show();
  @endif
});
@endif
</script>

@endsection