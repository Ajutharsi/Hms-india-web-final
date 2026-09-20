<!DOCTYPE html>
@php
  use Illuminate\Support\Str;
  use App\Helpers\Helpers;

  $menuFixed =
      $configData['layout'] === 'vertical'
          ? $menuFixed ?? ''
          : ($configData['layout'] === 'front'
              ? ''
              : $configData['headerType']);
  $navbarType =
      $configData['layout'] === 'vertical'
          ? $configData['navbarType']
          : ($configData['layout'] === 'front'
              ? 'layout-navbar-fixed'
              : '');
  $isFront = ($isFront ?? '') == true ? 'Front' : '';
  $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

  // Get skin name from configData - only applies to admin layouts
  $isAdminLayout = !Str::contains($configData['layout'] ?? '', 'front');
  $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';

  // Get semiDark value from configData - only applies to admin layouts
  $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);

  // Generate primary color CSS if color is set
  $primaryColorCSS = '';
  if (isset($configData['color']) && $configData['color']) {
      $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
  }

@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
  dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}" data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{ url('/') }}" data-framework="laravel" data-template="{{ $configData['layout'] }}-menu-template"
  data-bs-theme="{{ $configData['theme'] }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    @yield('title') | {{ config('variables.templateName') ? config('variables.templateName') : 'TemplateName' }}
    - {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix' }}
  </title>
  <meta name="description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords"
    content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />
  <meta property="og:title" content="{{ config('variables.ogTitle') ? config('variables.ogTitle') : '' }}" />
  <meta property="og:type" content="{{ config('variables.ogType') ? config('variables.ogType') : '' }}" />
  <meta property="og:url" content="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
  <meta property="og:image" content="{{ config('variables.ogImage') ? config('variables.ogImage') : '' }}" />
  <meta property="og:description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta property="og:site_name"
    content="{{ config('variables.creatorName') ? config('variables.creatorName') : '' }}" />
  <meta name="robots" content="noindex, nofollow" />
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon.png') }}" />

  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  @if (
      $primaryColorCSS &&
          (config('custom.custom.primaryColor') ||
              isset($_COOKIE['admin-primaryColor']) ||
              isset($_COOKIE['front-primaryColor'])))
    <!-- Primary Color Style -->
    <style id="primary-color-style">
      {!! $primaryColorCSS !!}
    </style>
  @endif

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)
</head>

<body>
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  {{-- remove while creating package --}}
  {{-- remove while creating package end --}}

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)

  {{-- Global Delete Confirmation Modal --}}
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-body text-center p-5">
          <div class="avatar avatar-xl mb-3 mx-auto">
            <span class="avatar-initial rounded-circle bg-label-danger" style="width:64px;height:64px;font-size:1.5rem;">
              <i class="icon-base ti tabler-trash text-danger"></i>
            </span>
          </div>
          <h5 class="mb-1" id="confirmDeleteTitle">Delete Item?</h5>
          <p class="text-muted mb-4" id="confirmDeleteMessage">This action cannot be undone.</p>
          <div class="d-flex gap-2 justify-content-center">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
              <i class="icon-base ti tabler-trash me-1"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Global Success Toast --}}
  <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
    <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body" id="successToastMsg">
          <i class="icon-base ti tabler-check me-2"></i> Done successfully!
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <script>
    // Generic Bootstrap-modal confirmation. Shows the shared modal with the
    // given title/message and runs onConfirm() when the Delete button is clicked.
    window.confirmAction = function(title, message, onConfirm) {
      const modal = document.getElementById('confirmDeleteModal');
      const titleEl = document.getElementById('confirmDeleteTitle');
      const msgEl = document.getElementById('confirmDeleteMessage');
      const btn = document.getElementById('confirmDeleteBtn');

      titleEl.textContent = title || 'Are you sure?';
      msgEl.textContent = message || 'This action cannot be undone.';

      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();

      // Remove previous listener
      const newBtn = btn.cloneNode(true);
      btn.parentNode.replaceChild(newBtn, btn);

      newBtn.addEventListener('click', function() {
        bsModal.hide();
        onConfirm();
      });
    };

    // Global confirm delete handler
    window.confirmDelete = function(url, itemName, onSuccess) {
      const title = 'Delete ' + (itemName || 'Item') + '?';
      const message = 'Are you sure you want to delete this ' + (itemName || 'item') + '? This action cannot be undone.';

      confirmAction(title, message, function() {
        fetch(url, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
          } else {
            // Show success toast
            const toastMsg = document.getElementById('successToastMsg');
            if (toastMsg) toastMsg.innerHTML = '<i class="icon-base ti tabler-check me-2"></i> Deleted successfully!';
            const toast = new bootstrap.Toast(document.getElementById('successToast'));
            toast.show();
            if (typeof onSuccess === 'function') {
              onSuccess();
            } else {
              setTimeout(() => location.reload(), 800);
            }
          }
        });
      });
    };

    // For classic <form method="POST">...@method('DELETE')</form> submissions
    // that previously relied on onsubmit="return confirm(...)".
    window.confirmFormSubmit = function(form, title, message) {
      confirmAction(title, message, function() {
        form.submit();
      });
    };

    // Defers initFn() until the Bootstrap tab whose trigger matches
    // tabTriggerSelector is first shown, so tab content (e.g. a DataTable)
    // isn't fetched/rendered until the user actually opens that tab.
    // Runs initFn() at most once per tabTriggerSelector.
    window.initLazyTab = function(tabTriggerSelector, initFn) {
      const trigger = document.querySelector(tabTriggerSelector);
      if (!trigger) return;

      let initialized = false;
      trigger.addEventListener('shown.bs.tab', function() {
        if (initialized) return;
        initialized = true;
        initFn();
      });
    };
  </script>


