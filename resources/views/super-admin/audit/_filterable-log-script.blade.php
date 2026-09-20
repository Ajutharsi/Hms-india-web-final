@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const filterIds = ['filterModule','filterAction','filterHospital','filterUser','filterRole','filterEventType','filterStatus','filterFrom','filterTo','filterSearch'];
  const filterParamMap = { filterModule: 'module', filterAction: 'action', filterHospital: 'hospital_id', filterUser: 'user_id', filterRole: 'role', filterEventType: 'event_type', filterStatus: 'status', filterFrom: 'from_date', filterTo: 'to_date', filterSearch: 'search' };

  @foreach($filters as $key => $value)
  (function(){
    const map = {module:'filterModule',action:'filterAction',hospital_id:'filterHospital',user_id:'filterUser',role:'filterRole',event_type:'filterEventType',status:'filterStatus',from_date:'filterFrom',to_date:'filterTo',search:'filterSearch'};
    const el = document.getElementById(map['{{ $key }}']);
    if (el) el.value = @json($value);
  })();
  @endforeach

  function currentFilters() {
    const params = {};
    filterIds.forEach(id => {
      const el = document.getElementById(id);
      if (el && el.value) params[filterParamMap[id]] = el.value;
    });
    return params;
  }

  const columns = @json($columns);
  const orderableColumns = @json($orderableColumns);
  const isOrderable = (key) => orderableColumns.length === 0 || orderableColumns.includes(key);
  const dtColumns = columns.map(c => ({ data: c.data, name: c.data, orderable: isOrderable(c.data) }));

  const table = $('#logTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    ajax: {
      url: '{{ $dataUrl }}',
      type: 'GET',
      data: function (d) { Object.assign(d, currentFilters()); }
    },
    columns: dtColumns,
    pageLength: 25,
    order: [[0, 'desc']],
    language: { processing: '<div class="spinner-border text-primary" role="status"></div>', emptyTable: 'Not tracked yet.', zeroRecords: 'Not tracked yet.' },
    createdRow: function (row, data) {
      if (data.is_failure) { $(row).addClass('table-danger'); }
    }
  });

  filterIds.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', function () {
      if (el.tagName === 'INPUT') {
        clearTimeout(el._debounce);
        el._debounce = setTimeout(() => { table.draw(); loadTimelineIfActive(); }, 300);
      } else {
        table.draw();
        loadTimelineIfActive();
      }
    });
  });

  let timelineActive = false;
  function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  function loadTimelineIfActive() {
    if (!timelineActive) return;
    const params = new URLSearchParams(Object.assign({ draw: 1, start: 0, length: 50, 'order[0][column]': 0, 'order[0][dir]': 'desc' }, currentFilters()));
    columns.forEach((c, i) => { params.append(`columns[${i}][data]`, c.data); params.append(`columns[${i}][name]`, c.data); });

    fetch('{{ $dataUrl }}?' + params.toString())
      .then(r => r.json())
      .then(json => {
        const body = document.getElementById('timelineBody');
        if (!json.data || !json.data.length) { body.innerHTML = '<p class="not-tracked text-center py-6">Not tracked yet.</p>'; return; }
        body.innerHTML = json.data.map(row => `
          <div class="timeline-item ${row.is_failure ? 'failure' : ''}">
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between">
                <strong>${escapeHtml(row.action)}</strong>
                <small class="text-body-secondary">${escapeHtml(row.created_at)}</small>
              </div>
              <p class="mb-1">${escapeHtml(row.description)}</p>
              <small class="text-body-secondary">${escapeHtml(row.user)} &middot; ${escapeHtml(row.module)} &middot; ${escapeHtml(row.ip_address)}</small>
            </div>
          </div>
        `).join('');
      });
  }

  document.getElementById('viewTableBtn').addEventListener('click', function () {
    timelineActive = false;
    this.classList.add('btn-primary','active'); this.classList.remove('btn-outline-primary');
    const tl = document.getElementById('viewTimelineBtn');
    tl.classList.add('btn-outline-primary'); tl.classList.remove('btn-primary','active');
    document.getElementById('tableViewContainer').classList.remove('d-none');
    document.getElementById('timelineViewContainer').classList.add('d-none');
  });
  document.getElementById('viewTimelineBtn').addEventListener('click', function () {
    timelineActive = true;
    this.classList.add('btn-primary','active'); this.classList.remove('btn-outline-primary');
    const tb = document.getElementById('viewTableBtn');
    tb.classList.add('btn-outline-primary'); tb.classList.remove('btn-primary','active');
    document.getElementById('tableViewContainer').classList.add('d-none');
    document.getElementById('timelineViewContainer').classList.remove('d-none');
    loadTimelineIfActive();
  });
});
</script>
@endsection
