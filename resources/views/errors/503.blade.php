@php
$configData = Helper::appClasses();
$maintenanceMessage = null;
try {
    $maintenanceMessage = \App\Models\PlatformSetting::first()?->maintenance_message;
} catch (\Throwable $e) {
    // DB unavailable — fall back to the generic message below.
}
@endphp

@extends('layouts/blankLayout')

@section('title', 'Down for Maintenance - HMS')

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card" style="max-width:480px;margin:0 auto;">
        <div class="card-body p-6 text-center">

          <div class="avatar avatar-xl mb-4 mx-auto">
            <span class="avatar-initial rounded-circle bg-label-warning"
              style="width:80px;height:80px;font-size:2rem;">
              <i class="icon-base ti tabler-tools text-warning"></i>
            </span>
          </div>

          <h4 class="mb-2">Down for Maintenance</h4>
          <p class="text-muted mb-0">
            {{ $maintenanceMessage ?: "We're performing scheduled maintenance. Please check back shortly." }}
          </p>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
