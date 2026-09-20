@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp

{{-- Brand --}}
@if (isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
  <a href="{{ url('/') }}" class="app-brand-link">
    <span class="app-brand-logo demo">@include('_partials.macros')</span>
    <span class="app-brand-text demo menu-text fw-bold">{{ config('variables.templateName') }}</span>
  </a>
  @if (isset($menuHorizontal))
  <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
    <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
  </a>
  @endif
</div>
@endif

{{-- Menu Toggle --}}
@if (!isset($navbarHideToggle))
<div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
  <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
    <i class="icon-base ti tabler-menu-2 icon-md"></i>
  </a>
</div>
@endif

<style>
/* ===== Search Bar Styles ===== */
.search-container {
  position: relative;
  display: inline-block;
}

.search-toggle-btn {
  background: transparent;
  border: none;
  color: var(--bs-body-color);
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.search-toggle-btn:hover {
  background: rgba(105, 108, 255, 0.08);
  color: var(--bs-primary);
}

.search-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 340px;
  max-width: 90vw;
  background: var(--bs-card-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 12px;
  box-shadow: 0 12px 48px rgba(0,0,0,0.18);
  padding: 0.75rem;
  display: none;
  z-index: 1060;
  opacity: 0;
  transform: translateY(-10px) scale(0.96);
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.search-dropdown.show {
  display: block;
  animation: searchSlideDown 0.22s ease forwards;
}
.search-dropdown.closing {
  display: block;
  pointer-events: none;
  animation: searchSlideUp 0.18s ease forwards;
}

@keyframes searchSlideDown {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
@keyframes searchSlideUp {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(-10px) scale(0.96);
  }
}

.search-container, .search-container .search-toggle-btn {
  position: relative;
  z-index: 1060;
}

@media (max-width: 575.98px) {
  .search-dropdown {
    position: fixed;
    top: 64px;
    left: 50%;
    right: auto;
    transform: translateX(-50%) translateY(-10px) scale(0.96);
    width: 94vw;
    max-width: 94vw;
  }
  .search-dropdown.show {
    animation: searchSlideDownMobile 0.22s ease forwards;
  }
  .search-dropdown.closing {
    animation: searchSlideUpMobile 0.18s ease forwards;
  }
  @keyframes searchSlideDownMobile {
    from { opacity: 0; transform: translateX(-50%) translateY(-10px) scale(0.96); }
    to   { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
  }
  @keyframes searchSlideUpMobile {
    from { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
    to   { opacity: 0; transform: translateX(-50%) translateY(-10px) scale(0.96); }
  }
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.search-input-wrapper .search-icon-input {
  position: absolute;
  left: 12px;
  color: var(--bs-secondary-color);
  font-size: 1rem;
}
.search-input-wrapper input {
  width: 100%;
  padding: 0.55rem 0.55rem 0.55rem 2.5rem;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-body-bg);
  color: var(--bs-body-color);
  font-size: 0.875rem;
  transition: all 0.2s ease;
}
.search-input-wrapper input:focus {
  outline: none;
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.12);
}
.search-input-wrapper input::placeholder {
  color: var(--bs-secondary-color);
  font-size: 0.8rem;
}

.search-results {
  max-height: 350px;
  overflow-y: auto;
  margin-top: 0.5rem;
}
.search-results::-webkit-scrollbar {
  width: 4px;
}
.search-results::-webkit-scrollbar-thumb {
  background: var(--bs-border-color);
  border-radius: 4px;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
  text-decoration: none;
  color: var(--bs-body-color);
}
.search-result-item:hover {
  background: var(--bs-secondary-bg, rgba(0,0,0,0.035));
  text-decoration: none;
}
.search-result-item .result-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(105, 108, 255, 0.08);
  color: var(--bs-primary);
  flex-shrink: 0;
}
.search-result-item .result-info {
  flex: 1;
  min-width: 0;
}
.search-result-item .result-info .result-title {
  font-size: 0.82rem;
  font-weight: 500;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.search-result-item .result-info .result-subtitle {
  font-size: 0.7rem;
  color: var(--bs-secondary-color);
  margin: 0;
}
.search-result-item .result-badge {
  font-size: 0.6rem;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
  flex-shrink: 0;
}

.search-no-results {
  text-align: center;
  padding: 1.5rem 0.5rem;
  color: var(--bs-secondary-color);
}
.search-no-results i {
  font-size: 2rem;
  display: block;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}
.search-no-results p {
  font-size: 0.8rem;
  margin: 0;
}

.search-shortcut-hint {
  font-size: 0.6rem;
  color: var(--bs-secondary-color);
  padding: 0.3rem 0.75rem;
  border-top: 1px solid var(--bs-border-color);
  margin-top: 0.5rem;
  display: flex;
  justify-content: space-between;
}
.search-shortcut-hint kbd {
  background: var(--bs-secondary-bg);
  padding: 0.1rem 0.4rem;
  border-radius: 3px;
  font-size: 0.6rem;
  font-weight: 600;
}
</style>

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

  {{-- Theme Switcher --}}
  @if ($configData['hasCustomizer'] == true)
  <div class="navbar-nav align-items-center">
    <li class="nav-item dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);"
        data-bs-toggle="dropdown">
        <i class="icon-base ti tabler-sun icon-md theme-icon-active"></i>
        <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
        <li>
          <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light">
            <span><i class="icon-base ti tabler-sun icon-22px me-3" data-icon="sun"></i>Light</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark">
            <span><i class="icon-base ti tabler-moon-stars icon-22px me-3" data-icon="moon-stars"></i>Dark</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system">
            <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3" data-icon="device-desktop-analytics"></i>System</span>
          </button>
        </li>
      </ul>
    </li>
  </div>
  @endif

  <ul class="navbar-nav flex-row align-items-center ms-auto">

    {{-- Round Alert (nurse/staff only) --}}
    @if(Auth::check() && Auth::user()->hasRole(['nurse', 'staff']))
    <li class="nav-item me-2 me-xl-0" style="position:relative;">
      <a class="nav-link" href="{{ route('staff-rounds.my-rounds') }}" title="My Rounds">
        <i class="icon-base ti tabler-clock-check icon-md"></i>
        <span id="roundAlertBadge" class="d-none" style="position:absolute;top:2px;right:2px;background:var(--bs-danger);color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.6rem;display:flex;align-items:center;justify-content:center;font-weight:700;"></span>
      </a>
    </li>
    @endif

    {{-- Shift Handover Alert (nurse only) — pending handovers awaiting this nurse's acceptance --}}
    @if(Auth::check() && Auth::user()->hasRole('nurse'))
    <li class="nav-item me-2 me-xl-0" style="position:relative;">
      <a class="nav-link" href="{{ route('shift-handover.index') }}" title="Pending Handovers">
        <i class="icon-base ti tabler-transfer-in icon-md"></i>
        <span id="handoverAlertBadge" class="d-none" style="position:absolute;top:2px;right:2px;background:var(--bs-danger);color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.6rem;display:flex;align-items:center;justify-content:center;font-weight:700;"></span>
      </a>
    </li>
    @endif

    {{-- Notification Bell (super_admin only) --}}
    @if(Auth::check() && Auth::user()->hasRole('super_admin'))
    <li class="nav-item dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" style="position:relative;" title="Notifications">
        <i class="icon-base ti tabler-bell icon-md"></i>
        <span id="notifBellBadge" class="d-none" style="position:absolute;top:2px;right:2px;background:var(--bs-danger);color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.6rem;display:flex;align-items:center;justify-content:center;font-weight:700;"></span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end py-0" style="width:340px;max-height:420px;overflow-y:auto;">
        <li class="dropdown-menu-header border-bottom">
          <div class="dropdown-header d-flex align-items-center py-3">
            <h6 class="mb-0 me-auto">Notifications</h6>
            <a href="{{ route('super-admin.notifications.index') }}" class="small">View All</a>
          </div>
        </li>
        <li id="notifBellList">
          <div class="text-center text-body-secondary small py-4">Loading…</div>
        </li>
      </ul>
    </li>
    @endif

    {{-- Search Button --}}
    <li class="nav-item me-2 me-xl-0">
      <div class="search-container">
        <button class="search-toggle-btn" id="searchToggleBtn" title="Search (Ctrl+K)" aria-label="Search">
          <i class="icon-base ti tabler-search icon-md"></i>
        </button>
        <div class="search-dropdown" id="searchDropdown">
          <div class="search-input-wrapper">
            <i class="icon-base ti tabler-search search-icon-input"></i>
            <input type="text" id="searchInput" placeholder="Search patients, doctors, appointments..." autocomplete="off">
          </div>
          <div class="search-results" id="searchResults">
            <div class="search-no-results">
              <i class="icon-base ti tabler-search"></i>
              <p>Type to start searching...</p>
            </div>
          </div>
          <div class="search-shortcut-hint">
            <span>Navigate with <kbd>↑</kbd> <kbd>↓</kbd></span>
            <span>Press <kbd>Esc</kbd> to close</span>
          </div>
        </div>
      </div>
    </li>

    {{-- User Dropdown --}}
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          @if(Auth::check() && Auth::user()->profile_photo_url)
          <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle" />
          @else
          <span class="avatar-initial rounded-circle bg-label-primary">
            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}
          </span>
          @endif
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">

        {{-- User Info --}}
        <li>
          <a class="dropdown-item mt-0"
            href="{{ Route::has('profile.show') ? route('profile.show') : 'javascript:void(0);' }}">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0 me-2">
                <div class="avatar avatar-online">
                  @if(Auth::check() && Auth::user()->profile_photo_url)
                  <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle" />
                  @else
                  <span class="avatar-initial rounded-circle bg-label-primary">
                    {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}
                  </span>
                  @endif
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">
                  {{ Auth::check() ? Auth::user()->name : 'Guest' }}
                </h6>
                <small class="text-body-secondary text-capitalize">
                  {{ Auth::check() ? str_replace('_', ' ', Auth::user()->roles->first()?->name ?? 'User') : '' }}
                </small>
              </div>
            </div>
          </a>
        </li>

        <li><div class="dropdown-divider my-1 mx-n2"></div></li>

        {{-- My Profile --}}
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('profile.show') ? route('profile.show') : 'javascript:void(0);' }}">
            <i class="icon-base ti tabler-user me-3 icon-md"></i>
            <span class="align-middle">My Profile</span>
          </a>
        </li>

        {{-- Settings --}}
        @if(Auth::check() && Auth::user()->hasRole(['super_admin', 'admin']))
        <li>
          <a class="dropdown-item" href="{{ route('settings.index') }}">
            <i class="icon-base ti tabler-settings me-3 icon-md"></i>
            <span class="align-middle">Settings</span>
          </a>
        </li>
        @endif

        <li><div class="dropdown-divider my-1 mx-n2"></div></li>

        {{-- Logout --}}
        @if(Auth::check())
        <li>
          <a class="dropdown-item" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="icon-base ti tabler-logout icon-md me-3"></i>
            <span>Logout</span>
          </a>
        </li>
        <form method="POST" id="logout-form" action="{{ route('logout') }}">
          @csrf
        </form>
        @else
        <li>
          <div class="d-grid px-2 pt-2 pb-1">
            <a class="btn btn-sm btn-danger d-flex"
              href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
              <small class="align-middle">Login</small>
              <i class="icon-base ti tabler-login ms-2 icon-14px"></i>
            </a>
          </div>
        </li>
        @endif

      </ul>
    </li>

  </ul>
</div>

{{-- Search JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchBtn = document.getElementById('searchToggleBtn');
  const searchDropdown = document.getElementById('searchDropdown');
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('searchResults');
  let isOpen = false;
  let searchTimeout = null;
  let selectedIndex = -1;
  let searchResultsData = [];

  // Toggle search dropdown
  function toggleSearch() {
    isOpen = !isOpen;

    if (isOpen) {
      searchDropdown.classList.remove('closing');
      searchDropdown.classList.add('show');
      searchInput.focus();
      searchInput.value = '';
      searchResults.innerHTML = `
        <div class="search-no-results">
          <i class="icon-base ti tabler-search"></i>
          <p>Type to start searching...</p>
        </div>
      `;
      selectedIndex = -1;
    } else {
      searchInput.blur();
      searchDropdown.classList.remove('show');
      searchDropdown.classList.add('closing');
      const clearClosing = function() {
        searchDropdown.classList.remove('closing');
        searchDropdown.removeEventListener('animationend', clearClosing);
        clearTimeout(closingFallbackTimer);
      };
      searchDropdown.addEventListener('animationend', clearClosing);
      // Fallback in case animationend never fires (e.g. prefers-reduced-motion
      // disabling CSS animations entirely) — without this, `.closing` could
      // stick forever, leaving an invisible dropdown floating over the page.
      const closingFallbackTimer = setTimeout(clearClosing, 250);
    }
  }

  searchBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleSearch();
  });

  // Close search when the backdrop (or anything else outside the panel) is clicked
  document.addEventListener('click', function(e) {
    if (isOpen && !searchDropdown.contains(e.target) && e.target !== searchBtn) {
      toggleSearch();
    }
  });

  // Close on Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isOpen) {
      toggleSearch();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      toggleSearch();
    }
  });

  // Keyboard navigation
  searchInput.addEventListener('keydown', function(e) {
    const items = searchResults.querySelectorAll('.search-result-item');
    
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (items.length > 0) {
        selectedIndex = (selectedIndex + 1) % items.length;
        highlightItem(items, selectedIndex);
        items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (items.length > 0) {
        selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
        highlightItem(items, selectedIndex);
        items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
      }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedIndex >= 0 && items[selectedIndex]) {
        items[selectedIndex].click();
      }
    }
  });

  function highlightItem(items, index) {
    items.forEach((item, i) => {
      item.style.background = i === index ? 'var(--bs-secondary-bg, rgba(0,0,0,0.05))' : '';
    });
  }

  // Search functionality
  searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
      searchResults.innerHTML = `
        <div class="search-no-results">
          <i class="icon-base ti tabler-search"></i>
          <p>Type at least 2 characters to search...</p>
        </div>
      `;
      return;
    }

    searchTimeout = setTimeout(function() {
      performSearch(query);
    }, 300);
  });

  function performSearch(query) {
    // Show loading state
    searchResults.innerHTML = `
      <div class="text-center py-3">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Searching...</span>
        </div>
        <p class="text-muted small mt-2 mb-0">Searching...</p>
      </div>
    `;

    fetch(`/api/search?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        searchResultsData = data;
        renderSearchResults(data);
      })
      .catch(() => {
        searchResults.innerHTML = `
          <div class="search-no-results">
            <i class="icon-base ti tabler-alert-circle"></i>
            <p>Something went wrong. Please try again.</p>
          </div>
        `;
      });
  }

  function renderSearchResults(data) {
    selectedIndex = -1;
    
    if (!data || data.length === 0) {
      searchResults.innerHTML = `
        <div class="search-no-results">
          <i class="icon-base ti tabler-search"></i>
          <p>No results found</p>
        </div>
      `;
      return;
    }

    let html = '';
    data.forEach(item => {
      const iconMap = {
        'patient': 'tabler-users',
        'doctor': 'tabler-stethoscope',
        'appointment': 'tabler-calendar',
        'invoice': 'tabler-file-invoice',
        'drug': 'tabler-pill',
        'lab': 'tabler-flask',
        'ward': 'tabler-building-hospital',
        'default': 'tabler-search'
      };
      const icon = iconMap[item.type] || iconMap.default;
      const colorMap = {
        'patient': 'primary',
        'doctor': 'info',
        'appointment': 'warning',
        'invoice': 'success',
        'drug': 'danger',
        'lab': 'warning',
        'ward': 'info',
        'default': 'secondary'
      };
      const color = colorMap[item.type] || colorMap.default;
      const iconHtml = item.photo_url
        ? `<img src="${item.photo_url}" alt="" style="width:100%;height:100%;border-radius:8px;object-fit:cover;">`
        : `<i class="icon-base ti ${icon}"></i>`;

      html += `
        <a href="${item.url}" class="search-result-item">
          <div class="result-icon" style="background: rgba(105,108,255,0.08); color: var(--bs-${color});">
            ${iconHtml}
          </div>
          <div class="result-info">
            <p class="result-title">${item.title}</p>
            <p class="result-subtitle">${item.subtitle || ''}</p>
          </div>
          <span class="result-badge" style="background: rgba(105,108,255,0.08); color: var(--bs-${color});">
            ${item.type}
          </span>
        </a>
      `;
    });

    searchResults.innerHTML = html;
  }
});

{{-- Round Alert JS (nurse/staff only) --}}
@if(Auth::check() && Auth::user()->hasRole(['nurse', 'staff']))
function checkRoundAlerts() {
  fetch('{{ route('staff-rounds.alert-count') }}')
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('roundAlertBadge');
      if (data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = 'flex';
        badge.classList.remove('d-none');
      } else {
        badge.style.display = 'none';
      }
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', function() {
  checkRoundAlerts();
  setInterval(checkRoundAlerts, 2 * 60 * 1000);
});
@endif

{{-- Shift Handover Alert JS (nurse only) --}}
@if(Auth::check() && Auth::user()->hasRole('nurse'))
function checkHandoverAlerts() {
  fetch('{{ route('shift-handover.alert-count') }}')
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('handoverAlertBadge');
      if (data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = 'flex';
        badge.classList.remove('d-none');
      } else {
        badge.style.display = 'none';
      }
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', function() {
  checkHandoverAlerts();
  setInterval(checkHandoverAlerts, 2 * 60 * 1000);
});
@endif

{{-- Notification Bell JS (super_admin only) --}}
@if(Auth::check() && Auth::user()->hasRole('super_admin'))
function notifEscapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderNotifBell(data) {
  const badge = document.getElementById('notifBellBadge');
  if (data.unread_count > 0) {
    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
    badge.style.display = 'flex';
    badge.classList.remove('d-none');
  } else {
    badge.style.display = 'none';
  }

  const list = document.getElementById('notifBellList');
  if (!data.recent.length) {
    list.innerHTML = '<div class="text-center text-body-secondary small py-4">No notifications yet.</div>';
    return;
  }

  const levelColors = { info: 'info', warning: 'warning', critical: 'danger' };
  list.innerHTML = data.recent.map(n => `
    <a href="${n.url ? notifEscapeHtml(n.url) : '#'}" class="dropdown-item d-flex align-items-start gap-2 py-2 border-bottom">
      <span class="badge bg-label-${levelColors[n.level] || 'secondary'} mt-1"><i class="icon-base ti tabler-bell"></i></span>
      <span class="flex-grow-1">
        <span class="d-block small ${n.read ? '' : 'fw-bold'}">${notifEscapeHtml(n.title)}</span>
        <span class="d-block text-body-secondary" style="font-size:0.7rem;">${notifEscapeHtml(n.message)}</span>
        <span class="d-block text-body-secondary" style="font-size:0.65rem;">${notifEscapeHtml(n.created_at)}</span>
      </span>
    </a>
  `).join('');
}

function checkNotifications() {
  fetch('{{ route('super-admin.notifications.bell') }}')
    .then(res => res.json())
    .then(renderNotifBell)
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', function() {
  checkNotifications();
  setInterval(checkNotifications, 2 * 60 * 1000);
});
@endif
</script>