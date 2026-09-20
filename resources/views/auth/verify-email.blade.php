@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$configData['layout'] = 'blank';
$configData['theme'] = $configData['theme'] ?? 'light';
$configData['textDirection'] = $configData['textDirection'] ?? 'ltr';
$configData['skinName'] = 'default';
$configData['semiDark'] = false;
$configData['navbarType'] = '';
$configData['headerType'] = '';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Verify Email - HMS')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">

          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-logo">
                <i class="icon-base ti tabler-heartbeat text-primary" style="font-size:32px"></i>
              </span>
              <span class="app-brand-text text-heading fw-bold fs-4">
                Hospital MS
              </span>
            </a>
          </div>

          <h4 class="mb-1">Verify Your Email</h4>
          <p class="mb-6 text-muted">
            Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
          </p>

          @if (session('status') == 'verification-link-sent')
          <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="icon-base ti tabler-check"></i>
            A new verification link has been sent to the email address you provided in your profile settings.
          </div>
          @endif

          <div class="d-flex align-items-center justify-content-between">
            <form method="POST" action="{{ route('verification.send') }}">
              @csrf
              <button type="submit" class="btn btn-primary">
                Resend Verification Email
              </button>
            </form>

            <div class="d-flex align-items-center gap-3">
              <a href="{{ route('profile.show') }}" class="text-muted small">Edit Profile</a>

              <form method="POST" action="{{ route('logout') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-label-secondary btn-sm">
                  Log Out
                </button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
