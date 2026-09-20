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

@section('title', 'HMS - Register')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
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

          <h4 class="mb-1">Create an account 🚀</h4>
          <p class="mb-6 text-muted">Register a patient account to book appointments</p>

          {{-- Error message --}}
          @if($errors->any())
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="icon-base ti tabler-alert-circle"></i>
            {{ $errors->first() }}
          </div>
          @endif

          <form id="formAuthentication" class="mb-4"
            action="{{ route('register') }}" method="POST">
            @csrf

            {{-- Name --}}
            <div class="mb-6">
              <label for="name" class="form-label">Full Name</label>
              <input
                type="text"
                class="form-control @error('name') is-invalid @enderror"
                id="name"
                name="name"
                value="{{ old('name') }}"
                placeholder="John Doe"
                autofocus
                required/>
              @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Email --}}
            <div class="mb-6">
              <label for="email" class="form-label">Email Address</label>
              <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@example.com"
                required/>
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Password --}}
            <div class="mb-6">
              <label class="form-label" for="password">Password</label>
              <input
                type="password"
                id="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password"
                placeholder="············"
                required/>
              @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="mb-6">
              <label class="form-label" for="password_confirmation">Confirm Password</label>
              <input
                type="password"
                id="password_confirmation"
                class="form-control"
                name="password_confirmation"
                placeholder="············"
                required/>
            </div>

            {{-- Submit --}}
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">
                <i class="icon-base ti tabler-user-plus me-1"></i> Sign Up
              </button>
            </div>

          </form>

          <p class="text-center">
            <span>Already have an account?</span>
            <a href="{{ route('login') }}">
              <span>Sign in instead</span>
            </a>
          </p>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
