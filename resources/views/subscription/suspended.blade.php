@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Account Suspended - HMS')

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card" style="max-width:480px;margin:0 auto;">
        <div class="card-body p-6 text-center">

          <div class="avatar avatar-xl mb-4 mx-auto">
            <span class="avatar-initial rounded-circle bg-label-warning"
              style="width:80px;height:80px;font-size:2rem;">
              <i class="icon-base ti tabler-lock-square text-warning"></i>
            </span>
          </div>

          <h4 class="mb-2">Account Suspended</h4>
          <p class="text-muted mb-1">Access to Hospital Management System has been suspended.</p>

          @php $sub = \App\Models\Subscription::resolveCurrent(); @endphp
          @if($sub)
          <p class="text-muted mb-4">
            The account for <strong>{{ $sub->hospital_name }}</strong> has been suspended.
          </p>
          @endif

          <div class="p-3 rounded mb-4" style="background:rgba(var(--bs-danger-rgb),0.1);border:1px solid rgba(var(--bs-danger-rgb),0.2);">
            <p class="mb-0 small text-danger">
              <i class="icon-base ti tabler-info-circle me-1"></i>
              Please contact your system administrator for assistance in restoring access.
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