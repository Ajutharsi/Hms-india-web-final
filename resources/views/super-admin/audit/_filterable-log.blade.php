{{-- Shared filter bar + timeline/table toggle, reused by activity.blade.php and security.blade.php.
     Both views share the exact same columns/filters — only the data/export/print endpoints differ. --}}

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">{{ $title }}</h4>
    <p class="mb-0 text-body-secondary">{{ $description }}</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ $csvUrl }}" class="btn btn-label-secondary" id="exportCsvBtn"><i class="icon-base ti tabler-file-spreadsheet me-1"></i> Export CSV</a>
    <a href="{{ $printUrl }}" target="_blank" class="btn btn-label-secondary" id="printBtn"><i class="icon-base ti tabler-printer me-1"></i> Print</a>
  </div>
</div>

<div class="card mb-6">
  <div class="card-body py-4">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" class="form-control" id="filterSearch" placeholder="Search description, action, user..." />
        </div>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterModule">
          <option value="">All Modules</option>
          @foreach($modules as $m)
          <option value="{{ $m }}">{{ ucwords(str_replace('_',' ',$m)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterAction">
          <option value="">All Actions</option>
          @foreach($actions as $a)
          <option value="{{ $a }}">{{ ucwords(str_replace('_',' ',$a)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterHospital">
          <option value="">All Hospitals</option>
          @foreach($hospitals as $h)
          <option value="{{ $h->id }}">{{ $h->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" id="filterUser">
          <option value="">All Users</option>
          @foreach($users as $u)
          <option value="{{ $u->id }}">{{ $u->name }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="row g-3 align-items-center mt-1">
      <div class="col-md-2">
        <select class="form-select" id="filterRole">
          <option value="">All Roles</option>
          @foreach($roles as $r)
          <option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterEventType">
          <option value="">All Event Types</option>
          <option value="security">Security</option>
          <option value="config">Configuration</option>
          <option value="export">Export</option>
          <option value="login">Login</option>
          <option value="impersonation">Impersonation</option>
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" id="filterStatus">
          <option value="">Success & Failure</option>
          <option value="success">Success</option>
          <option value="failure">Failure</option>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filterFrom" title="From date" />
      </div>
      <div class="col-md-2">
        <input type="date" class="form-control" id="filterTo" title="To date" />
      </div>
      <div class="col-md-2 text-end">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-primary btn-sm active" id="viewTableBtn"><i class="icon-base ti tabler-table"></i> Table</button>
          <button type="button" class="btn btn-outline-primary btn-sm" id="viewTimelineBtn"><i class="icon-base ti tabler-timeline"></i> Timeline</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card" id="tableViewContainer">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Results</h5>
    <small class="text-body-secondary">Scroll horizontally to see every column</small>
  </div>
  <div class="table-responsive">
    <table class="table" id="logTable" style="width:100%">
      <thead><tr>@foreach($columns as $c)<th>{{ $c['title'] }}</th>@endforeach</tr></thead>
    </table>
  </div>
</div>

<div class="card d-none" id="timelineViewContainer">
  <div class="card-header border-bottom"><h5 class="mb-0">Timeline <small class="text-body-secondary fw-normal">— most recent 50</small></h5></div>
  <div class="card-body" id="timelineBody">
    <p class="not-tracked text-center py-6">Loading…</p>
  </div>
</div>
