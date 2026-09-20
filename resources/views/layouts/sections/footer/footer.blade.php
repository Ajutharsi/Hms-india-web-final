@php
$containerFooter =
isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
? 'container-xxl'
: 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
        &#169;
        <script>document.write(new Date().getFullYear());</script>
        <strong>Hospital Management System</strong> — Powered by
        <a href="https://trenexa.com" target="_blank" class="footer-link fw-medium">
          Trenexa IT Solutions
        </a>
      </div>
      <div class="d-none d-lg-inline-block">
        <span class="text-muted me-3" style="font-size:13px">
          <i class="icon-base ti tabler-shield-check text-success me-1"></i>
          Secured System
        </span>
        <span class="text-muted me-3" style="font-size:13px">
          <i class="icon-base ti tabler-user me-1"></i>
          {{ auth()->user()->name ?? '' }}
        </span>
        <span class="badge bg-label-primary" style="font-size:11px">
          {{ ucfirst(auth()->user()->getRoleNames()->first() ?? '') }}
        </span>
      </div>
    </div>
  </div>
</footer>
<!-- / Footer -->