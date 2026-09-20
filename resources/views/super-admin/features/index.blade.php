@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Feature Management - HMS')

@section('page-style')
<style>
  .feature-kpi-link { display: block; width: 100%; text-align: left; text-decoration: none; color: inherit; border: none; background: none; padding: 0; }
  .feature-kpi-card { transition: transform 0.15s ease, box-shadow 0.15s ease; cursor: pointer; }
  .feature-kpi-link:hover .feature-kpi-card, .feature-kpi-link:focus-visible .feature-kpi-card { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Feature Management']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Feature Management Centre</h4>
    <p class="mb-0 text-body-secondary">Control platform-wide modules and feature availability.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('super-admin.features.catalog') }}" class="btn btn-primary"><i class="icon-base ti tabler-list-details me-1"></i> Feature Catalog</a>
  </div>
</div>

<div class="row g-4 mb-6">
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.catalog') }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Total Features</span>
          <h4 class="mb-0 mt-1">{{ $stats['total'] }}</h4>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.catalog', ['status' => 'enabled']) }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Enabled Features</span>
          <h4 class="mb-0 mt-1 text-success">{{ $stats['enabled'] }}</h4>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.catalog', ['status' => 'disabled']) }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Disabled Features</span>
          <h4 class="mb-0 mt-1 text-danger">{{ $stats['disabled'] }}</h4>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.groups') }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Feature Groups</span>
          <h4 class="mb-0 mt-1">{{ $stats['groups'] }}</h4>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.catalog', ['tier' => 'core']) }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Core Modules</span>
          <h4 class="mb-0 mt-1">{{ $stats['core'] }}</h4>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.catalog', ['tier' => 'optional']) }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Optional Modules</span>
          <h4 class="mb-0 mt-1">{{ $stats['optional'] }}</h4>
        </div>
      </div>
    </a>
  </div>
  <div class="col-sm-6 col-xl-3">
    <a href="{{ route('super-admin.features.catalog', ['tier' => 'experimental']) }}" class="feature-kpi-link">
      <div class="card feature-kpi-card">
        <div class="card-body">
          <span class="text-heading">Experimental Features</span>
          <h4 class="mb-0 mt-1">{{ $stats['experimental'] }}</h4>
        </div>
      </div>
    </a>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-4">
    <a href="{{ route('super-admin.features.catalog') }}" class="quick-action-btn d-block p-4">
      <span class="quick-action-icon mb-2"><i class="icon-base ti tabler-list-details icon-22px"></i></span>
      <h6 class="mb-1">Feature Catalog</h6>
      <small class="text-body-secondary">Search, filter, and control every feature.</small>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('super-admin.features.groups') }}" class="quick-action-btn d-block p-4">
      <span class="quick-action-icon mb-2"><i class="icon-base ti tabler-category icon-22px"></i></span>
      <h6 class="mb-1">Feature Groups</h6>
      <small class="text-body-secondary">Browse features organized by module group.</small>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('super-admin.features.plans') }}" class="quick-action-btn d-block p-4">
      <span class="quick-action-icon mb-2"><i class="icon-base ti tabler-crown icon-22px"></i></span>
      <h6 class="mb-1">Plan Feature Mapping</h6>
      <small class="text-body-secondary">View and assign features to subscription plans.</small>
    </a>
  </div>
</div>

<style>
.quick-action-btn { display: block; border-radius: 12px; border: 1px solid var(--bs-border-color); text-decoration: none; color: var(--bs-body-color); transition: all 0.15s ease; background: var(--bs-card-bg); }
.quick-action-btn:hover { border-color: var(--bs-primary); background: rgba(105,108,255,0.05); color: var(--bs-primary); }
.quick-action-btn .quick-action-icon { width: 44px; height: 44px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: rgba(105,108,255,0.08); color: var(--bs-primary); }
</style>

@endsection