@auth
{{-- Session Timeout Warning Modal --}}
<div class="modal fade" id="sessionTimeoutModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center p-5">
        <div class="avatar avatar-xl mb-3 mx-auto">
          <span class="avatar-initial rounded-circle bg-label-warning" style="width:64px;height:64px;font-size:1.5rem;">
            <i class="icon-base ti tabler-clock-exclamation text-warning"></i>
          </span>
        </div>
        <h5 class="mb-1">Session Expiring!</h5>
        <p class="text-muted mb-1">Your session will expire in</p>
        <h3 class="text-danger mb-4" id="sessionCountdown">2:00</h3>
        <p class="text-muted small mb-4">You will be automatically logged out due to inactivity.</p>
        <div class="d-flex gap-2 justify-content-center">
          <button type="button" class="btn btn-primary" id="stayLoggedIn">
            <i class="icon-base ti tabler-refresh me-1"></i> Stay Logged In
          </button>
          <form action="{{ route('logout') }}" method="POST" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-label-secondary">Logout Now</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const IDLE_TIMEOUT    = 28 * 60 * 1000; // 28 minutes → show warning
  const WARNING_TIMEOUT = 2  * 60 * 1000; // 2 minutes → then logout
  const PING_URL        = '{{ route('session.ping') }}';
  const LOGOUT_URL      = '{{ route('logout') }}';
  const CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')?.content;

  let idleTimer;
  let countdownTimer;
  let countdownSeconds = 120;
  let warningShown = false;

  const modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));

  // Reset idle timer on user activity
  function resetIdleTimer() {
    if (warningShown) return; // don't reset if warning is showing
    clearTimeout(idleTimer);
    idleTimer = setTimeout(showWarning, IDLE_TIMEOUT);
  }

  // Show warning modal
  function showWarning() {
    warningShown = true;
    countdownSeconds = 120;
    modal.show();
    startCountdown();
  }

  // Countdown timer
  function startCountdown() {
    clearInterval(countdownTimer);
    countdownTimer = setInterval(function() {
      countdownSeconds--;
      const mins = Math.floor(countdownSeconds / 60);
      const secs = countdownSeconds % 60;
      const el = document.getElementById('sessionCountdown');
      if (el) el.textContent = mins + ':' + String(secs).padStart(2, '0');

      if (countdownSeconds <= 0) {
        clearInterval(countdownTimer);
        // Auto logout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = LOGOUT_URL;
        const csrf = document.createElement('input');
        csrf.type  = 'hidden';
        csrf.name  = '_token';
        csrf.value = CSRF_TOKEN;
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
      }
    }, 1000);
  }

  // Stay logged in button
  document.getElementById('stayLoggedIn')?.addEventListener('click', function() {
    fetch(PING_URL, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Content-Type': 'application/json',
      }
    }).then(() => {
      clearInterval(countdownTimer);
      warningShown = false;
      modal.hide();
      resetIdleTimer();
    });
  });

  // Track user activity
  ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
    document.addEventListener(event, resetIdleTimer, { passive: true });
  });

  // Start timer
  resetIdleTimer();
})();
</script>
@endauth
</body>

</html>
