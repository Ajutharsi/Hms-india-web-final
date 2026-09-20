@php
use Illuminate\Support\Str;
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Dashboard - HMS')

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('page-style')
<style>
.hms-header {
  background: var(--bs-card-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 12px;
  padding: 1.5rem 2rem;
  margin-bottom: 1.5rem;
}

/* ===== System Alerts — top-right popup toasts ===== */
.hms-toast-container {
  position: fixed;
  top: 90px;
  right: 20px;
  z-index: 1080;
  width: 340px;
  max-width: calc(100vw - 2rem);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.hms-toast {
  position: relative;
  margin-bottom: 0 !important;
  padding-right: 2.25rem !important;
  box-shadow: 0 10px 28px rgba(0,0,0,0.14);
  animation: hmsToastIn 0.25s ease;
  background: linear-gradient(150deg, #FFFBEB 0%, #FFF3C4 100%) !important;
  border: 1px solid #FCE588 !important;
  color: #7A5B00 !important;
}
.hms-toast .icon-base { color: #B8860B; }
.hms-toast-close {
  position: absolute;
  top: 0.6rem;
  right: 0.6rem;
  background: transparent;
  border: none;
  color: inherit;
  opacity: 0.55;
  font-size: 1.1rem;
  line-height: 1;
  padding: 0.15rem;
  cursor: pointer;
}
.hms-toast-close:hover { opacity: 1; }
@keyframes hmsToastIn {
  from { opacity: 0; transform: translateX(24px); }
  to { opacity: 1; transform: translateX(0); }
}


:root {
  --hms-blue: #2563EB;
  --hms-blue-dark: #1D4ED8;
  --hms-blue-tint: rgba(37,99,235,0.08);
  --hms-blue-tint-strong: rgba(37,99,235,0.14);
}


.hms-stat-card {
  background: var(--bs-card-bg);
  border: 1px solid rgba(0,0,0,0.04);
  border-radius: 20px;
  padding: 1.6rem 1.35rem 1.35rem;
  height: 100%;
  cursor: pointer;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.03);
  min-width: 0;
  width: 100%;
  box-sizing: border-box;
}
.hms-stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 22px 45px rgba(0,0,0,0.10), 0 8px 20px rgba(0,0,0,0.05);
}
.hms-stat-card-static {
  cursor: default;
}
.hms-stat-card-static:hover {
  transform: none;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.03);
}


.hms-stat-primary, .hms-stat-success, .hms-stat-warning,
.hms-stat-danger, .hms-stat-info, .hms-stat-secondary {
  background: var(--bs-card-bg);
}

.hms-stat-icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  flex-shrink: 0;
  background: var(--hms-blue-tint);
  box-shadow: none;
  margin-bottom: 0.9rem;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hms-stat-card:hover .hms-stat-icon { transform: scale(1.08); }
.hms-stat-icon-primary, .hms-stat-icon-success, .hms-stat-icon-warning,
.hms-stat-icon-danger, .hms-stat-icon-info, .hms-stat-icon-secondary {
  color: var(--hms-blue);
}

.hms-stat-body { width: 100%; min-width: 0; flex: 1 1 auto; }
.hms-stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--bs-secondary-color);
  margin-bottom: 0.3rem;
}
.hms-stat-value {
  font-size: 2.1rem;
  font-weight: 700;
  line-height: 1.2;
  color: var(--bs-heading-color);
  margin-bottom: 0.3rem;
  overflow-wrap: break-word;
  word-break: break-word;
}
.hms-stat-trend {
  font-size: 0.7rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
}
.hms-stat-trend-up { color: var(--hms-blue); }
.hms-stat-trend-down { color: var(--bs-secondary-color); }
.hms-stat-trend-neutral { color: var(--bs-secondary-color); }

.hms-stat-footer {
  width: 100%;
  display: flex;
  margin-top: 1.1rem;
}
.hms-stat-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  width: 100%;
  color: inherit;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.5rem 0.75rem;
  border-radius: 10px;
  text-decoration: none !important;
  transition: all 0.2s ease;
  border: none;
  pointer-events: none;
}
.hms-stat-btn { background: var(--hms-blue-tint); color: var(--hms-blue); }
.hms-stat-card:hover .hms-stat-btn { background: var(--hms-blue); color: #fff; }

.hms-list-link {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.hms-list-link:hover {
  background-color: var(--bs-secondary-bg, rgba(0,0,0,0.035));
}
.hms-card {
  background: var(--bs-card-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 20px;
  overflow: hidden;
  height: 100%;
  box-shadow: 0 4px 16px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.02);
  transition: box-shadow 0.25s ease, transform 0.25s ease;
}
.hms-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 34px rgba(0,0,0,0.07), 0 4px 10px rgba(0,0,0,0.03);
}
.hms-card-header {
  padding: 1.1rem 1.35rem;
  border-bottom: 1px solid var(--bs-border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.hms-card-header h6 { margin: 0; font-size: 0.875rem; font-weight: 600; }
/* Quick Action grid — always fills exactly one row on desktop, however many
   tiles a role has, by dividing 100% by --action-count (set inline per
   tile — see dashboard.blade.php). Wraps to fewer columns on smaller
   screens where an even split would make tiles illegibly narrow. Icon
   stays on the LEFT and text on the RIGHT at every breakpoint — only the
   tile's own size changes, never its internal order. */
.hms-action-col {
  flex: 0 0 50%;
  max-width: 50%;
}
@media (min-width: 576px) {
  .hms-action-col { flex: 0 0 33.333%; max-width: 33.333%; }
}
@media (min-width: 768px) {
  .hms-action-col { flex: 0 0 25%; max-width: 25%; }
}
@media (min-width: 992px) {
  .hms-action-col { flex: 0 0 16.666%; max-width: 16.666%; }
}
@media (min-width: 1200px) {
  .hms-action-col {
    flex: 0 0 calc(100% / var(--action-count, 8));
    max-width: calc(100% / var(--action-count, 8));
  }
}
.hms-action-tile {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-start;
  gap: 0.5rem;
  text-decoration: none;
  background: var(--bs-card-bg);
  border: 1px solid rgba(0,0,0,0.03);
  border-radius: 14px;
  padding: 0.55rem 0.65rem;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  height: 100%;
  text-align: left;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  overflow: hidden;
}
.hms-action-tile:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 30px rgba(0,0,0,0.09);
  text-decoration: none;
}
.action-icon {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}
/* Quick Actions — one blue accent, not a per-tile colour rotation
   (the underlying bg-label-primary/success/warning/etc classes stay in the
   markup so nothing else needs touching; this just neutralizes them). */
.action-icon.bg-label-primary, .action-icon.bg-label-success, .action-icon.bg-label-warning,
.action-icon.bg-label-danger, .action-icon.bg-label-info, .action-icon.bg-label-secondary {
  background: var(--hms-blue-tint) !important;
}
.action-icon .text-primary, .action-icon .text-success, .action-icon .text-warning,
.action-icon .text-danger, .action-icon .text-info, .action-icon .text-secondary {
  color: var(--hms-blue) !important;
}
.action-text { min-width: 0; }
.action-label { margin: 0; font-size: 0.7rem; font-weight: 600; color: var(--bs-body-color); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.action-desc { margin: 0; font-size: 0.62rem; font-weight: 400; color: var(--bs-secondary-color); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hms-section-header {
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 1px; color: var(--bs-secondary-color); margin-bottom: 0.75rem;
}
.stat-detail-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.6rem 0; border-bottom: 1px dashed var(--bs-border-color);
}
.stat-detail-row:last-child { border-bottom: none; }

/* ===== Responsive polish ===== */
@media (max-width: 575.98px) {
  .hms-stat-card { padding: 1.25rem 1.1rem; border-radius: 18px; }
  .hms-stat-value { font-size: 1.55rem; }
  .hms-stat-icon { width: 44px; height: 44px; font-size: 1.3rem; border-radius: 13px; }
  .hms-card-header { padding: 0.9rem 1rem; }
  .hms-action-tile { padding: 0.55rem 0.6rem; gap: 0.45rem; }
  .action-icon { width: 30px; height: 30px; font-size: 0.85rem; }
  .action-label { font-size: 0.66rem; }
  .action-desc { font-size: 0.58rem; }
}

/* ===== Custom Modal Styles ===== */
.modal-content {
  border: none;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

/* Remove blur effect when modal is open */
.modal-open .hms-stat-card,
.modal-open .hms-card,
.modal-open .hms-action-tile,
.modal-open .hms-header,
.modal-open .row,
.modal-open .col-6,
.modal-open .col-md-4,
.modal-open .col-md-6,
.modal-open .col-xl-3 {
  filter: none !important;
  backdrop-filter: none !important;
}


.hms-modal-close {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: var(--bs-secondary-bg, rgba(0,0,0,0.05));
  color: var(--bs-secondary-color);
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
  line-height: 1;
}
.hms-modal-close:hover {
  background: var(--hms-blue-tint);
  color: var(--hms-blue);
  transform: rotate(90deg);
}
.hms-modal-close i {
  font-size: 1.1rem;
}

/* Modal header with custom close */
.hms-modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--bs-border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.hms-modal-header h5 {
  margin: 0;
  font-weight: 600;
}


.modal-footer {
  padding: 1rem 1.5rem !important;
  gap: 0.6rem;
  display: flex;
  align-items: center;
  justify-content: space-between !important;
}
.modal-footer > * { margin: 0 !important; }
.modal-footer .btn {
  padding: 0.55rem 1rem;
  font-weight: 600;
  border-radius: 8px;
}

.modal-body .text-primary, .modal-body .text-success, .modal-body .text-warning,
.modal-body .text-danger, .modal-body .text-info, .modal-body .text-secondary {
  color: var(--hms-blue) !important;
}
.modal-body .badge.bg-label-primary, .modal-body .badge.bg-label-success, .modal-body .badge.bg-label-warning,
.modal-body .badge.bg-label-danger, .modal-body .badge.bg-label-info, .modal-body .badge.bg-label-secondary {
  background: var(--hms-blue-tint) !important;
  color: var(--hms-blue) !important;
}
.hms-modal-header .avatar-initial.bg-label-primary, .hms-modal-header .avatar-initial.bg-label-success,
.hms-modal-header .avatar-initial.bg-label-warning, .hms-modal-header .avatar-initial.bg-label-danger,
.hms-modal-header .avatar-initial.bg-label-info, .hms-modal-header .avatar-initial.bg-label-secondary {
  background: var(--hms-blue-tint) !important;
}
.hms-modal-header .text-primary, .hms-modal-header .text-success, .hms-modal-header .text-warning,
.hms-modal-header .text-danger, .hms-modal-header .text-info, .hms-modal-header .text-secondary {
  color: var(--hms-blue) !important;
}
/* Every popup footer button — one solid blue "primary action", grey "Close" */
.modal-footer .btn-primary, .modal-footer .btn-success, .modal-footer .btn-warning,
.modal-footer .btn-danger, .modal-footer .btn-info, .modal-footer .btn-secondary {
  background: var(--hms-blue) !important;
  border-color: var(--hms-blue) !important;
  color: #fff !important;
}
.modal-body .p-3.border.rounded {
  border-color: var(--bs-border-color) !important;
  background: var(--hms-blue-tint);
}


.hms-kpi-card {
  background: var(--bs-card-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 20px;
  padding: 1.5rem;
  height: 100%;
  box-shadow: 0 4px 16px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.03);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
}
.hms-kpi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 38px rgba(0,0,0,0.08), 0 6px 16px rgba(0,0,0,0.04);
}
.hms-kpi-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 1.1rem;
}
.hms-kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: var(--hms-blue-tint);
  color: var(--hms-blue);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}
.hms-kpi-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--bs-heading-color);
  margin: 0;
}
.hms-kpi-pair {
  display: flex;
  align-items: stretch;
  flex: 1 1 auto;
  margin-bottom: 1.1rem;
}
.hms-kpi-metric {
  flex: 1 1 50%;
  min-width: 0;
  text-align: center;
  padding: 0 0.5rem;
}
.hms-kpi-metric + .hms-kpi-metric {
  border-left: 1px solid var(--bs-border-color);
}
.hms-kpi-metric-label {
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--bs-secondary-color);
  margin-bottom: 0.35rem;
  display: block;
  overflow-wrap: break-word;
}
.hms-kpi-metric-value {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--bs-heading-color);
  line-height: 1.2;
  overflow-wrap: break-word;
}

@media (min-width: 576px) and (max-width: 1199.98px) {
  .hms-kpi-metric-value { font-size: 1.3rem; }
}
.hms-kpi-btn {
  display: block;
  width: 100%;
  text-align: center;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  background: var(--hms-blue-tint);
  color: var(--hms-blue) !important;
  font-size: 0.78rem;
  font-weight: 600;
  text-decoration: none !important;
  border: none;
  transition: all 0.2s ease;
  margin-top: auto;
}
.hms-kpi-card:hover .hms-kpi-btn { background: var(--hms-blue); color: #fff !important; }

@media (max-width: 575.98px) {
  .hms-kpi-card { padding: 1.15rem; border-radius: 16px; }
  .hms-kpi-metric-value { font-size: 1.3rem; }
  .hms-kpi-icon { width: 34px; height: 34px; font-size: 1rem; }
}
</style>
@endsection

@section('content')

@php $currentRoleStr = $currentRole ?? auth()->user()->roles->first()?->name; @endphp

@if(isset($currentSubscription) && $currentSubscription)
  @if($currentSubscription->isExpired() || $currentSubscription->status === 'cancelled')
  <div class="alert alert-danger mb-6">
    <i class="icon-base ti tabler-circle-x me-2"></i>
    Your subscription has expired. Contact your administrator to renew.
  </div>
  @elseif($subscriptionDaysLeft > 0 && $subscriptionDaysLeft < 7)
  <div class="alert alert-warning mb-6">
    <i class="icon-base ti tabler-alert-triangle me-2"></i>
    Your subscription expires in {{ $subscriptionDaysLeft }} day{{ $subscriptionDaysLeft == 1 ? '' : 's' }}. Contact admin to renew.
  </div>
  @endif
@endif

{{-- System Alerts (super_admin + admin) — top-right popup --}}
@if(in_array($currentRoleStr, ['super_admin','admin']) && isset($systemAlerts) && count($systemAlerts) > 0)
<div class="hms-toast-container" id="systemAlertsSection">
  @foreach($systemAlerts as $i => $alert)
  <div class="hms-toast alert alert-{{ $alert['type'] }} mb-0" id="sysAlert{{ $i }}">
    <button type="button" class="hms-toast-close" aria-label="Close alert"
      onclick="document.getElementById('sysAlert{{ $i }}')?.remove()">
      <i class="icon-base ti tabler-x"></i>
    </button>
    @if(!empty($alert['url']))
    <a href="{{ $alert['url'] }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset"
      aria-label="{{ $alert['msg'] }} — view details">
      <i class="icon-base ti {{ $alert['icon'] }}"></i>
      <span>{{ $alert['msg'] }}</span>
    </a>
    @else
    <div class="d-flex align-items-center gap-2">
      <i class="icon-base ti {{ $alert['icon'] }}"></i>
      <span>{{ $alert['msg'] }}</span>
    </div>
    @endif
  </div>
  @endforeach
</div>
@endif

{{-- Quick Actions — icon always LEFT, title+description always RIGHT.
     Colour capped at the 3-hue KPI accent system (primary/success/warning),
     rotated per tile so consecutive tiles read as distinct. --}}
@php
$allActions = [
  'super_admin' => [
    ['label'=>'Add Patient',  'desc'=>'New patient',    'url'=>'/patients',         'icon'=>'tabler-user-plus',     'color'=>'primary'],
    ['label'=>'Appointments', 'desc'=>'Book a visit',   'url'=>'/appointments',     'icon'=>'tabler-calendar-plus', 'color'=>'success'],
    ['label'=>'Doctors',      'desc'=>'Manage staff',   'url'=>'/doctors',          'icon'=>'tabler-stethoscope',   'color'=>'warning'],
    ['label'=>'Lab Reports',  'desc'=>'View orders',    'url'=>'/lab/orders',       'icon'=>'tabler-flask',         'color'=>'primary'],
    ['label'=>'Billing',      'desc'=>'Invoices',       'url'=>'/billing/invoices', 'icon'=>'tabler-file-invoice',  'color'=>'success'],
    ['label'=>'Emergency',    'desc'=>'ER desk',        'url'=>'/er',               'icon'=>'tabler-ambulance',     'color'=>'warning'],
    ['label'=>'Reports',      'desc'=>'Analytics',      'url'=>'/reports',          'icon'=>'tabler-chart-bar',     'color'=>'primary'],
    ['label'=>'New Admission','desc'=>'Admit patient',  'url'=>'/ward',             'icon'=>'tabler-bed',           'color'=>'success'],
    ['label'=>'Pharmacy',     'desc'=>'Drug stock',     'url'=>'/pharmacy/drugs',   'icon'=>'tabler-pill',          'color'=>'warning'],
    ['label'=>'Settings',     'desc'=>'Configure',      'url'=>'/settings',         'icon'=>'tabler-settings',      'color'=>'primary'],
    ['label'=>'My Profile',   'desc'=>'Account',        'url'=>'/user/profile',     'icon'=>'tabler-user-circle',   'color'=>'success'],
  ],
  'admin' => [
    ['label'=>'Add Patient',  'desc'=>'New patient',    'url'=>'/patients',         'icon'=>'tabler-user-plus',     'color'=>'primary'],
    ['label'=>'Appointments', 'desc'=>'Book a visit',   'url'=>'/appointments',     'icon'=>'tabler-calendar-plus', 'color'=>'success'],
    ['label'=>'Doctors',      'desc'=>'Manage staff',   'url'=>'/doctors',          'icon'=>'tabler-stethoscope',   'color'=>'warning'],
    ['label'=>'Lab Reports',  'desc'=>'View orders',    'url'=>'/lab/orders',       'icon'=>'tabler-flask',         'color'=>'primary'],
    ['label'=>'Billing',      'desc'=>'Invoices',       'url'=>'/billing/invoices', 'icon'=>'tabler-file-invoice',  'color'=>'success'],
    ['label'=>'Emergency',    'desc'=>'ER desk',        'url'=>'/er',               'icon'=>'tabler-ambulance',     'color'=>'warning'],
    ['label'=>'Reports',      'desc'=>'Analytics',      'url'=>'/reports',          'icon'=>'tabler-chart-bar',     'color'=>'primary'],
    ['label'=>'New Admission','desc'=>'Admit patient',  'url'=>'/ward',             'icon'=>'tabler-bed',           'color'=>'success'],
    ['label'=>'Pharmacy',     'desc'=>'Drug stock',     'url'=>'/pharmacy/drugs',   'icon'=>'tabler-pill',          'color'=>'warning'],
    ['label'=>'Settings',     'desc'=>'Configure',      'url'=>'/settings',         'icon'=>'tabler-settings',      'color'=>'primary'],
    ['label'=>'My Profile',   'desc'=>'Account',        'url'=>'/user/profile',     'icon'=>'tabler-user-circle',   'color'=>'success'],
  ],
  'doctor' => [
    ['label'=>'Appointments','desc'=>'Your schedule',  'url'=>'/appointments',     'icon'=>'tabler-calendar-plus', 'color'=>'primary'],
    ['label'=>'Patients',    'desc'=>'Search records', 'url'=>'/patients',         'icon'=>'tabler-users',         'color'=>'success'],
    ['label'=>'Lab Reports', 'desc'=>'Lab results',    'url'=>'/lab/orders',       'icon'=>'tabler-flask',         'color'=>'warning'],
    ['label'=>'Referrals',   'desc'=>'Refer patient',  'url'=>'/referrals',        'icon'=>'tabler-transfer',      'color'=>'primary'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',     'icon'=>'tabler-user-circle',   'color'=>'success'],
  ],
  'receptionist' => [
    ['label'=>'Add Patient', 'desc'=>'New patient',    'url'=>'/patients',         'icon'=>'tabler-user-plus',     'color'=>'primary'],
    ['label'=>'Appointments','desc'=>'Book a visit',   'url'=>'/appointments',     'icon'=>'tabler-calendar-plus', 'color'=>'success'],
    ['label'=>'Billing',     'desc'=>'Invoices',       'url'=>'/billing/invoices', 'icon'=>'tabler-file-invoice',  'color'=>'warning'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',     'icon'=>'tabler-user-circle',   'color'=>'primary'],
  ],
  'nurse' => [
    ['label'=>'Patients',    'desc'=>'Search records', 'url'=>'/patients',               'icon'=>'tabler-users',             'color'=>'primary'],
    ['label'=>'Ward',        'desc'=>'Beds & wards',   'url'=>'/ward',                   'icon'=>'tabler-building-hospital', 'color'=>'success'],
    ['label'=>'Food Intake', 'desc'=>'Log meals',      'url'=>'/food-intake',            'icon'=>'tabler-meat',              'color'=>'warning'],
    ['label'=>'Fluid Intake','desc'=>'Log fluids',     'url'=>'/fluid-intake',           'icon'=>'tabler-droplet',           'color'=>'primary'],
    ['label'=>'My Rounds',   'desc'=>'Your rounds',    'url'=>'/staff-rounds/my-rounds', 'icon'=>'tabler-clock-check',       'color'=>'success'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',           'icon'=>'tabler-user-circle',       'color'=>'warning'],
  ],
  'staff' => [
    ['label'=>'Patients',    'desc'=>'Search records', 'url'=>'/patients',               'icon'=>'tabler-users',             'color'=>'primary'],
    ['label'=>'Food Intake', 'desc'=>'Log meals',      'url'=>'/food-intake',            'icon'=>'tabler-meat',              'color'=>'success'],
    ['label'=>'Fluid Intake','desc'=>'Log fluids',     'url'=>'/fluid-intake',           'icon'=>'tabler-droplet',           'color'=>'warning'],
    ['label'=>'My Rounds',   'desc'=>'Your rounds',    'url'=>'/staff-rounds/my-rounds', 'icon'=>'tabler-clock-check',       'color'=>'primary'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',           'icon'=>'tabler-user-circle',       'color'=>'success'],
  ],
  'pharmacist' => [
    ['label'=>'Drug Master', 'desc'=>'Drug stock',     'url'=>'/pharmacy/drugs',         'icon'=>'tabler-pill',              'color'=>'primary'],
    ['label'=>'Dispensing',  'desc'=>'Dispense',       'url'=>'/pharmacy/dispensing',    'icon'=>'tabler-clipboard-list',    'color'=>'success'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',           'icon'=>'tabler-user-circle',       'color'=>'warning'],
  ],
  'lab_assistant' => [
    ['label'=>'Lab Orders',  'desc'=>'Pending orders', 'url'=>'/lab/orders',             'icon'=>'tabler-flask',             'color'=>'primary'],
    ['label'=>'Lab Tests',   'desc'=>'Test catalog',   'url'=>'/lab/tests',              'icon'=>'tabler-test-pipe',         'color'=>'success'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',           'icon'=>'tabler-user-circle',       'color'=>'warning'],
  ],
  'patient' => [
    ['label'=>'Appointments','desc'=>'Book a visit',   'url'=>'/appointments',           'icon'=>'tabler-calendar',          'color'=>'primary'],
    ['label'=>'Lab Results', 'desc'=>'Your results',   'url'=>'/lab/orders',             'icon'=>'tabler-flask',             'color'=>'success'],
    ['label'=>'My Profile',  'desc'=>'Account',        'url'=>'/user/profile',           'icon'=>'tabler-user-circle',       'color'=>'warning'],
  ],
];
$quickActions = $allActions[$currentRoleStr] ?? $allActions['admin'];
@endphp

<p class="hms-section-header">Quick Actions</p>
<div class="row g-3 mb-6">
  @foreach($quickActions as $action)
  <div class="hms-action-col" style="--action-count: {{ count($quickActions) }};">
    <a href="{{ $action['url'] }}" class="hms-action-tile" aria-label="Go to {{ $action['label'] }}">
      <div class="action-icon bg-label-{{ $action['color'] }}">
        <i class="icon-base ti {{ $action['icon'] }} text-{{ $action['color'] }}"></i>
      </div>
      <div class="action-text">
        <p class="action-label">{{ $action['label'] }}</p>
        <p class="action-desc">{{ $action['desc'] }}</p>
      </div>
    </a>
  </div>
  @endforeach
</div>

@php
// Only the two remaining Operational Alerts cards still use the old
// single-stat-card + modal mechanism (Available Beds/Pending Bills).
// Every KPI category card below opens its own popup directly by id.
$modalBackedLabels = ['Available Beds', 'Pending Bills'];
@endphp

{{-- Overall Statistics —
     admin/super_admin: one card per category, two paired metrics each,
     every card opens its own popup (kpiCategories() in BuildsDashboardStats).
     Every other role: their own single-stat cards (unchanged), just restyled
     to the same two-colour system via the shared .hms-stat-card CSS. --}}
@if(!empty($kpiCategories))
<p class="hms-section-header">Overall Statistics</p>
<div class="row g-4 mb-6">
  @foreach($kpiCategories as $cat)
  <div class="col-sm-6 col-xl-3">
    <div class="hms-kpi-card" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_{{ $cat['modal'] }}"
      aria-label="View {{ $cat['category'] }} details">
      <div class="hms-kpi-header">
        <span class="hms-kpi-icon"><i class="icon-base ti {{ $cat['icon'] }}"></i></span>
        <p class="hms-kpi-title">{{ $cat['category'] }}</p>
      </div>
      <div class="hms-kpi-pair">
        @foreach($cat['metrics'] as $metric)
        <div class="hms-kpi-metric">
          <span class="hms-kpi-metric-label">{{ $metric['label'] }}</span>
          <div class="hms-kpi-metric-value">{{ $metric['value'] }}</div>
        </div>
        @endforeach
      </div>
      <span class="hms-kpi-btn">View Details</span>
    </div>
  </div>
  @endforeach
</div>
@else
<p class="hms-section-header">Overall Statistics</p>
<div class="row g-4 mb-6">
  @foreach($mainStats as $stat)
  <div class="col-6 col-xl-3">
    @include('admin.partials.stat-card', ['stat' => $stat])
  </div>
  @endforeach
</div>
@if(!empty($secondaryStats))
<div class="row g-4 mb-6">
  @foreach($secondaryStats as $stat)
  <div class="col-6 col-xl-3">
    @include('admin.partials.stat-card', ['stat' => $stat])
  </div>
  @endforeach
</div>
@endif
@endif


@if(!empty($operationalStats))
<p class="hms-section-header">Operational Alerts</p>
<div class="row g-4 mb-6">
  @foreach($operationalStats as $stat)
  <div class="col-6 col-xl-3">
    @include('admin.partials.stat-card', ['stat' => $stat])
  </div>
  @endforeach
</div>
@endif

@if(in_array($currentRoleStr, ['super_admin','admin']))
<!-- Popup: Patients -->
<div class="modal fade" id="modal_kpi_patients" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-users text-primary" style="font-size:1rem;"></i>
          </span>Patient Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $totalP     = $detailStats['patients']['total'];
        $activeP    = $detailStats['patients']['active'];
        $discharged = $detailStats['patients']['discharged'];
        $deceased   = $detailStats['patients']['deceased'];
        $ipP        = $detailStats['patients']['ip'];
        $opP        = $detailStats['patients']['op'];
        $todayP     = $detailStats['patients']['today'];
        $monthP     = $detailStats['patients']['month'];
        $aIP        = $detailStats['patients']['activeIp'];
        $aOP        = $detailStats['patients']['activeOp'];
        $rec        = $detailStats['patients']['recent'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $totalP }}</h4><small class="text-muted">Total Patients</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $activeP }}</h4><small class="text-muted">Active Patients</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $ipP }}</h4><small class="text-muted">In Patients</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $opP }}</h4><small class="text-muted">Out Patients</small></div></div>
        </div>
        @foreach([['Active IP',$aIP],['Active OP',$aOP],['Discharged',$discharged],['Deceased',$deceased],['Registered Today',$todayP],['This Month',$monthP]] as [$l,$v])
        <div class="stat-detail-row"><span class="text-muted small">{{ $l }}</span><span class="badge bg-label-primary">{{ $v }}</span></div>
        @endforeach
        <p class="text-muted small fw-semibold text-uppercase mb-2 mt-4" style="letter-spacing:0.5px;">Recent Active Patients</p>
        @foreach($rec as $p)
        <a href="{{ route('patients.show', $p->id) }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View patient — {{ $p->name }}, {{ $p->mrn }}">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-initial rounded-circle bg-label-primary" style="width:28px;height:28px;font-size:0.7rem;display:flex;align-items:center;justify-content:center;">{{ strtoupper(substr($p->name,0,1)) }}</div>
            <span class="small fw-medium">{{ $p->name }}</span>
          </div>
          <span class="text-muted small">{{ $p->mrn }}</span>
        </a>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('patients.index') }}" class="btn btn-primary btn-sm"><i class="icon-base ti tabler-users me-1"></i>View All Patients</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Appointments -->
<div class="modal fade" id="modal_kpi_appointments" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-warning" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-calendar text-warning" style="font-size:1rem;"></i>
          </span>Appointment Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $sched  = $detailStats['appointments']['scheduledToday'];
        $comp   = $detailStats['appointments']['completedToday'];
        $canc   = $detailStats['appointments']['cancelledToday'];
        $opA    = $detailStats['appointments']['opToday'];
        $emer   = $detailStats['appointments']['emergencyToday'];
        $totalA = $detailStats['appointments']['total'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-warning">{{ $sched }}</h4><small class="text-muted">Scheduled</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-success">{{ $comp }}</h4><small class="text-muted">Completed</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-danger">{{ $canc }}</h4><small class="text-muted">Cancelled</small></div></div>
        </div>
        @foreach([['OP',$opA,'primary'],['Emergency',$emer,'danger'],['All Time',$totalA,'secondary']] as [$l,$v,$c])
        <div class="stat-detail-row"><span class="text-muted small">{{ $l }}</span><span class="badge bg-label-{{ $c }}">{{ $v }}</span></div>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('appointments.index') }}" class="btn btn-warning btn-sm">View All Appointments</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Admissions -->
<div class="modal fade" id="modal_kpi_admissions" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-building-hospital text-primary" style="font-size:1rem;"></i>
          </span>Admission Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $totalAdm  = $detailStats['admissions']['total'];
        $activeAdm = $detailStats['admissions']['active'];
        $recentAdm = $detailStats['admissions']['recent'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $totalAdm }}</h4><small class="text-muted">Total Admissions</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $activeAdm }}</h4><small class="text-muted">Active Admissions</small></div></div>
        </div>
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Recently Admitted</p>
        @forelse($recentAdm as $adm)
        <a href="{{ route('ward.admissions', ['patient_id' => $adm->patient_id]) }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View admission — {{ $adm->patient->name ?? 'N/A' }}">
          <div><span class="small fw-medium d-block">{{ $adm->patient->name ?? 'N/A' }}</span><span class="text-muted" style="font-size:0.75rem;">{{ $adm->admission_no }}</span></div>
          <span class="text-muted small">{{ \Carbon\Carbon::parse($adm->admission_date)->format('d M Y') }}</span>
        </a>
        @empty
        <div class="text-center py-4"><small class="text-muted">No active admissions</small></div>
        @endforelse
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('ward.admissions') }}" class="btn btn-primary btn-sm">View All Admissions</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Available Beds -->
<div class="modal fade" id="modal_available_beds" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-info" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-bed text-info" style="font-size:1rem;"></i>
          </span>Ward & Bed Statistics
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $tW  = $detailStats['wards']['total'];
        $tB  = $detailStats['wards']['totalBeds'];
        $aB  = $detailStats['wards']['availableBeds'];
        $oB  = $tB - $aB;
        $wds = $detailStats['wards']['list'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $tW }}</h4><small class="text-muted">Wards</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-danger">{{ $oB }}</h4><small class="text-muted">Occupied</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-success">{{ $aB }}</h4><small class="text-muted">Available</small></div></div>
        </div>
        @foreach($wds as $w)
        <a href="{{ route('ward.show', $w->id) }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View ward — {{ $w->name }}">
          <span class="small fw-medium">{{ $w->name }}</span>
          <div class="d-flex gap-2">
            <span class="badge bg-label-danger">{{ ($w->total_beds??0)-($w->available_beds??0) }} occupied</span>
            <span class="badge bg-label-success">{{ $w->available_beds??0 }} free</span>
          </div>
        </a>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('ward.index') }}" class="btn btn-info btn-sm">View Wards</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Doctors -->
