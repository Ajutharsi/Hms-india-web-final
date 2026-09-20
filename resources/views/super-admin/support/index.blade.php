@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Support Centre - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-style')
<style>
#ticketsTable tbody tr.ticket-row-clickable { cursor: pointer; transition: background-color 0.15s ease; }
#ticketsTable tbody tr.ticket-row-clickable:hover { background-color: var(--bs-secondary-bg, rgba(105,108,255,0.06)); }
.support-kpi { padding: 0.9rem 1rem; border: 1px solid var(--bs-border-color); border-radius: 10px; height: 100%; text-decoration: none; color: inherit; display: block; transition: transform 0.15s ease, box-shadow 0.15s ease; }
.support-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); color: inherit; }
.support-kpi .support-kpi-value { font-size: 1.3rem; font-weight: 600; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Support Centre']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Support Centre</h4>
    <p class="mb-0 text-body-secondary">Manage customer support tickets across every hospital on the platform.</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasTicketAdd">
    <i class="icon-base ti tabler-plus me-1"></i> New Ticket
  </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- KPI strip — every card is clickable, deep-linking into the filtered list below --}}
<div class="row g-3 mb-6 row-cols-2 row-cols-sm-3 row-cols-xl-5">
  <div class="col">
    <a href="{{ route('super-admin.support.index') }}" class="support-kpi">
      <div class="support-kpi-value">{{ $kpis['total'] }}</div>
      <small class="text-body-secondary">Total Tickets</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['status' => 'open']) }}" class="support-kpi">
      <div class="support-kpi-value text-primary">{{ $kpis['open'] }}</div>
      <small class="text-body-secondary">Open</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['status' => 'in_progress']) }}" class="support-kpi">
      <div class="support-kpi-value text-warning">{{ $kpis['inProgress'] }}</div>
      <small class="text-body-secondary">In Progress</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['status' => 'waiting_customer']) }}" class="support-kpi">
      <div class="support-kpi-value text-warning">{{ $kpis['waitingCustomer'] }}</div>
      <small class="text-body-secondary">Waiting for Customer</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['status' => 'resolved']) }}" class="support-kpi">
      <div class="support-kpi-value text-success">{{ $kpis['resolved'] }}</div>
      <small class="text-body-secondary">Resolved</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['status' => 'closed']) }}" class="support-kpi">
      <div class="support-kpi-value text-secondary">{{ $kpis['closed'] }}</div>
      <small class="text-body-secondary">Closed</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['priority' => 'high']) }}" class="support-kpi">
      <div class="support-kpi-value text-warning">{{ $kpis['highPriority'] }}</div>
      <small class="text-body-secondary">High Priority</small>
    </a>
  </div>
  <div class="col">
    <a href="{{ route('super-admin.support.index', ['overdue' => 1]) }}" class="support-kpi">
      <div class="support-kpi-value text-danger">{{ $kpis['overdue'] }}</div>
      <small class="text-body-secondary">Overdue</small>
    </a>
  </div>
  <div class="col">
    <div class="support-kpi" style="cursor:default;">
      @if($kpis['avgResolutionHours'] !== null)
      <div class="support-kpi-value">{{ $kpis['avgResolutionHours'] }}h</div>
      <small class="text-body-secondary">Avg. Resolution Time</small>
      @else
      <div class="support-kpi-value fst-italic text-body-secondary" style="font-size:0.8125rem;">Not tracked yet</div>
      <small class="text-body-secondary">Avg. Resolution Time</small>
      @endif
    </div>
  </div>
</div>

{{-- Recently Updated Tickets --}}
<div class="card mb-6">
  <div class="card-header border-bottom"><h6 class="mb-0"><i class="icon-base ti tabler-clock-edit me-1"></i> Recently Updated Tickets</h6></div>
  <div class="p-0">
    @forelse($recentlyUpdated as $t)
    <a href="{{ route('super-admin.support.show', $t) }}" class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-reset text-decoration-none">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar-initial rounded bg-label-primary" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">{{ $t->ticket_number }}</span>
        <div>
          <p class="mb-0 fw-medium small">{{ $t->subject }}</p>
          <small class="text-muted">{{ $t->hospital?->name ?? '—' }} &middot; {{ $t->updated_at->diffForHumans() }}</small>
        </div>
      </div>
      <span class="badge bg-label-secondary text-capitalize">{{ str_replace('_', ' ', $t->status) }}</span>
    </a>
    @empty
    <div class="text-center py-6"><small class="text-muted">No tickets yet</small></div>
    @endforelse
  </div>
</div>

