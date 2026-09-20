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

@section('title', 'HMS - Confirm Password')

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

          <!-- Logo -->
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

          <h4 class="mb-1">Confirm Password 🔒</h4>
          <p class="mb-6 text-muted">This is a secure area. Please confirm your password before continuing.</p>

          @if($errors->any())
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="icon-base ti tabler-alert-circle"></i>
            {{ $errors->first() }}
          </div>
          @endif

          <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-6">
              <label class="form-label" for="password">Password</label>
              <input
                type="password"
                id="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password"
                placeholder="············"
                autofocus
                required/>
              @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">
                <i class="icon-base ti tabler-lock me-1"></i> Confirm
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
