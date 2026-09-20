@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Plan Feature Mapping - HMS')

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Feature Management', 'url' => route('super-admin.features.index')], ['label' => 'Plan Feature Mapping']]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Plan Feature Mapping</h4>
    <p class="mb-0 text-body-secondary">View and assign optional features to each subscription plan. Reuses the existing Plan Catalog's <code>modules</code> field directly — no separate subscription system.</p>
  </div>
</div>

@if(empty($plans))
<div class="card"><div class="card-body text-center text-body-secondary py-6">No subscription plans exist yet. Create one from the Plan Catalog first.</div></div>
@endif

<div class="row g-4">
  @foreach($plans as $plan)
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0">{{ $plan['name'] }}</h6>
        <span class="badge bg-label-{{ $plan['status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($plan['status']) }}</span>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('super-admin.features.plans.update', $plan['id']) }}" class="plan-modules-form">
          @csrf
          @method('PATCH')
          <div class="row row-cols-2 g-2 mb-3">
            @foreach($assignable as $module)
            <div class="col">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $module->key }}"
                  id="plan{{ $plan['id'] }}_{{ $module->key }}"
                  {{ collect($plan['modules'])->pluck('key')->contains($module->key) ? 'checked' : '' }}>
                <label class="form-check-label" for="plan{{ $plan['id'] }}_{{ $module->key }}">{{ $module->name }}</label>
              </div>
            </div>
            @endforeach
          </div>
          <button type="submit" class="btn btn-sm btn-primary">Save Feature Assignment</button>
        </form>
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.plan-modules-form').forEach(form => {
    form.addEventListener('submit', function (e) {
      if (!confirm('Save this plan\'s feature assignment? This affects which features hospitals on this plan can access.')) {
        e.preventDefault();
      }
    });
  });
});
</script>
@endsection
