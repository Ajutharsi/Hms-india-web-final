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

@section('title', 'Two Factor Authentication - HMS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

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

          <h4 class="mb-1">Two-Factor Authentication</h4>
          <p class="mb-6 text-muted">
            Enter the authentication code from your authenticator app, or one of your emergency recovery codes.
          </p>

          @if ($errors->any())
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="icon-base ti tabler-alert-circle"></i>
            {{ $errors->first() }}
          </div>
          @endif

          <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf

            <div class="mb-6">
              <label for="code" class="form-label">Authentication Code</label>
              <input id="code" type="text" inputmode="numeric" class="form-control" name="code" autofocus autocomplete="one-time-code" placeholder="Leave blank to use a recovery code instead" />
            </div>

            <div class="mb-6">
              <label for="recovery_code" class="form-label">Recovery Code</label>
              <input id="recovery_code" type="text" class="form-control" name="recovery_code" autocomplete="one-time-code" />
            </div>

            <button type="submit" class="btn btn-primary w-100">
              Log in
            </button>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