{{-- Search & Filters --}}
<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="searchInput" placeholder="Search ticket #, subject, hospital..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">All Status</option>
          @foreach(\App\Models\SupportTicket::STATUSES as $s)
          <option value="{{ $s }}" @selected($statusFilter===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
          @endforeach
          <option value="archived" @selected($statusFilter==='archived')>Archived</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterPriority">
          <option value="">All Priorities</option>
          @foreach(\App\Models\SupportTicket::PRIORITIES as $p)
          <option value="{{ $p }}" @selected($priorityFilter===$p)>{{ ucfirst($p) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterCategory">
          <option value="">All Categories</option>
          @foreach(\App\Models\SupportTicket::CATEGORIES as $c)
          <option value="{{ $c }}" @selected($categoryFilter===$c)>{{ ucwords(str_replace('_',' ',$c)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="filterHospital">
          <option value="">All Hospitals</option>
          @foreach($hospitals as $h)
          <option value="{{ $h->id }}">{{ $h->name }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="row g-3 align-items-center mt-1">
      <div class="col-md-3">
        <select class="form-select" id="filterAssigned">
          <option value="">All Staff</option>
          @foreach($staff as $s)
          <option value="{{ $s->id }}">{{ $s->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control" id="filterContact" placeholder="Contact person..." />
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filterFrom" title="From date" />
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filterTo" title="To date" />
      </div>
      <div class="col-md-2">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="filterOverdue" @checked($overdueFilter==='1')>
          <label class="form-check-label" for="filterOverdue">Overdue only</label>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Tickets</h5>
    <small class="text-body-secondary">Scroll horizontally to see every column</small>
  </div>
  <div class="table-responsive">
    <table class="table" id="ticketsTable" style="width:100%">
      <thead>
        <tr>
          <th>Ticket #</th>
          <th>Subject</th>
          <th>Hospital</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Assigned</th>
          <th>Created</th>
          <th>Updated</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- New Ticket Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTicketAdd" style="width:480px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">New Support Ticket</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('super-admin.support.store') }}" method="POST">
      @csrf
      <div class="mb-3">
        <label class="form-label">Hospital *</label>
        <select name="hospital_id" class="form-select" required>
          <option value="">Select hospital...</option>
          @foreach($hospitals as $h)
          <option value="{{ $h->id }}">{{ $h->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Subject *</label>
        <input type="text" name="subject" class="form-control" required maxlength="255" />
      </div>
      <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="description" class="form-control" rows="4" required></textarea>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label">Contact Name</label>
          <input type="text" name="contact_name" class="form-control" placeholder="Defaults to hospital contact" />
        </div>
        <div class="col-6">
          <label class="form-label">Contact Email</label>
          <input type="email" name="contact_email" class="form-control" placeholder="Defaults to hospital email" />
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label">Priority *</label>
          <select name="priority" class="form-select" required>
            @foreach(\App\Models\SupportTicket::PRIORITIES as $p)
            <option value="{{ $p }}" @selected($p==='medium')>{{ ucfirst($p) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Category *</label>
          <select name="category" class="form-select" required>
            @foreach(\App\Models\SupportTicket::CATEGORIES as $c)
            <option value="{{ $c }}">{{ ucwords(str_replace('_',' ',$c)) }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Create Ticket</button>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('page-script')
<script>
let ticketsTable;

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams(window.location.search);

  ticketsTable = $('#ticketsTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    rowId: 'id',
    ajax: {
      url: '{{ route('super-admin.support.data') }}',
      type: 'GET',
      data: function (d) {
        d.status = document.getElementById('filterStatus').value;
        d.priority = document.getElementById('filterPriority').value;
        d.category = document.getElementById('filterCategory').value;
        d.hospital_id = document.getElementById('filterHospital').value;
        d.assigned_to = document.getElementById('filterAssigned').value;
        d.contact = document.getElementById('filterContact').value;
        d.from_date = document.getElementById('filterFrom').value;
        d.to_date = document.getElementById('filterTo').value;
        d.overdue = document.getElementById('filterOverdue').checked ? '1' : '';
      }
    },
    columns: [
      { data: 'ticket_number', name: 'ticket_number' },
      { data: 'subject', name: 'subject', render: (d) => `<span class="fw-medium">${escapeHtml(d)}</span>` },
      { data: 'hospital_name', name: 'hospital.name', orderable: false },
      { data: 'priority_badge', name: 'priority', orderable: false },
      { data: 'status_badge', name: 'status', orderable: false },
      { data: 'assigned_name', name: 'assigned_to', orderable: false },
      { data: 'created_display', name: 'created_at' },
      { data: 'updated_display', name: 'updated_at', orderable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    pageLength: 25,
    order: [[6, 'desc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>' },
    createdRow: function (row, data) {
      row.classList.add('ticket-row-clickable');
      row.dataset.ticketId = data.id;
    }
  });

  $('#ticketsTable tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('a, button, select, input').length) return;
    const id = this.dataset.ticketId;
    if (id) window.location.href = `/super-admin/support/${id}`;
  });

  ['filterStatus', 'filterPriority', 'filterCategory', 'filterHospital', 'filterAssigned', 'filterOverdue'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => ticketsTable.draw());
  });
  ['filterFrom', 'filterTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => ticketsTable.draw());
  });

  let searchDebounce;
  document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => ticketsTable.search(this.value).draw(), 300);
  });
  let contactDebounce;
  document.getElementById('filterContact').addEventListener('input', function () {
    clearTimeout(contactDebounce);
    contactDebounce = setTimeout(() => ticketsTable.draw(), 300);
  });

  if (params.get('new') === '1') {
    new bootstrap.Offcanvas(document.getElementById('offcanvasTicketAdd')).show();
  }
});
</script>
@endsection
