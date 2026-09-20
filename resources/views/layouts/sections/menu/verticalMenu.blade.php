@php
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu" @foreach ($configData['menuAttributes'] as $attribute=>
  $value)
  {{ $attribute }}="{{ $value }}" @endforeach>

  @if (!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-bold ms-3">{{ config('variables.templateName') }}</span>
    </a>
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
      <i class="icon-base ti tabler-x d-block d-xl-none"></i>
    </a>
  </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)

    @php
      $userRole    = auth()->check() ? auth()->user()->getRoleNames()->first() : null;
      $menuRoles   = isset($menu->roles) ? $menu->roles : null;
      $menuFeature = isset($menu->feature) ? $menu->feature : null;
      $hasRoleAccess    = !$menuRoles || ($userRole && in_array($userRole, $menuRoles));
      $hasFeatureAccess = !$menuFeature || ($hospitalFeatures[$menuFeature] ?? true);
      $hasAccess = $hasRoleAccess && $hasFeatureAccess;
    @endphp

    @if(!$hasAccess)
      @continue
    @endif

    {{-- Menu Headers --}}
    @if (isset($menu->menuHeader))
    <li class="menu-header small">
      <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
    </li>
    @else

    {{-- Active menu --}}
    @php
      $activeClass      = null;
      $currentRouteName = Route::currentRouteName();
      if ($currentRouteName === $menu->slug) {
        $activeClass = 'active';
      } elseif (isset($menu->submenu)) {
        if (gettype($menu->slug) === 'array') {
          foreach ($menu->slug as $slug) {
            if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
              $activeClass = 'active open';
            }
          }
        } else {
          if (str_contains($currentRouteName, $menu->slug) and strpos($currentRouteName, $menu->slug) === 0) {
            $activeClass = 'active open';
          }
        }
      }
    @endphp

    <li class="menu-item {{ $activeClass }}">
      <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
        class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
        @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>

        @isset($menu->icon)
        <i class="{{ $menu->icon }}"></i>
        @endisset

        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>

        {{-- Dynamic Badges --}}
        @if(isset($menu->slug) && $menu->slug === 'pharmacy.drugs.index' && $lowStockCount > 0)
          <div class="badge bg-danger rounded-pill ms-auto">{{ $lowStockCount }}</div>
        @elseif(isset($menu->slug) && $menu->slug === 'lab.orders.index' && $pendingLabCount > 0)
          <div class="badge bg-warning rounded-pill ms-auto">{{ $pendingLabCount }}</div>
        @elseif(isset($menu->slug) && $menu->slug === 'billing.invoices.index' && $openInvoiceCount > 0)
          <div class="badge bg-info rounded-pill ms-auto">{{ $openInvoiceCount }}</div>
        @else
          @isset($menu->badge)
          <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
          @endisset
        @endif

      </a>
      @isset($menu->submenu)
      @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
      @endisset
    </li>
    @endif

    @endforeach
  </ul>

</aside>