@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Access Denied - HMS')

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card" style="max-width:480px;margin:0 auto;">
        <div class="card-body p-6 text-center">

          <div class="avatar avatar-xl mb-4 mx-auto">
            <span class="avatar-initial rounded-circle bg-label-danger"
              style="width:80px;height:80px;font-size:2rem;">
              <i class="icon-base ti tabler-lock text-danger"></i>
            </span>
          </div>

          <h4 class="mb-2">Access Denied</h4>
          <p class="text-muted mb-4">
            {{ $exception->getMessage() ?: 'You are not authorized to access this resource.' }}
          </p>

          <div class="p-3 rounded mb-4" style="background:rgba(var(--bs-warning-rgb),0.1);border:1px solid rgba(var(--bs-warning-rgb),0.2);">
            <p class="mb-0 small text-warning">
              <i class="icon-base ti tabler-info-circle me-1"></i>
              If you believe this is a mistake, please contact your system administrator.
            </p>
          </div>

          <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
            class="btn btn-primary w-100 mb-3">
            <i class="icon-base ti tabler-arrow-left me-1"></i> Go Back
          </a>

          <a href="{{ route('dashboard') }}" class="btn btn-label-secondary w-100">
            <i class="icon-base ti tabler-smart-home me-1"></i> Back to Dashboard
          </a>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
