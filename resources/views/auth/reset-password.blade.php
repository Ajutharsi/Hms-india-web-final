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

          <h4 class="mb-1">Reset Password 🔒</h4>
          <p class="mb-6">
            <span class="fw-medium">Your new password must be different from previously used passwords</span>
          </p>

          {{-- Errors --}}
          @if($errors->any())
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
            <i class="icon-base ti tabler-alert-circle"></i>
            {{ $errors->first() }}
          </div>
          @endif

         
          <form id="formAuthentication" action="{{ route('password.update') }}" method="POST">
            @csrf

           
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <input type="hidden" name="email" value="{{ $request->email }}">

            {{-- New Password --}}
            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password">New Password</label>
              <div class="input-group input-group-merge">
                <input
                  type="password"
                  id="password"
                  class="form-control @error('password') is-invalid @enderror"
                  name="password"
                  placeholder="············"
                  required />
                <span class="input-group-text cursor-pointer">
                  <i class="icon-base ti tabler-eye-off"></i>
                </span>
              </div>
              @error('password')
              <div class="text-danger mt-1" style="font-size:13px">{{ $message }}</div>
              @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password_confirmation">Confirm Password</label>
              <div class="input-group input-group-merge">
                <input
                  type="password"
                  id="password_confirmation"
                  class="form-control"
                  name="password_confirmation"
                  placeholder="············"
                  required />
                <span class="input-group-text cursor-pointer">
                  <i class="icon-base ti tabler-eye-off"></i>
                </span>
              </div>
            </div>

            <button class="btn btn-primary d-grid w-100 mb-6" type="submit">
              Set new password
            </button>

            <div class="text-center">
              <a href="{{ route('login') }}" class="d-flex justify-content-center">
                <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
                Back to login
              </a>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection