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
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-logo">
                <i class="icon-base ti tabler-heartbeat text-primary" style="font-size:32px"></i>
              </span>
              <span class="app-brand-text text-heading fw-bold fs-4">
                Hospital MS
              </span>
            </a>
          </div>

          <h4 class="mb-1">Welcome! 👋</h4>
          <p class="mb-6 text-muted">Sign in to Hospital Management System</p>

          {{-- Error message --}}
          @if($errors->any())
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="icon-base ti tabler-alert-circle"></i>
            {{ $errors->first() }}
          </div>
          @endif

          {{-- Success message --}}
          @if(session('status'))
          <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="icon-base ti tabler-check"></i>
            {{ session('status') }}
          </div>
          @endif

          
          <form id="formAuthentication" class="mb-4"
            action="{{ route('login') }}" method="POST">
            @csrf

            {{-- Email --}}
            <div class="mb-6">
              <label for="email" class="form-label">Email Address</label>
              <input
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="admin@hospital.com"
                autofocus
                required/>
              @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Password --}}
            <div class="mb-6">
              <label class="form-label" for="password">Password</label>
              <div class="input-group input-group-merge">
                <input
                  type="password"
                  id="password"
                  class="form-control @error('password') is-invalid @enderror"
                  name="password"
                  placeholder="············"
                  required/>
                <span class="input-group-text cursor-pointer">
                  <i class="icon-base ti tabler-eye-off"></i>
                </span>
              </div>
              @error('password')
              <div class="text-danger mt-1" style="font-size:13px">{{ $message }}</div>
              @enderror
            </div>

            {{-- Remember me --}}
            <div class="my-8">
              <div class="d-flex justify-content-between">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox"
                    name="remember" id="remember-me"/>
                  <label class="form-check-label" for="remember-me">
                    Remember Me
                  </label>
                </div>
               
                <a href="{{ route('password.request') }}">
                  <!-- <p class="mb-0">Forgot Password?</p> -->
                </a>
              </div>
            </div>

            {{-- Submit --}}
            <div class="mb-6">
  <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" type="submit">
    <i class="icon-base ti tabler-login" style="font-size:1.1rem;"></i>
    <span>Sign In</span>
  </button>
</div>

          </form>

        

        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const passwordInput = document.getElementById('password');
  const toggleIcon    = document.querySelector('.input-group-text .icon-base');
  const toggleBtn     = document.querySelector('.input-group-text');

  if (toggleBtn && passwordInput && toggleIcon) {
    toggleBtn.addEventListener('click', function () {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('tabler-eye-off');
        toggleIcon.classList.add('tabler-eye');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('tabler-eye');
        toggleIcon.classList.add('tabler-eye-off');
      }
    });
  }
});
</script>
@endsection
@endsection


