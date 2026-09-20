@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Audit & Activity Centre - HMS')

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Audit & Activity Centre']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Audit & Activity Centre</h4>
    <p class="mb-0 text-body-secondary">Complete visibility of important platform activity, security events, and configuration changes.</p>
  </div>
</div>

<div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
  <div class="col">
    <x-kpi-card label="Total Activities" :value="$kpis['totalActivities']" icon="tabler-list-details" color="primary" :href="route('super-admin.audit.activity')" />
  </div>
  <div class="col">
    <x-kpi-card label="Today's Activities" :value="$kpis['todayActivities']" icon="tabler-calendar-event" color="info" :href="route('super-admin.audit.activity', ['from_date' => now()->format('Y-m-d')])" />
  </div>
  <div class="col">
    <x-kpi-card label="Failed Actions" :value="$kpis['failedActions']" icon="tabler-alert-triangle" color="danger" :href="route('super-admin.audit.security', ['status' => 'failure'])" />
  </div>
  <div class="col">
    <x-kpi-card label="Security Events" :value="$kpis['securityEvents']" icon="tabler-shield-exclamation" color="warning" :href="route('super-admin.audit.security')" />
  </div>
  <div class="col">
    <x-kpi-card label="Login Events" :value="$kpis['loginEvents']" icon="tabler-login" color="success" :href="route('super-admin.audit.activity', ['event_type' => 'login'])" />
  </div>
  <div class="col">
    <x-kpi-card label="Impersonation Events" :value="$kpis['impersonationEvents']" icon="tabler-user-shield" color="secondary" :href="route('super-admin.audit.impersonation')" />
  </div>
  <div class="col">
    <x-kpi-card label="Configuration Changes" :value="$kpis['configurationChanges']" icon="tabler-settings-cog" color="primary" :href="route('super-admin.audit.config-history')" />
  </div>
  <div class="col">
    <x-kpi-card label="Export Activities" :value="$kpis['exportActivities']" icon="tabler-download" color="info" :href="route('super-admin.audit.activity', ['event_type' => 'export'])" />
  </div>
</div>

<div class="row g-4 mt-2">
  <div class="col-md-6 col-xl-3">
    <a href="{{ route('super-admin.audit.activity') }}" class="text-decoration-none">
      <div class="card h-100"><div class="card-body text-center">
        <i class="icon-base ti tabler-timeline icon-32px text-primary mb-2"></i>
        <h6 class="mb-1">Activity Log</h6>
        <small class="text-body-secondary">Timeline & table view of every platform action</small>
      </div></div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <a href="{{ route('super-admin.audit.security') }}" class="text-decoration-none">
      <div class="card h-100"><div class="card-body text-center">
        <i class="icon-base ti tabler-shield-exclamation icon-32px text-warning mb-2"></i>
        <h6 class="mb-1">Security Events</h6>
        <small class="text-body-secondary">Failed logins, permission failures, sensitive changes</small>
      </div></div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <a href="{{ route('super-admin.audit.impersonation') }}" class="text-decoration-none">
      <div class="card h-100"><div class="card-body text-center">
        <i class="icon-base ti tabler-user-shield icon-32px text-secondary mb-2"></i>
        <h6 class="mb-1">Impersonation History</h6>
        <small class="text-body-secondary">Every "Login as Hospital Admin" session</small>
      </div></div>
    </a>
  </div>
  <div class="col-md-6 col-xl-3">
    <a href="{{ route('super-admin.audit.config-history') }}" class="text-decoration-none">
      <div class="card h-100"><div class="card-body text-center">
        <i class="icon-base ti tabler-settings-cog icon-32px text-primary mb-2"></i>
        <h6 class="mb-1">Configuration History</h6>
        <small class="text-body-secondary">Settings, plan & subscription changes, before/after</small>
      </div></div>
    </a>
  </div>
</div>

@endsection
