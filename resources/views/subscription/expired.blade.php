@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Subscription Expired - HMS')

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card" style="max-width:480px;margin:0 auto;">
        <div class="card-body p-6 text-center">

          <div class="avatar avatar-xl mb-4 mx-auto">
            <span class="avatar-initial rounded-circle bg-label-danger"
              style="width:80px;height:80px;font-size:2rem;">
              <i class="icon-base ti tabler-calendar-off text-danger"></i>
            </span>
          </div>

          <h4 class="mb-2">Subscription Expired</h4>
          <p class="text-muted mb-1">Access to Hospital Management System has been paused.</p>

          @php $sub = \App\Models\Subscription::resolveCurrent(); @endphp
          @if($sub)
          <p class="text-muted mb-4">
            The subscription for <strong>{{ $sub->hospital_name }}</strong> expired on
            <strong>{{ $sub->expires_at?->format('d M Y') }}</strong>.
          </p>
          @endif

          <div class="p-3 rounded mb-4" style="background:rgba(var(--bs-warning-rgb),0.1);border:1px solid rgba(var(--bs-warning-rgb),0.2);">
            <p class="mb-0 small text-warning">
              <i class="icon-base ti tabler-info-circle me-1"></i>
              Please contact your system administrator to renew the subscription and restore access.
            </p>
          </div>

          @role('super_admin')
          <a href="{{ route('subscription.index') }}" class="btn btn-primary w-100 mb-3">
            <i class="icon-base ti tabler-crown me-1"></i> Manage Subscriptions
          </a>
          @endrole

          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-label-secondary w-100">
              <i class="icon-base ti tabler-logout me-1"></i> Logout
            </button>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection