@php
$configData = Helper::appClasses();
$hospital = $ticket->hospital;
$subscription = $hospital?->currentSubscription;
@endphp

@extends('layouts/layoutMaster')

@section('title', $ticket->ticket_number . ' - Support Centre - HMS')

@section('page-style')
<style>
.timeline-item { position: relative; padding-left: 2.25rem; padding-bottom: 1.5rem; }
.timeline-item:last-child { padding-bottom: 0; }
.timeline-item::before { content:''; position:absolute; left:0.6rem; top:1.6rem; bottom:0; width:2px; background:var(--bs-border-color); }
.timeline-item:last-child::before { display:none; }
.timeline-dot { position:absolute; left:0; top:0.15rem; width:1.35rem; height:1.35rem; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; }
.timeline-card { border:1px solid var(--bs-border-color); border-radius:10px; padding:0.85rem 1rem; }
.timeline-card.internal { background: var(--bs-warning-bg-subtle, rgba(255,171,0,0.08)); border-color: rgba(255,171,0,0.3); }
.hospital-quick-link { display:flex; align-items:center; gap:0.5rem; padding:0.55rem 0.75rem; border:1px solid var(--bs-border-color); border-radius:8px; text-decoration:none; color:inherit; font-size:0.8125rem; }
.hospital-quick-link:hover { background: var(--bs-secondary-bg, rgba(0,0,0,0.03)); color:inherit; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[
  ['label' => 'Platform Dashboard', 'url' => route('dashboard')],
  ['label' => 'Support Centre', 'url' => route('super-admin.support.index')],
  ['label' => $ticket->ticket_number],
]" />

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="hms-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h4 class="mb-0">{{ $ticket->subject }}</h4>
      @if($ticket->trashed())<span class="badge bg-label-secondary">Archived</span>@endif
      @if($ticket->isOverdue())<span class="badge bg-label-danger">Overdue</span>@endif
    </div>
    <p class="mb-0 text-body-secondary">{{ $ticket->ticket_number }} &middot; {{ $hospital?->name ?? 'No hospital' }} &middot; Created {{ $ticket->created_at->diffForHumans() }}</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    @unless($ticket->trashed())
    @if(in_array($ticket->status, ['closed', 'resolved']))
    <form action="{{ route('super-admin.support.reopen', $ticket) }}" method="POST">@csrf
      <button type="submit" class="btn btn-label-primary"><i class="icon-base ti tabler-refresh me-1"></i> Reopen</button>
    </form>
    @else
    <form action="{{ route('super-admin.support.close', $ticket) }}" method="POST">@csrf
      <button type="submit" class="btn btn-label-secondary"><i class="icon-base ti tabler-circle-check me-1"></i> Close Ticket</button>
    </form>
    @endif
    @if(in_array($ticket->status, ['resolved', 'closed']))
    <button class="btn btn-label-danger" onclick="archiveTicket({{ $ticket->id }})"><i class="icon-base ti tabler-archive me-1"></i> Archive</button>
    @endif
    @else
    <button class="btn btn-label-success" onclick="restoreTicket({{ $ticket->id }})"><i class="icon-base ti tabler-refresh me-1"></i> Restore</button>
    @endunless
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">

    {{-- Ticket Information --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-info-circle me-1"></i> Ticket Information</h6></div>
      <div class="card-body">
        <p class="mb-3">{{ $ticket->description }}</p>
        <div class="row g-3">
          <div class="col-sm-4"><small class="text-body-secondary d-block">Contact Person</small><strong>{{ $ticket->contact_name ?? '—' }}</strong></div>
          <div class="col-sm-4"><small class="text-body-secondary d-block">Contact Email</small><strong>{{ $ticket->contact_email ?? '—' }}</strong></div>
          <div class="col-sm-4"><small class="text-body-secondary d-block">Category</small><strong class="text-capitalize">{{ str_replace('_',' ',$ticket->category) }}</strong></div>
        </div>
        @unless($ticket->trashed())
        <button class="btn btn-sm btn-label-secondary mt-3" data-bs-toggle="offcanvas" data-bs-target="#offcanvasTicketEdit">
          <i class="icon-base ti tabler-edit me-1"></i> Edit Details
        </button>
        @endunless
      </div>
    </div>

    {{-- Conversation Timeline (public replies) --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-messages me-1"></i> Conversation Timeline</h6></div>
      <div class="card-body">
        @forelse($publicReplies as $reply)
        <div class="timeline-item">
          <span class="timeline-dot bg-label-primary"><i class="icon-base ti tabler-message"></i></span>
          <div class="timeline-card">
            <div class="d-flex justify-content-between mb-1">
              <strong class="small">{{ $reply->user?->name ?? 'System' }}</strong>
              <small class="text-muted">{{ $reply->created_at->format('d M Y H:i') }}</small>
            </div>
            <p class="mb-0 small">{{ $reply->body }}</p>
          </div>
        </div>
        @empty
        <p class="text-body-secondary text-center py-4 mb-0"><small>No replies yet — sent replies are emailed to the hospital contact.</small></p>
        @endforelse
      </div>
      @unless($ticket->trashed())
      <div class="card-footer border-top">
        <form action="{{ route('super-admin.support.replies.store', $ticket) }}" method="POST">
          @csrf
          <textarea name="body" class="form-control mb-2" rows="2" placeholder="Write a public reply — this will be emailed to {{ $ticket->contact_email ?? 'the contact' }}..." required></textarea>
          <button type="submit" class="btn btn-primary btn-sm"><i class="icon-base ti tabler-send me-1"></i> Send Reply</button>
        </form>
      </div>
      @endunless
    </div>

    {{-- Internal Notes (staff-only) --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-note me-1"></i> Internal Notes <span class="badge bg-label-warning ms-1">Staff only</span></h6></div>
      <div class="card-body">
        @forelse($internalNotes as $note)
        <div class="timeline-item">
          <span class="timeline-dot bg-label-warning"><i class="icon-base ti tabler-note"></i></span>
          <div class="timeline-card internal">
            <div class="d-flex justify-content-between mb-1">
              <strong class="small">{{ $note->user?->name ?? 'System' }}</strong>
              <small class="text-muted">{{ $note->created_at->format('d M Y H:i') }}</small>
            </div>
            <p class="mb-0 small">{{ $note->body }}</p>
          </div>
        </div>
        @empty
        <p class="text-body-secondary text-center py-4 mb-0"><small>No internal notes yet.</small></p>
        @endforelse
      </div>
      @unless($ticket->trashed())
      <div class="card-footer border-top">
        <form action="{{ route('super-admin.support.notes.store', $ticket) }}" method="POST">
          @csrf
          <textarea name="body" class="form-control mb-2" rows="2" placeholder="Add an internal note — never visible to the hospital..." required></textarea>
          <button type="submit" class="btn btn-label-warning btn-sm"><i class="icon-base ti tabler-note me-1"></i> Add Note</button>
        </form>
      </div>
      @endunless
    </div>

    {{-- Attachments --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-paperclip me-1"></i> Attachments</h6></div>
      <div class="p-0">
        @forelse($ticket->attachments as $att)
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <i class="icon-base ti tabler-file"></i>
            <div>
              <p class="mb-0 small fw-medium">{{ $att->file_name }}</p>
              <small class="text-muted">{{ $att->file_size }} &middot; uploaded by {{ $att->uploadedBy?->name ?? 'System' }} &middot; {{ $att->created_at->diffForHumans() }}</small>
            </div>
          </div>
          <a href="{{ route('super-admin.support.attachments.download', $att) }}" class="btn btn-sm btn-label-secondary"><i class="icon-base ti tabler-download"></i></a>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No attachments</small></div>
        @endforelse
      </div>
      @unless($ticket->trashed())
      <div class="card-footer border-top">
        <form action="{{ route('super-admin.support.attachments.store', $ticket) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
          @csrf
          <input type="file" name="file" class="form-control form-control-sm" required>
          <button type="submit" class="btn btn-sm btn-label-secondary text-nowrap"><i class="icon-base ti tabler-upload me-1"></i> Attach</button>
        </form>
      </div>
      @endunless
    </div>

    {{-- Activity Timeline / Audit History --}}
    <div class="card">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-history me-1"></i> Activity Timeline &amp; Audit History</h6></div>
      <div class="p-0">
        @forelse($activityLog as $log)
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
          <span class="avatar-initial rounded-circle bg-label-primary" style="width:28px;height:28px;font-size:0.7rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="icon-base ti tabler-point"></i>
          </span>
          <div class="flex-grow-1">
            <p class="mb-0 small">{{ $log->description }}</p>
            <small class="text-muted">{{ $log->user_name }} &middot; {{ $log->created_at->diffForHumans() }}</small>
          </div>
        </div>
        @empty
        <div class="text-center py-6"><small class="text-muted">No activity recorded yet</small></div>
        @endforelse
      </div>
    </div>

  </div>

  <div class="col-lg-4">

    {{-- Quick Actions --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0">Quick Actions</h6></div>
      <div class="card-body">
        @unless($ticket->trashed())
        <label class="form-label small">Status</label>
        <form action="{{ route('super-admin.support.set-status', $ticket) }}" method="POST" class="mb-3">
          @csrf @method('PATCH')
          <select name="status" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
            @foreach(\App\Models\SupportTicket::STATUSES as $s)
            <option value="{{ $s }}" @selected($ticket->status===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
            @endforeach
          </select>
        </form>

        <label class="form-label small">Priority</label>
        <form action="{{ route('super-admin.support.set-priority', $ticket) }}" method="POST" class="mb-3">
          @csrf @method('PATCH')
          <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach(\App\Models\SupportTicket::PRIORITIES as $p)
            <option value="{{ $p }}" @selected($ticket->priority===$p)>{{ ucfirst($p) }}</option>
            @endforeach
          </select>
        </form>

        <label class="form-label small">Assigned Staff</label>
        <form action="{{ route('super-admin.support.assign', $ticket) }}" method="POST">
          @csrf
          <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Unassigned</option>
            @foreach($staff as $s)
            <option value="{{ $s->id }}" @selected($ticket->assigned_to===$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
        </form>
        @else
        <p class="text-body-secondary small mb-0">This ticket is archived. Restore it to make changes.</p>
        @endunless
      </div>
    </div>

    {{-- Assigned Staff summary --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0">Assigned Staff</h6></div>
      <div class="card-body">
        @if($ticket->assignedStaff)
        <div class="d-flex align-items-center gap-2">
          <span class="avatar-initial rounded-circle bg-label-primary" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">{{ strtoupper(substr($ticket->assignedStaff->name,0,1)) }}</span>
          <div>
            <p class="mb-0 fw-medium small">{{ $ticket->assignedStaff->name }}</p>
            <small class="text-muted">{{ $ticket->assignedStaff->email }}</small>
          </div>
        </div>
        @else
        <p class="text-body-secondary small mb-0">Not yet assigned.</p>
        @endif
      </div>
    </div>

    {{-- Hospital Information & Integration --}}
    <div class="card mb-4">
      <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-building-hospital me-1"></i> Hospital Information</h6></div>
      <div class="card-body">
        @if($hospital)
        <p class="mb-1 fw-medium">{{ $hospital->name }}</p>
        <p class="mb-3 small text-body-secondary">{{ $hospital->code }} &middot; {{ ucfirst($hospital->status) }}</p>

        <div class="d-flex flex-column gap-2 mb-3">
          <a href="{{ route('super-admin.hospitals.show', $hospital) }}" class="hospital-quick-link"><i class="icon-base ti tabler-building-hospital"></i> Hospital Details</a>
          <a href="{{ route('super-admin.hospitals.show', $hospital) }}#subscription" class="hospital-quick-link"><i class="icon-base ti tabler-crown"></i> Hospital Subscription</a>
          <a href="{{ route('super-admin.hospitals.show', $hospital) }}#audit" class="hospital-quick-link"><i class="icon-base ti tabler-history"></i> Hospital Audit Logs</a>
          <a href="{{ route('super-admin.hospitals.show', $hospital) }}#usage" class="hospital-quick-link"><i class="icon-base ti tabler-chart-bar"></i> Hospital Usage</a>
          <a href="{{ route('super-admin.analytics.index') }}" class="hospital-quick-link"><i class="icon-base ti tabler-chart-infographic"></i> Platform Analytics</a>
        </div>

        @php $adminUser = $hospital->adminUser(); @endphp
        @if($adminUser && ! session('impersonator_id'))
        <form action="{{ route('super-admin.hospitals.impersonate', $hospital) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-label-info w-100" onclick="return confirm('Log in as {{ addslashes($adminUser->name) }} ({{ addslashes($hospital->name) }})? This will be recorded in the audit log.')">
            <i class="icon-base ti tabler-user-shield me-1"></i> Login as Hospital Admin
          </button>
        </form>
        @endif

        {{-- Related Subscription --}}
        <hr>
        <p class="mb-1 small fw-medium">Related Subscription</p>
        @if($subscription)
        <div class="d-flex justify-content-between small mb-1"><span class="text-body-secondary">Plan</span><span>{{ $subscription->planCatalog?->name ?? ucfirst($subscription->plan) }}</span></div>
        <div class="d-flex justify-content-between small mb-1"><span class="text-body-secondary">Status</span><span class="text-capitalize">{{ $subscription->status }}</span></div>
        <div class="d-flex justify-content-between small"><span class="text-body-secondary">Expires</span><span>{{ $subscription->expires_at?->format('d M Y') ?? '—' }}</span></div>
        @else
        <p class="not-tracked small text-body-secondary fst-italic mb-0">No subscription on record for this hospital.</p>
        @endif
        @else
        <p class="text-body-secondary small mb-0">This ticket has no linked hospital.</p>
        @endif
      </div>
    </div>

  </div>
</div>

{{-- Edit Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTicketEdit" style="width:480px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Ticket</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('super-admin.support.update', $ticket) }}" method="POST">
      @csrf @method('PUT')
      <input type="hidden" name="hospital_id" value="{{ $ticket->hospital_id }}" />
      <div class="mb-3">
        <label class="form-label">Subject *</label>
        <input type="text" name="subject" class="form-control" value="{{ $ticket->subject }}" required maxlength="255" />
      </div>
      <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="description" class="form-control" rows="4" required>{{ $ticket->description }}</textarea>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label">Contact Name</label>
          <input type="text" name="contact_name" class="form-control" value="{{ $ticket->contact_name }}" />
        </div>
        <div class="col-6">
          <label class="form-label">Contact Email</label>
          <input type="email" name="contact_email" class="form-control" value="{{ $ticket->contact_email }}" />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Category *</label>
        <select name="category" class="form-select" required>
          @foreach(\App\Models\SupportTicket::CATEGORIES as $c)
          <option value="{{ $c }}" @selected($ticket->category===$c)>{{ ucwords(str_replace('_',' ',$c)) }}</option>
          @endforeach
        </select>
      </div>
      <input type="hidden" name="priority" value="{{ $ticket->priority }}" />
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Save</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('page-script')
<script>
function archiveTicket(id) {
  if (!confirm('Archive this ticket? It will be hidden from the active list but can be restored later.')) return;
  fetch(`/super-admin/support/${id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
  })
  .then(res => res.json().then(data => ({ ok: res.ok, data })))
  .then(({ ok, data }) => { if (ok && data.success) { window.location.reload(); } else { alert(data.error || 'Unable to archive this ticket.'); } })
  .catch(() => alert('Unable to archive this ticket — please try again.'));
}

function restoreTicket(id) {
  fetch(`/super-admin/support/${id}/restore`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => { if (data.success) window.location.reload(); });
}
</script>
@endsection