<div class="modal fade" id="modal_kpi_doctors" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-stethoscope text-primary" style="font-size:1rem;"></i>
          </span>Doctor Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $actD  = $detailStats['doctors']['active'];
        $ina   = $detailStats['doctors']['inactive'];
        $availD = $detailStats['doctors']['availableToday'];
        $recD  = $detailStats['doctors']['recent'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $actD }}</h4><small class="text-muted">Active</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $availD }}</h4><small class="text-muted">Available Today</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $ina }}</h4><small class="text-muted">Inactive</small></div></div>
        </div>
        @foreach($recD as $d)
        <a href="{{ route('doctors.show', $d->id) }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View doctor — Dr. {{ $d->name }}">
          <div class="d-flex align-items-center gap-2">
            <img src="{{ $d->photo_url }}" alt="" class="rounded-circle flex-shrink-0" style="width:28px;height:28px;object-fit:cover;"
              onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
            <div><span class="small fw-medium d-block">Dr. {{ $d->name }}</span><span class="text-muted" style="font-size:0.75rem;">{{ $d->specialization }}</span></div>
          </div>
          <span class="badge bg-label-{{ $d->status==='active'?'success':'secondary' }}">{{ $d->status }}</span>
        </a>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('doctors.index') }}" class="btn btn-primary btn-sm">View All Doctors</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Revenue -->
<div class="modal fade" id="modal_kpi_revenue" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-currency-dollar text-primary" style="font-size:1rem;"></i>
          </span>Revenue Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $cur   = $hospitalSettings->currency ?? 'LKR';
        $tRev  = $detailStats['revenue']['total'];
        $todR  = $detailStats['revenue']['today'];
        $monR  = $detailStats['revenue']['month'];
        $outst = $detailStats['revenue']['outstanding'];
        $tInv  = $detailStats['revenue']['invoices'];
        $pInv  = $detailStats['revenue']['paidCount'];
        $uInv  = $detailStats['revenue']['unpaidCount'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-6"><div class="p-3 border rounded text-center"><h5 class="mb-0 text-success">{{ $cur }} {{ number_format($todR) }}</h5><small class="text-muted">Today</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h5 class="mb-0 text-primary">{{ $cur }} {{ number_format($monR) }}</h5><small class="text-muted">This Month</small></div></div>
        </div>
        @foreach([['Total Revenue',$cur.' '.number_format($tRev),'success'],['Outstanding',$cur.' '.number_format($outst),'danger'],['Total Invoices',$tInv,'secondary'],['Paid',$pInv,'success'],['Unpaid',$uInv,'warning']] as [$l,$v,$c])
        <div class="stat-detail-row"><span class="text-muted small">{{ $l }}</span><span class="fw-medium text-{{ $c }} small">{{ $v }}</span></div>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('billing.invoices.index') }}" class="btn btn-success btn-sm">View Invoices</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Pending Bills -->
<div class="modal fade" id="modal_pending_bills" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-warning" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-file-invoice text-warning" style="font-size:1rem;"></i>
          </span>Pending Bills
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $cur2  = $hospitalSettings->currency ?? 'LKR';
        $pBill = $detailStats['revenue']['pendingList'];
        $tPend = $detailStats['revenue']['unpaidTotal'];
        $tOut  = $detailStats['revenue']['outstanding'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-6"><div class="p-3 border rounded text-center"><h5 class="mb-0 text-warning">{{ $cur2 }} {{ number_format($tPend) }}</h5><small class="text-muted">Total Billed</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h5 class="mb-0 text-danger">{{ $cur2 }} {{ number_format($tOut) }}</h5><small class="text-muted">Outstanding</small></div></div>
        </div>
        @foreach($pBill as $inv)
        <a href="{{ route('billing.invoices.show', $inv->id) }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View invoice — {{ $inv->invoice_no }}">
          <div><span class="small fw-medium d-block">{{ $inv->patient->name ?? 'N/A' }}</span><span class="text-muted" style="font-size:0.75rem;">{{ $inv->invoice_no }}</span></div>
          <span class="text-danger small fw-medium">{{ $cur2 }} {{ number_format($inv->balance) }}</span>
        </a>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('billing.invoices.index') }}" class="btn btn-warning btn-sm">View Invoices</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Pharmacy -->
<div class="modal fade" id="modal_kpi_pharmacy" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-pill text-primary" style="font-size:1rem;"></i>
          </span>Pharmacy Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $lsL  = $detailStats['drugs']['lowStock'];
        $neL  = $detailStats['drugs']['nearExpiry'];
        $tDrg = $detailStats['drugs']['total'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $tDrg }}</h4><small class="text-muted">Total Medicines</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-danger">{{ $lsL->count() }}</h4><small class="text-muted">Low Stock</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-warning">{{ $neL->count() }}</h4><small class="text-muted">Near Expiry</small></div></div>
        </div>
        @foreach($lsL as $dr)
        <a href="{{ route('pharmacy.drugs.index') }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View pharmacy — {{ $dr->drug_name }}">
          <div><span class="small fw-medium d-block">{{ $dr->drug_name }}</span><span class="text-muted" style="font-size:0.75rem;">{{ $dr->drug_code }}</span></div>
          <span class="badge bg-label-{{ $dr->current_stock==0?'danger':'warning' }}">{{ $dr->current_stock }} units</span>
        </a>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('pharmacy.drugs.index') }}" class="btn btn-primary btn-sm">View Pharmacy</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Laboratory -->
<div class="modal fade" id="modal_kpi_laboratory" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-flask text-primary" style="font-size:1rem;"></i>
          </span>Laboratory Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $totalTests   = $detailStats['labs']['total'];
        $pendingTests = $detailStats['labs']['pending'];
        $recentLabs   = $detailStats['labs']['recent'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $totalTests }}</h4><small class="text-muted">Total Tests</small></div></div>
          <div class="col-6"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $pendingTests }}</h4><small class="text-muted">Pending Tests</small></div></div>
        </div>
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Pending Orders</p>
        @forelse($recentLabs as $lab)
        <a href="{{ route('lab.orders.index') }}" class="stat-detail-row text-decoration-none text-reset hms-list-link"
          aria-label="View lab order — {{ $lab->patient->name ?? 'N/A' }}">
          <div><span class="small fw-medium d-block">{{ $lab->patient->name ?? 'N/A' }}</span><span class="text-muted" style="font-size:0.75rem;">{{ $lab->patient->mrn ?? 'N/A' }}</span></div>
          <span class="badge bg-label-primary">Pending</span>
        </a>
        @empty
        <div class="text-center py-4"><small class="text-muted">No pending lab orders</small></div>
        @endforelse
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('lab.orders.index') }}" class="btn btn-primary btn-sm">View All Lab Orders</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Popup: Blood Bank -->
<div class="modal fade" id="modal_kpi_blood_bank" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="hms-modal-header">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
            <i class="icon-base ti tabler-droplet text-primary" style="font-size:1rem;"></i>
          </span>Blood Bank Summary
        </h5>
        <button type="button" class="hms-modal-close" data-bs-dismiss="modal" aria-label="Close">
          <i class="icon-base ti tabler-x"></i>
        </button>
      </div>
      <div class="modal-body p-4">
        @php
        $bbAvailable  = $detailStats['bloodBank']['available'];
        $bbLowGroups  = $detailStats['bloodBank']['lowGroups'];
        $bbNearExpiry = $detailStats['bloodBank']['nearExpiry'];
        $bbByGroup    = $detailStats['bloodBank']['byGroup'];
        @endphp
        <div class="row g-3 mb-4">
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $bbAvailable }}</h4><small class="text-muted">Available Units</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $bbLowGroups }}</h4><small class="text-muted">Low Stock Groups</small></div></div>
          <div class="col-4"><div class="p-3 border rounded text-center"><h4 class="mb-0 text-primary">{{ $bbNearExpiry }}</h4><small class="text-muted">Near Expiry</small></div></div>
        </div>
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Units by Blood Group</p>
        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group)
        <div class="stat-detail-row">
          <span class="text-muted small">{{ $group }}</span>
          <span class="badge bg-label-{{ ($bbByGroup[$group] ?? 0) < 5 ? 'danger' : 'primary' }}">{{ $bbByGroup[$group] ?? 0 }} units</span>
        </div>
        @endforeach
      </div>
      <div class="modal-footer border-top">
        <a href="{{ route('blood-bank.index') }}" class="btn btn-primary btn-sm">View Blood Bank</a>
        <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endif

