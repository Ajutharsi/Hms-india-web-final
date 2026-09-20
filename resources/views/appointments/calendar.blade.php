@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Appointments Calendar - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-calendar.scss')
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
'resources/assets/vendor/libs/flatpickr/flatpickr.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/moment/moment.js',
])
@endsection

@section('content')
<div class="card app-calendar-wrapper">
  <div class="row g-0">

    {{-- Calendar Sidebar --}}
    <div class="col app-calendar-sidebar border-end" id="app-calendar-sidebar">
      <div class="border-bottom p-6 my-sm-0 mb-4">
        @role('super_admin|admin|receptionist')
        <a href="{{ route('appointments.index') }}" class="btn btn-primary w-100">
          <i class="icon-base ti tabler-plus icon-16px me-2"></i>
          Book Appointment
        </a>
        @endrole
      </div>

      <div class="px-3 pt-2">
        <div class="inline-calendar"></div>
      </div>

      <hr class="mb-6 mx-n4 mt-3" />

      <div class="px-6 pb-2">
        <h5 class="mb-4">Filter by Status</h5>
        <div class="app-calendar-events-filter text-heading">
          <div class="form-check mb-4 ms-2">
            <input class="form-check-input input-filter" type="checkbox"
              id="select-scheduled" data-value="scheduled" checked />
            <label class="form-check-label" for="select-scheduled">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ff9f43;margin-right:6px;"></span>
              Scheduled
            </label>
          </div>
          <div class="form-check mb-4 ms-2">
            <input class="form-check-input input-filter" type="checkbox"
              id="select-completed" data-value="completed" checked />
            <label class="form-check-label" for="select-completed">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#28c76f;margin-right:6px;"></span>
              Completed
            </label>
          </div>
          <div class="form-check mb-4 ms-2">
            <input class="form-check-input input-filter" type="checkbox"
              id="select-cancelled" data-value="cancelled" checked />
            <label class="form-check-label" for="select-cancelled">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ea5455;margin-right:6px;"></span>
              Cancelled
            </label>
          </div>
          <div class="form-check ms-2">
            <input class="form-check-input input-filter" type="checkbox"
              id="select-no_show" data-value="no_show" checked />
            <label class="form-check-label" for="select-no_show">
              <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#82868b;margin-right:6px;"></span>
              No Show
            </label>
          </div>
        </div>
      </div>
    </div>
    {{-- /Calendar Sidebar --}}

    {{-- Calendar Content --}}
    <div class="col app-calendar-content">
      <div class="card shadow-none border-0">
        <div class="card-body pb-0">
          <div id="calendar"></div>
        </div>
      </div>
      <div class="app-overlay"></div>
    </div>
    {{-- /Calendar Content --}}

  </div>
</div>
@endsection


@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl) return;

  if (typeof Calendar === 'undefined') {
    console.error('Calendar not loaded!');
    return;
  }

  const statusBadge = {
    scheduled: 'warning',
    completed: 'success',
    cancelled: 'danger',
    no_show:   'secondary',
  };

  let activeFilters = ['scheduled', 'completed', 'cancelled', 'no_show'];

  const calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, timegridPlugin, listPlugin, interactionPlugin],
    initialView:  'dayGridMonth',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    events: function(fetchInfo, successCallback, failureCallback) {
      fetch(`/appointments/calendar/data?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
        .then(res => res.json())
        .then(data => {
          const filtered = data.filter(e =>
            activeFilters.includes(e.extendedProps.status)
          );
          successCallback(filtered);
        })
        .catch(err => failureCallback(err));
    },
    editable:     false,
    dayMaxEvents: true,
    height:       'auto',
    eventClick: function(info) {
      const p = info.event.extendedProps;
      Swal.fire({
        title: `<strong>${p.patient}</strong>`,
        html: `
          <div class="text-start">
            <p class="mb-2"><strong>MRN:</strong> ${p.mrn ?? 'N/A'}</p>
            <p class="mb-2"><strong>Doctor:</strong> Dr. ${p.doctor}</p>
            <p class="mb-2"><strong>Date:</strong> ${info.event.startStr.split('T')[0]}</p>
            <p class="mb-2"><strong>Time:</strong> ${p.time ?? 'N/A'}</p>
            <p class="mb-2"><strong>Token:</strong> #${p.token ?? 'N/A'}</p>
            <p class="mb-2"><strong>Type:</strong>
              <span class="badge bg-label-${p.type === 'emergency' ? 'danger' : 'info'}">
                ${p.type ? p.type.toUpperCase() : 'N/A'}
              </span>
            </p>
            <p class="mb-0"><strong>Status:</strong>
              <span class="badge bg-label-${statusBadge[p.status] ?? 'secondary'}">
                ${p.status ? p.status.replace('_', ' ') : 'N/A'}
              </span>
            </p>
          </div>
        `,
        showCancelButton:   true,
        confirmButtonText:  'View Appointment',
        cancelButtonText:   'Close',
        confirmButtonColor: '#696cff',
      }).then(result => {
        if (result.isConfirmed) {
          window.location.href = '/appointments/' + info.event.id;
        }
      });
    },
  });

  calendar.render();

  const inlineCal = document.querySelector('.inline-calendar');
  if (inlineCal && typeof flatpickr !== 'undefined') {
    flatpickr(inlineCal, {
      inline: true,
      onChange: function(selectedDates) {
        if (selectedDates[0]) calendar.gotoDate(selectedDates[0]);
      }
    });
  }

  document.querySelectorAll('.input-filter').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const val = this.getAttribute('data-value');
      if (this.checked) {
        if (!activeFilters.includes(val)) activeFilters.push(val);
      } else {
        activeFilters = activeFilters.filter(f => f !== val);
      }
      calendar.refetchEvents();
    });
  });

});
</script>
@endsection