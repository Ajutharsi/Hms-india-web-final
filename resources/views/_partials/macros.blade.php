@php
  $width = $width ?? '32';
  $height = $height ?? '32';
@endphp

<span class="text-primary">
  <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 32 32" fill="none"
    xmlns="http://www.w3.org/2000/svg">
    <!-- Hospital Cross Symbol -->
    <rect width="32" height="32" rx="8" fill="currentColor" opacity="0.15"/>
    <rect x="13" y="6" width="6" height="20" rx="2" fill="currentColor"/>
    <rect x="6" y="13" width="20" height="6" rx="2" fill="currentColor"/>
  </svg>
</span>