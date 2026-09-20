{{--
    Subscription plan usage card — see SUPER_ADMIN_USAGE_LIMIT_ENFORCEMENT_REPORT.md.
    Shows "used / limit" for a plan-limited resource with a progress bar and
    an 80%/100% warning, matching this app's existing kpi-card visual
    language (see components/kpi-card.blade.php).

    Usage:
    <x-plan-usage-card label="Doctors" :used="8" :limit="10" :percent="80" status="approaching" icon="tabler-stethoscope" />
--}}
@props(['label', 'used', 'limit', 'percent', 'status', 'icon' => 'tabler-users', 'color' => 'primary'])

@php
$displayLimit = $limit === null ? 'Unlimited' : number_format($limit);
$barColor = match ($status) {
    'exceeded' => 'danger',
    'approaching' => 'warning',
    default => $color,
};
@endphp

<div {{ $attributes->merge(['class' => 'card h-100']) }}>
  <div class="card-body">
    <div class="d-flex align-items-start justify-content-between mb-2">
      <div>
        <span class="text-heading">{{ $label }}</span>
        <div class="d-flex align-items-center my-1"><h4 class="mb-0 me-2">{{ number_format($used) }} / {{ $displayLimit }}</h4></div>
      </div>
      <div class="avatar"><span class="avatar-initial rounded bg-label-{{ $barColor }}"><i class="icon-base ti {{ $icon }} icon-26px"></i></span></div>
    </div>
    @if($limit !== null)
    <div class="progress" style="height: 6px;">
      <div class="progress-bar bg-{{ $barColor }}" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    @if($status === 'exceeded')
    <small class="text-danger d-block mt-2"><i class="icon-base ti tabler-alert-triangle me-1"></i>Limit reached</small>
    @elseif($status === 'approaching')
    <small class="text-warning d-block mt-2"><i class="icon-base ti tabler-alert-circle me-1"></i>Approaching limit</small>
    @endif
    @else
    <small class="text-body-secondary d-block mt-1">Unlimited on your current plan</small>
    @endif
  </div>
</div>
