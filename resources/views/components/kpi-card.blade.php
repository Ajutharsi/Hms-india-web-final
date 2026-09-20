{{--
    Reusable, clickable KPI stat card. Same visual shape used across the app
    (see super-admin/hospitals/index.blade.php's stat row) but wrapped as a
    single component so every card on a page is guaranteed structurally
    identical, and every card is a real link/button — not just decoration.

    Usage:
    <x-kpi-card label="Total Hospitals" :value="24" sub="All registered tenants"
        icon="tabler-building-hospital" color="primary" :href="route('super-admin.hospitals.index')" />

    Pass `modal-target="#someModal"` instead of `href` to open a modal instead of navigating.
--}}
@props(['label', 'value', 'sub' => null, 'icon', 'color' => 'primary', 'href' => null, 'modalTarget' => null])

@php
$tag = $modalTarget ? 'button' : 'a';
$attrs = $modalTarget
    ? ['type' => 'button', 'data-bs-toggle' => 'modal', 'data-bs-target' => $modalTarget]
    : ['href' => $href ?? 'javascript:void(0)'];
@endphp

<{{ $tag }} {{ $attributes->merge($attrs)->merge(['class' => 'kpi-card-link']) }}>
  <div class="card kpi-card h-100">
    <div class="card-body">
      <div class="d-flex align-items-start justify-content-between">
        <div class="content-left">
          <span class="text-heading">{{ $label }}</span>
          <div class="d-flex align-items-center my-1"><h4 class="mb-0 me-2">{{ $value }}</h4></div>
          @if($sub)<small class="mb-0">{{ $sub }}</small>@endif
        </div>
        <div class="avatar"><span class="avatar-initial rounded bg-label-{{ $color }}"><i class="icon-base ti {{ $icon }} icon-26px"></i></span></div>
      </div>
    </div>
  </div>
</{{ $tag }}>
