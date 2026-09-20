{{--
    Shown on every authenticated page while a Super Admin is impersonating a
    hospital admin. See SUPER_ADMIN_IMPERSONATION_REPORT.md.

    Deliberately reads the session marker directly (not a route/controller
    concern) so it renders regardless of which page is currently loaded —
    this partial is included once in the shared layout, not per-page.
--}}
@if(session('impersonator_id'))
@php
$impersonation = \App\Models\Impersonation::with('hospital')->find(session('impersonation_id'));
@endphp
<div class="impersonation-banner">
  <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap px-3 py-2">
    <span class="d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-user-shield icon-20px"></i>
      <span>
        You are currently viewing <strong>{{ $impersonation?->hospital?->name ?? 'a hospital' }}</strong>
        as <strong>{{ auth()->user()->name }}</strong>
      </span>
    </span>
    <form action="{{ route('impersonate.stop') }}" method="POST" class="m-0">
      @csrf
      <button type="submit" class="btn btn-sm btn-light fw-semibold">
        <i class="icon-base ti tabler-logout-2 me-1"></i> Return to Super Admin
      </button>
    </form>
  </div>
</div>
<style>
.impersonation-banner {
  position: sticky;
  top: 0;
  z-index: 1045;
  background: linear-gradient(90deg, #7A5B00 0%, #B8860B 100%);
  color: #fff;
  font-size: 0.875rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.impersonation-banner .btn-light {
  color: #7A5B00;
}
</style>
@endif