{{-- Bottom Row --}}
<div class="row g-4 mb-6">

  {{-- Departments --}}
  @if(in_array($currentRoleStr, ['super_admin','admin','receptionist','doctor']))
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-building me-2 text-primary"></i>Departments</h6>
        <small class="text-muted">Patient distribution</small>
      </div>
      <div class="p-3">
        @forelse($departments as $dept)
        @php $total = $departments->sum('patients_count') ?: 1; @endphp
        <a href="{{ route('patients.index') }}" class="d-block mb-3 p-1 rounded text-decoration-none text-reset hms-list-link"
          aria-label="View patients — {{ $dept->name }} department">
          <div class="d-flex justify-content-between mb-1">
            <small class="fw-medium">{{ $dept->name }}</small>
            <small class="text-muted">{{ $dept->patients_count }}</small>
          </div>
          <div class="progress" style="height:6px;">
            <div class="progress-bar bg-primary" style="width:{{ ($dept->patients_count/$total)*100 }}%"></div>
          </div>
        </a>
        @empty
        <div class="text-center py-3"><small class="text-muted">No department data</small></div>
        @endforelse
      </div>
    </div>
  </div>
  @endif

  {{-- Today's Appointments --}}
  @if($currentRoleStr !== 'patient')
  @php
  $canOpenAppointments = auth()->user()->hasAnyRole(['super_admin', 'admin', 'doctor', 'receptionist']);
  @endphp
  <div class="{{ in_array($currentRoleStr,['super_admin','admin','receptionist','doctor'])?'col-md-4':'col-md-6' }}">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-calendar me-2 text-warning"></i>Today's Appointments</h6>
        <span class="text-muted small">{{ now()->format('d M') }}</span>
      </div>
      @php
      $apptIndexUrl = $canOpenAppointments ? route('appointments.index') : route('dashboard.placeholder', ['feature' => 'Appointments']);
      @endphp
      @if(count($todayAppointments)===0)
      <div class="text-center py-4">
        <i class="icon-base ti tabler-calendar-off text-muted" style="font-size:2rem;"></i>
        <p class="text-muted mt-2 mb-3 small">No appointments today</p>
        <a href="{{ $apptIndexUrl }}" class="btn btn-sm btn-outline-primary">View All Appointments</a>
      </div>
      @else
      @foreach($todayAppointments as $appt)
      @php
      $apptUrl = $canOpenAppointments ? route('appointments.show', $appt['id']) : route('dashboard.placeholder', ['feature' => 'Appointment Details']);
      @endphp
      <a href="{{ $apptUrl }}"
        class="d-flex align-items-center gap-3 px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
        aria-label="View appointment — {{ $appt['name'] }} with {{ $appt['doctor'] }}">
        <div class="avatar-initial rounded-circle bg-label-{{ $appt['color'] }}"
          style="width:32px;height:32px;font-size:0.75rem;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
          {{ strtoupper(substr($appt['name'],0,1)) }}
        </div>
        <div class="flex-grow-1" style="min-width:0;">
          <p class="mb-0 fw-medium text-truncate" style="font-size:0.82rem;">{{ $appt['name'] }}</p>
          <small class="text-muted">{{ $appt['doctor'] }}</small>
        </div>
        <small class="text-muted flex-shrink-0">{{ $appt['time'] }}</small>
      </a>
      @endforeach
      @endif
    </div>
  </div>
  @endif

  {{-- Recent Patients --}}
  @if($currentRoleStr !== 'patient')
  @php
  $canOpenPatients = auth()->user()->hasAnyRole(['super_admin', 'admin', 'doctor', 'receptionist', 'nurse']);
  $patientsIndexUrl = $canOpenPatients ? route('patients.index') : route('dashboard.placeholder', ['feature' => 'Patients']);
  @endphp
  <div class="{{ in_array($currentRoleStr,['super_admin','admin','receptionist','doctor'])?'col-md-4':'col-md-6' }}">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-users me-2 text-success"></i>Recent Patients</h6>
        <a href="{{ $patientsIndexUrl }}" class="text-primary small">View All</a>
      </div>
      @forelse($recentPatients as $patient)
      @php
      $patientUrl = $canOpenPatients ? route('patients.show', $patient['id']) : route('dashboard.placeholder', ['feature' => 'Patient Details']);
      @endphp
      <a href="{{ $patientUrl }}"
        class="d-flex align-items-center gap-3 px-3 py-2 border-bottom text-decoration-none hms-list-link"
        aria-label="View patient — {{ $patient['name'] }}, {{ $patient['mrn'] }}">
        <img src="{{ $patient['photo_url'] }}" alt="{{ $patient['name'] }}"
          class="rounded-circle flex-shrink-0" style="width:32px;height:32px;object-fit:cover;"
          onerror="this.onerror=null;this.src='{{ asset('images/defaults/patient.png') }}';" />
        <div class="flex-grow-1" style="min-width:0;">
          <p class="mb-0 fw-medium text-truncate text-body" style="font-size:0.82rem;">{{ $patient['name'] }}</p>
          <small class="text-muted">{{ $patient['mrn'] }}</small>
        </div>
        <span class="badge bg-label-{{ $patient['status']==='active'?'success':'secondary' }}" style="font-size:0.65rem;">
          {{ $patient['status'] }}
        </span>
      </a>
      @empty
      <div class="text-center py-4"><small class="text-muted">No patients found</small></div>
      @endforelse
    </div>
  </div>
  @endif

  {{-- Patient Portal --}}
  @if($currentRoleStr === 'patient')
  <div class="col-md-6">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-calendar me-2 text-primary"></i>My Appointments</h6>
        <a href="{{ route('appointments.index') }}" class="text-primary small">View All</a>
      </div>
      @if(isset($appointments) && $appointments->count() > 0)
      @foreach($appointments as $appt)
      <a href="{{ route('appointments.index') }}"
        class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
        aria-label="View my appointments — Dr. {{ $appt->doctor->name ?? 'N/A' }}">
        <div>
          <p class="mb-0 fw-medium small">Dr. {{ $appt->doctor->name ?? 'N/A' }}</p>
          <small class="text-muted">{{ \Carbon\Carbon::parse($appt->appointment_date)->format('d M Y') }} · {{ \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') }}</small>
        </div>
        <span class="badge bg-label-{{ $appt->status==='completed'?'success':($appt->status==='cancelled'?'danger':'warning') }} text-capitalize">{{ $appt->status }}</span>
      </a>
      @endforeach
      @else
      <div class="text-center py-4">
        <i class="icon-base ti tabler-calendar-off text-muted" style="font-size:2rem;"></i>
        <p class="text-muted mt-2 small">No appointments found.</p>
      </div>
      @endif
    </div>
  </div>
  <div class="col-md-6">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-user me-2 text-success"></i>My Information</h6>
        <a href="{{ route('profile.show') }}" class="text-primary small">Edit</a>
      </div>
      @if(isset($patientRecord) && $patientRecord)
      <div class="p-3">
        <div class="d-flex justify-content-center mb-3">
          <img src="{{ $patientRecord->photo_url }}" alt="{{ $patientRecord->name }}"
            class="rounded-circle" style="width:72px;height:72px;object-fit:cover;"
            onerror="this.onerror=null;this.src='{{ asset('images/defaults/patient.png') }}';" />
        </div>
        @foreach([['MRN',$patientRecord->mrn],['Blood Group',$patientRecord->blood_group??'N/A'],['Gender',ucfirst($patientRecord->gender??'N/A')],['Phone',$patientRecord->phone??'N/A'],['Type',$patientRecord->patient_type==='ip'?'In Patient':'Out Patient'],['Status',ucfirst($patientRecord->status)]] as [$l,$v])
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="text-muted small">{{ $l }}</span>
          <span class="fw-medium small text-capitalize">{{ $v }}</span>
        </div>
        @endforeach
      </div>
      @else
      <div class="text-center py-4">
        <i class="icon-base ti tabler-user-off text-muted" style="font-size:2rem;"></i>
        <p class="text-muted mt-2 small">No patient record linked.</p>
      </div>
      @endif
    </div>
  </div>
  @endif

</div>

{{-- Super Admin + Admin Extra Sections --}}
@if(in_array($currentRoleStr, ['super_admin','admin']))

{{-- Charts + Pending Actions + Top Doctors + Recent Activity --}}
<div class="row g-4 mb-6">

  {{-- Patient Registration Chart --}}
  <div class="col-md-6">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-users me-2 text-primary"></i>Patient Registrations</h6>
        <a href="{{ route('reports.patients') }}" class="text-primary small">View Report</a>
      </div>
      <a href="{{ route('reports.patients') }}" class="d-block p-3 text-decoration-none" aria-label="View patient registrations report">
        <canvas id="patientChart" height="200" style="cursor:pointer;"></canvas>
      </a>
    </div>
  </div>

  {{-- Revenue Chart --}}
  <div class="col-md-6">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-currency-dollar me-2 text-success"></i>Revenue Trend</h6>
        <a href="{{ route('reports.billing') }}" class="text-primary small">View Report</a>
      </div>
      <a href="{{ route('reports.billing') }}" class="d-block p-3 text-decoration-none" aria-label="View revenue report">
        <canvas id="revenueChart" height="200" style="cursor:pointer;"></canvas>
      </a>
    </div>
  </div>

</div>

<div class="row g-4 mb-6">

  {{-- Pending Actions --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-bell me-2 text-warning"></i>Pending Actions</h6>
      </div>
      <div class="p-0">
        @php
        $pActions = [
          ['Consent Forms',   $detailStats['pending']['consentForms'], route('consent-forms.index'),  'warning'],
          ['Referrals',       $detailStats['pending']['referrals'],    route('referrals.index'),      'info'],
          ['Lab Orders',      $detailStats['pending']['labOrders'],    route('lab.orders.index'),     'primary'],
          ['Unpaid Invoices', $detailStats['pending']['invoices'],     route('billing.invoices.index'),'danger'],
          ['Overdue Rounds',  $detailStats['pending']['rounds'],       route('staff-rounds.index'),   'danger'],
        ];
        @endphp
        @foreach($pActions as [$l,$c,$u,$col])
        <a href="{{ $u }}" class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom text-decoration-none hms-list-link"
          aria-label="View {{ $l }}">
          <span class="text-body small">{{ $l }}</span>
          @if($c > 0)
          <span class="badge bg-label-{{ $col }}">{{ $c }}</span>
          @else
          <span class="badge bg-label-success">✓</span>
          @endif
        </a>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Top Doctors --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-stethoscope me-2 text-primary"></i>Top Doctors</h6>
        <small class="text-muted">This month</small>
      </div>
      <div class="p-0">
        @php $topDocs = $detailStats['appointments']['topDoctors']; @endphp
        @forelse($topDocs as $i => $ap)
        @if($ap->doctor)
        <a href="{{ route('doctors.show', $ap->doctor->id) }}"
          class="d-flex align-items-center gap-3 px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
          aria-label="View doctor — Dr. {{ $ap->doctor->name }}">
        @else
        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
        @endif
          <span class="badge bg-label-{{ ['primary','success','warning','info','secondary'][$i]??'secondary' }}">#{{ $i+1 }}</span>
          @if($ap->doctor)
          <img src="{{ $ap->doctor->photo_url }}" alt="" class="rounded-circle flex-shrink-0" style="width:32px;height:32px;object-fit:cover;"
            onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
          @endif
          <div class="flex-grow-1" style="min-width:0;">
            <p class="mb-0 fw-medium text-truncate" style="font-size:0.82rem;">Dr. {{ $ap->doctor->name??'N/A' }}</p>
            <small class="text-muted">{{ $ap->doctor->specialization??'' }}</small>
          </div>
          <span class="badge bg-label-primary">{{ $ap->total }} appts</span>
        @if($ap->doctor)
        </a>
        @else
        </div>
        @endif
        @empty
        <div class="text-center py-4"><small class="text-muted">No appointments this month</small></div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Recent Activity --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-history me-2 text-info"></i>Recent Activity</h6>
        <a href="{{ route('settings.activity-log') }}" class="text-primary small">View All</a>
      </div>
      <div class="p-0">
        @if(isset($recentActivity) && $recentActivity->count() > 0)
        @foreach($recentActivity as $log)
        @if($log->link_url)
        <a href="{{ $log->link_url }}"
          class="d-flex align-items-center gap-3 px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
          aria-label="View — {{ $log->description }}">
        @else
        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
        @endif
          <div class="avatar-initial rounded-circle bg-label-info"
            style="width:30px;height:30px;font-size:0.7rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            {{ strtoupper(substr($log->user->name??'S',0,1)) }}
          </div>
          <div class="flex-grow-1" style="min-width:0;">
            <p class="mb-0 fw-medium text-truncate" style="font-size:0.82rem;">{{ $log->description }}</p>
            <small class="text-muted">{{ $log->user->name??'System' }} · {{ $log->created_at->diffForHumans() }}</small>
          </div>
        @if($log->link_url)
        </a>
        @else
        </div>
        @endif
        @endforeach
        @else
        <div class="text-center py-4"><small class="text-muted">No recent activity</small></div>
        @endif
      </div>
    </div>
  </div>

</div>

{{-- Super Admin Only: Module Status + Users by Role + System Health --}}
@if($currentRoleStr === 'super_admin')

{{-- Quick Stats --}}
<div class="row g-4 mb-6">
  @php
  $qStats = [
    ['Total Staff',        $detailStats['staffCount'],                    'tabler-users-group',      'primary', route('staff.index')],
    ['Total Wards',        $detailStats['wards']['total'],                'tabler-building-hospital','info',    route('ward.index')],
    ['Pending Lab Orders', $detailStats['pending']['labOrders'],          'tabler-flask',            'warning', route('lab.orders.index')],
    ['Near Expiry Drugs',  $detailStats['drugs']['nearExpiry']->count(),  'tabler-calendar-x',       'danger',  route('pharmacy.drugs.index')],
    ['Consent Pending',    $detailStats['pending']['consentForms'],       'tabler-writing',          'secondary', route('consent-forms.index')],
    ['Referrals Pending',  $detailStats['pending']['referrals'],          'tabler-transfer',         'success', route('referrals.index')],
  ];
  @endphp
  @foreach($qStats as [$ql,$qc,$qi,$qcol,$qurl])
  <div class="col-6 col-md-4 col-xl-2">
    <a href="{{ $qurl }}" class="hms-stat-card hms-stat-{{ $qcol }} d-block text-decoration-none text-reset"
      aria-label="View {{ $ql }}">
      <div class="hms-stat-icon hms-stat-icon-{{ $qcol }}">
        <i class="icon-base ti {{ $qi }}"></i>
      </div>
      <div class="hms-stat-body">
        <h4 class="mb-1 fw-bold">{{ $qc }}</h4>
        <small class="hms-stat-label d-block">{{ $ql }}</small>
      </div>
      <div class="hms-stat-footer">
        <span class="hms-stat-btn">
          View Details
          <i class="icon-base ti tabler-arrow-right" style="font-size:0.8rem;"></i>
        </span>
      </div>
    </a>
  </div>
  @endforeach
</div>

<div class="row g-4 mb-6">

  {{-- Module Status --}}
  <div class="col-md-8">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-apps me-2 text-primary"></i>Module Status</h6>
        <a href="{{ route('settings.index') }}" class="text-primary small">Manage</a>
      </div>
      <div class="p-3">
        <div class="row g-2">
          @php
          $moduleUrls = [
            'lab' => route('lab.orders.index'), 'pharmacy' => route('pharmacy.drugs.index'),
            'billing' => route('billing.invoices.index'), 'ward' => route('ward.index'),
            'food_intake' => route('food-intake.index'), 'fluid_intake' => route('fluid-intake.index'),
            'staff_rounds' => route('staff-rounds.index'), 'consent_forms' => route('consent-forms.index'),
            'referrals' => route('referrals.index'), 'reports' => route('reports.index'),
            // portal.* routes are role:patient-only, so super_admin can't open them —
            // point at the settings page (where the module toggle itself lives) instead.
            'patient_portal' => route('settings.index'),
          ];
          @endphp
          @foreach($moduleStatus as $mod)
          @php $en = $features[$mod['key']] ?? true; $mUrl = $moduleUrls[$mod['key']] ?? null; @endphp
          <div class="col-md-6">
            @if($mUrl)
            <a href="{{ $mUrl }}" class="d-flex align-items-center justify-content-between p-2 border rounded text-decoration-none text-reset hms-list-link"
              aria-label="View {{ $mod['name'] }}">
            @else
            <div class="d-flex align-items-center justify-content-between p-2 border rounded">
            @endif
              <div class="d-flex align-items-center gap-2">
                <span class="avatar-initial rounded bg-label-{{ $mod['color'] }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                  <i class="icon-base ti {{ $mod['icon'] }}" style="font-size:0.875rem;"></i>
                </span>
                <span style="font-size:0.82rem;font-weight:500;">{{ $mod['name'] }}</span>
              </div>
              <span class="badge bg-label-{{ $en?'success':'secondary' }}">{{ $en?'ON':'OFF' }}</span>
            @if($mUrl)
            </a>
            @else
            </div>
            @endif
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- Users by Role --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-users me-2 text-info"></i>Users by Role</h6>
        <a href="{{ route('users.index') }}" class="text-primary small">Manage</a>
      </div>
      <div class="p-0">
        @foreach($usersByRole as $rd)
        <a href="{{ route('users.index') }}" class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
          aria-label="Manage users — {{ $rd['role'] }}">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-initial rounded-circle bg-label-{{ $rd['color'] }}" style="width:24px;height:24px;font-size:0.65rem;display:flex;align-items:center;justify-content:center;">{{ strtoupper(substr($rd['role'],0,1)) }}</div>
            <span style="font-size:0.82rem;">{{ $rd['role'] }}</span>
          </div>
          <span class="badge bg-label-{{ $rd['color'] }}">{{ $rd['count'] }}</span>
        </a>
        @endforeach
        <div class="d-flex justify-content-between align-items-center px-3 py-2">
          <span class="fw-semibold" style="font-size:0.82rem;">Total</span>
          <span class="badge bg-label-primary">{{ collect($usersByRole)->sum('count') }}</span>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- System Health + Settings Overview --}}
<div class="row g-4 mb-6">

  {{-- System Health --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-server me-2 text-success"></i>System Health</h6>
        <a href="{{ route('users.index') }}" class="text-primary small">Users</a>
      </div>
      <div class="p-0">
        @foreach([
          ['Database Size', $systemHealth['db_size'],         'tabler-database',      'warning'],
          ['Total Users',   $systemHealth['total_users'],     'tabler-users',         'primary'],
          ['PHP Version',   $systemHealth['php_version'],     'tabler-brand-php',     'secondary'],
          ['Laravel',       $systemHealth['laravel_version'], 'tabler-brand-laravel', 'danger'],
          ['Server Time',   $systemHealth['server_time'],     'tabler-clock',         'info'],
        ] as [$l,$v,$ic,$c])
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti {{ $ic }} text-{{ $c }}" style="font-size:0.875rem;"></i>
            <span class="text-muted" style="font-size:0.82rem;">{{ $l }}</span>
          </div>
          <span class="fw-medium" style="font-size:0.82rem;">{{ $v }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Settings Overview --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-settings me-2 text-secondary"></i>Settings Overview</h6>
        <a href="{{ route('settings.index') }}" class="text-primary small">Edit</a>
      </div>
      <div class="p-0">
        @foreach([
          ['MRN Prefix',         $hospitalSettings->mrn_prefix??'HMS',        'tabler-id',              'primary'],
          ['Doctor Prefix',      $hospitalSettings->doctor_id_prefix??'DR',   'tabler-stethoscope',     'info'],
          ['Employee Prefix',    $hospitalSettings->employee_id_prefix??'EMP','tabler-id-badge',        'success'],
          ['Currency',           $hospitalSettings->currency??'LKR',           'tabler-currency-dollar', 'warning'],
          ['Auto Patient Login', $hospitalSettings->auto_patient_login?'Enabled':'Disabled', 'tabler-user-plus', $hospitalSettings->auto_patient_login?'success':'secondary'],
          ['Auto Doctor Login',  $hospitalSettings->auto_doctor_login?'Enabled':'Disabled',  'tabler-stethoscope', $hospitalSettings->auto_doctor_login?'success':'secondary'],
        ] as [$l,$v,$ic,$c])
        <a href="{{ route('settings.index') }}" class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
          aria-label="Edit settings — {{ $l }}">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti {{ $ic }} text-{{ $c }}" style="font-size:0.875rem;"></i>
            <span class="text-muted" style="font-size:0.82rem;">{{ $l }}</span>
          </div>
          <span class="fw-medium" style="font-size:0.82rem;">{{ $v }}</span>
        </a>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Activity Log Preview --}}
  <div class="col-md-4">
    <div class="hms-card">
      <div class="hms-card-header">
        <h6><i class="icon-base ti tabler-history me-2 text-info"></i>System Activity</h6>
        <a href="{{ route('settings.activity-log') }}" class="text-primary small">View All</a>
      </div>
      <div class="p-0">
        @if(isset($recentActivity) && $recentActivity->count() > 0)
        @foreach($recentActivity->take(4) as $log)
        @if($log->link_url)
        <a href="{{ $log->link_url }}"
          class="d-flex align-items-center gap-2 px-3 py-2 border-bottom text-decoration-none text-reset hms-list-link"
          aria-label="View — {{ $log->description }}">
        @else
        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
        @endif
          <div class="avatar-initial rounded-circle bg-label-secondary" style="width:26px;height:26px;font-size:0.65rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ strtoupper(substr($log->user->name??'S',0,1)) }}</div>
          <div class="flex-grow-1" style="min-width:0;">
            <p class="mb-0 text-truncate" style="font-size:0.78rem;">{{ $log->description }}</p>
            <small class="text-muted" style="font-size:0.7rem;">{{ $log->created_at->diffForHumans() }}</small>
          </div>
        @if($log->link_url)
        </a>
        @else
        </div>
        @endif
        @endforeach
        @endif
      </div>
    </div>
  </div>

</div>

@endif
{{-- End Super Admin Only --}}

@endif
{{-- End Super Admin + Admin --}}

@endsection

@section('page-script')
<script>
(function () {
  const container = document.getElementById('systemAlertsSection');
  if (!container) return;

  setTimeout(function () {
    container.querySelectorAll('.hms-toast').forEach(function (toast) {
      toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(24px)';
      setTimeout(function () { toast.remove(); }, 400);
    });
  }, 30000);
})();

function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2,'0');
  const m = String(now.getMinutes()).padStart(2,'0');
  const el = document.getElementById('liveClock');
  if (el) el.textContent = h+':'+m;
}
updateClock();
setInterval(updateClock, 1000);

@if(isset($monthlyPatients) && isset($monthlyRevenue))
const patientLabels  = @json(collect($monthlyPatients)->pluck('month'));
const patientCounts  = @json(collect($monthlyPatients)->pluck('count'));
const revenueLabels  = @json(collect($monthlyRevenue)->pluck('month'));
const revenueAmounts = @json(collect($monthlyRevenue)->pluck('amount'));

const patientCtx = document.getElementById('patientChart');
if (patientCtx) {
  new Chart(patientCtx, {
    type: 'bar',
    data: {
      labels: patientLabels,
      datasets: [{ label:'Patients', data:patientCounts, backgroundColor:'rgba(105,108,255,0.2)', borderColor:'rgba(105,108,255,1)', borderWidth:2, borderRadius:6 }]
    },
    options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true } } }
  });
}

const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
  new Chart(revenueCtx, {
    type: 'line',
    data: {
      labels: revenueLabels,
      datasets: [{ label:'Revenue', data:revenueAmounts, backgroundColor:'rgba(113,221,55,0.1)', borderColor:'rgba(113,221,55,1)', borderWidth:2, tension:0.4, fill:true, pointBackgroundColor:'rgba(113,221,55,1)' }]
    },
    options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true } } }
  });
}
@endif
</script>
@endsection