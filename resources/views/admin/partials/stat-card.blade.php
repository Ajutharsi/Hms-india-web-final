{{--
  Single premium KPI/stat card — icon top, label + value, optional trend,
  "View Details" button pinned at the bottom. Shared by every stat grid on
  the dashboard (Overall Statistics, Operational Alerts, per-role stat rows)
  so they all render identically. Expects $stat (see BuildsDashboardStats::stat())
  and inherits $currentRoleStr / $modalBackedLabels from the including view.
--}}
@php
$hasModal   = in_array($currentRoleStr, ['super_admin', 'admin']) && in_array($stat['label'], $modalBackedLabels ?? []);
$clickable  = $hasModal || !empty($stat['url']);
$trendClass = $stat['trendIcon'] === 'tabler-trending-up' ? 'hms-stat-trend-up' : ($stat['trendIcon'] === 'tabler-trending-down' ? 'hms-stat-trend-down' : 'hms-stat-trend-neutral');
$targetUrl  = $hasModal ? '#' : ($stat['url'] ?? '#');
@endphp
@if($clickable)
<a href="{{ $targetUrl }}"
  @if($hasModal) data-bs-toggle="modal" data-bs-target="#modal_{{ \Illuminate\Support\Str::slug($stat['modalKey'] ?? $stat['label'], '_') }}" @endif
  class="hms-stat-card hms-stat-{{ $stat['color'] }} d-block text-decoration-none text-reset"
  aria-label="View {{ $stat['label'] }} details">
@else
<div class="hms-stat-card hms-stat-card-static hms-stat-{{ $stat['color'] }}">
@endif
  <div class="hms-stat-icon hms-stat-icon-{{ $stat['color'] }}">
    <i class="icon-base ti {{ $stat['icon'] }}"></i>
  </div>
  <div class="hms-stat-body">
    <p class="hms-stat-label">{{ $stat['label'] }}</p>
    <h3 class="hms-stat-value">{{ $stat['count'] }}</h3>
    <span class="hms-stat-trend {{ $trendClass }}">
      <i class="icon-base ti {{ $stat['trendIcon'] }}" style="font-size:0.7rem;"></i>
      {{ $stat['trend'] }}
    </span>
  </div>
  @if($clickable)
  <div class="hms-stat-footer">
    <span class="hms-stat-btn">
      View Details
      <i class="icon-base ti tabler-arrow-right" style="font-size:0.8rem;"></i>
    </span>
  </div>
  @endif
@if($clickable)
</a>
@else
</div>
@endif
