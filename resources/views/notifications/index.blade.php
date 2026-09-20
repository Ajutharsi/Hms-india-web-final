@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Notifications - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
  <i class="icon-base ti tabler-alert-triangle me-2"></i>{{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="card mb-6">
  <div class="card-widget-separator-wrapper">
    <div class="card-body card-widget-separator">
      <div class="row gy-4 gy-sm-1">
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $totalSent }}</h4>
              <p class="mb-0">Sent</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-success text-heading">
                <i class="icon-base ti tabler-send icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none me-6" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
            <div>
              <h4 class="mb-0">{{ $totalFailed }}</h4>
              <p class="mb-0">Failed</p>
            </div>
            <div class="avatar me-lg-6">
              <span class="avatar-initial rounded bg-label-danger text-heading">
                <i class="icon-base ti tabler-x icon-26px"></i>
              </span>
            </div>
          </div>
          <hr class="d-none d-sm-block d-lg-none" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
            <div>
              <h4 class="mb-0">{{ $totalPending }}</h4>
              <p class="mb-0">Pending</p>
            </div>
            <div class="avatar me-sm-6">
              <span class="avatar-initial rounded bg-label-warning text-heading">
                <i class="icon-base ti tabler-clock icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-0">{{ $todayCount }}</h4>
              <p class="mb-0">Today</p>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info text-heading">
                <i class="icon-base ti tabler-bell-ringing icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Notification Logs Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Notification Logs</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('notifications.templates') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-template me-1"></i> Templates
      </a>
      <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSend">
        <i class="icon-base ti tabler-send me-1"></i> Send Notification
      </button>
    </div>
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="logsTable">
      <thead>
        <tr>
          <th></th>
          <th>Patient</th>
          <th>Channel</th>
          <th>Recipient</th>
          <th>Template</th>
          <th>Message</th>
          <th>Status</th>
          <th>Sent At</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Send Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSend">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Send Notification</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form action="{{ route('notifications.send') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label">Patient</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" id="send_patient">
            <option value="">None / Manual</option>
            @foreach ($patients as $p)
            <option value="{{ $p->id }}" data-phone="{{ $p->phone }}" data-email="{{ $p->email }}" @selected(old('patient_id')==$p->id)>{{ $p->name }} ({{ $p->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Channel *</label>
          <select class="form-select @error('channel') is-invalid @enderror" name="channel" id="send_channel" required>
            <option value="sms" @selected(old('channel','sms')=='sms')>SMS</option>
            <option value="whatsapp" @selected(old('channel')=='whatsapp')>WhatsApp</option>
            <option value="email" @selected(old('channel')=='email')>Email</option>
          </select>
          @error('channel') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-4">
          <label class="form-label">Recipient *</label>
          <input type="text" class="form-control @error('recipient') is-invalid @enderror" name="recipient" id="send_recipient" placeholder="Phone or Email" value="{{ old('recipient') }}" required />
          @error('recipient') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-6">
          <label class="form-label">Template *</label>
          <select class="form-select @error('template') is-invalid @enderror" name="template" required>
            <option value="">Select Template</option>
            @foreach ($templates as $t)
            <option value="{{ $t->name }}" @selected(old('template')==$t->name)>{{ $t->title }}</option>
            @endforeach
          </select>
          @error('template') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-send me-1"></i> Send
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('page-script')
<script>
let logsTable;

function statusColor(s) {
  return { sent: 'success', failed: 'danger', pending: 'warning' }[s] || 'secondary';
}

function channelColor(c) {
  return { sms: 'primary', whatsapp: 'success', email: 'info' }[c] || 'secondary';
}

document.addEventListener('DOMContentLoaded', function () {
  logsTable = $('#logsTable').DataTable({
    processing: true,
    ajax: { url: '{{ route('notifications.data') }}', type: 'GET' },
    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'patient_name' },
      { data: 'channel', render: d => `<span class="badge bg-label-${channelColor(d)}">${d}</span>` },
      { data: 'recipient' },
      { data: 'template', defaultContent: '—' },
      { data: 'message', render: d => `<small class="text-muted text-truncate d-inline-block" style="max-width:250px;" title="${d.replace(/"/g,'&quot;')}">${d}</small>` },
      { data: 'status', render: (d, t, r) => `<span class="badge bg-label-${statusColor(d)}" title="${r.error_message || ''}">${d}</span>` },
      { data: 'sent_at', defaultContent: '—' },
    ]
  });

  document.getElementById('send_patient').addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    const channel = document.getElementById('send_channel').value;
    if (!opt || !opt.value) return;
    document.getElementById('send_recipient').value = channel === 'email' ? (opt.dataset.email || '') : (opt.dataset.phone || '');
  });

  document.getElementById('send_channel').addEventListener('change', function () {
    document.getElementById('send_patient').dispatchEvent(new Event('change'));
  });
});

@if ($errors->any())
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasSend')).show();
});
@endif
</script>
@endsection

@endsection
