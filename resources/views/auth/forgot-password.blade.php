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

@section('title', 'HMS - Login')

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
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">Hospital MS</span>
            </a>
          </div>

          <h4 class="mb-1">Forgot Password? 🔒</h4>
          <p class="mb-6">Enter your email and we'll send you instructions to reset your password</p>

          {{-- Status Message --}}
          @if(session('status'))
          <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="icon-base ti tabler-check"></i>
            {{ session('status') }}
          </div>
          @endif

          {{-- Errors --}}
          @if($errors->any())
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
            <i class="icon-base ti tabler-alert-circle"></i>
            {{ $errors->first() }}
          </div>
          @endif

          
          <form id="formAuthentication" class="mb-6"
            action="{{ route('password.email') }}" method="POST">
            @csrf

            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">Email</label>
              <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Enter your email"
                autofocus
                required />
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <button class="btn btn-primary d-grid w-100" type="submit">
              Send Reset Link
            </button>

          </form>

          <div class="text-center">
            <a href="{{ route('login') }}" class="d-flex justify-content-center">
              <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
              Back to login
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection