@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', $feature . ' - HMS')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card text-center" style="border-radius:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
      <div class="card-body p-6">

        <div class="avatar avatar-xl mb-4 mx-auto">
          <span class="avatar-initial rounded-circle bg-label-primary"
            style="width:80px;height:80px;font-size:2rem;">
            <i class="icon-base ti tabler-tools text-primary"></i>
          </span>
        </div>

        <h4 class="mb-2">{{ $feature }}</h4>
        <p class="text-muted mb-4">
          This detail view is coming soon. Check back after a future update.
        </p>

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
@endsection
