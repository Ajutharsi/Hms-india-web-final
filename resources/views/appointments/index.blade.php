@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Appointments - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
])
@endsection

@section('content')

@if($fromPatient)
<x-breadcrumb :items="[
  ['label' => 'Appointments', 'url' => route('appointments.index')],
  ['label' => $fromPatient->name],
]" />
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Today's Appointments</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $todayAppointments }}</h4>
            </div>
            <small>{{ now()->format('d M Y') }}</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-calendar icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Scheduled</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $scheduledAppointments }}</h4>
            </div>
            <small>Pending appointments</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-clock icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Completed</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $completedAppointments }}</h4>
            </div>
            <small>Today completed</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-circle-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Cancelled</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $cancelledAppointments }}</h4>
            </div>
            <small>Total cancelled</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-x icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Appointments Table --}}
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Appointments</h5>
    @role('super_admin|admin|receptionist')
    <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAppointmentAdd">
      <i class="icon-base ti tabler-plus me-1"></i> Book Appointment
    </button>
    @endrole
  </div>

  <div class="card-datatable table-responsive">
    <table class="table border-top" id="appointmentsTable">
      <thead>
        <tr>
          <th></th>
          <th>ID</th>
          <th>Patient</th>
          <th>Doctor</th>
          <th>Date</th>
          <th>Time</th>
          <th>Token</th>
          <th>Type</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  {{-- Add Appointment Offcanvas --}}
  @role('super_admin|admin|receptionist')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAppointmentAdd">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Book Appointment</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6">
      <form action="{{ route('appointments.store') }}" method="POST">
        @csrf

        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" required>
            <option value="">Select Patient</option>
            @foreach($patients as $patient)
            <option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->name }} ({{ $patient->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Doctor *</label>
          <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id" id="doctorSelect" required>
            <option value="">Select Doctor</option>
            @foreach($doctors as $doctor)
            <option value="{{ $doctor->id }}" data-photo="{{ $doctor->photo_url }}" @selected(old('doctor_id')==$doctor->id)>{{ $doctor->name }} - {{ $doctor->specialization }}</option>
            @endforeach
          </select>
          @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Date *</label>
          <input type="date" class="form-control @error('appointment_date') is-invalid @enderror" name="appointment_date"
            id="appointmentDate" min="{{ now()->toDateString() }}" value="{{ old('appointment_date') }}" required />
          @error('appointment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Time *</label>
          <select class="form-select @error('appointment_time') is-invalid @enderror" name="appointment_time" id="timeSlots" required>
            <option value="">Select Doctor & Date first</option>
          </select>
          @error('appointment_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Type *</label>
          <select class="form-select @error('type') is-invalid @enderror" name="type" required>
            <option value="op" @selected(old('type', 'op')=='op')>Out Patient (OP)</option>
            <option value="consult" @selected(old('type')=='consult')>Consult</option>
            <option value="emergency" @selected(old('type')=='emergency')>Emergency</option>
            <option value="followup" @selected(old('type')=='followup')>Follow Up</option>
          </select>
          @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status">
            <option value="scheduled" @selected(old('status', 'scheduled')=='scheduled')>Scheduled</option>
            <option value="completed" @selected(old('status')=='completed')>Completed</option>
            <option value="cancelled" @selected(old('status')=='cancelled')>Cancelled</option>
            <option value="no_show" @selected(old('status')=='no_show')>No Show</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Book
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

  {{-- Edit Appointment Offcanvas --}}
  @role('super_admin|admin|receptionist')
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAppointmentEdit">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Edit Appointment</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6">
      <form id="editAppointmentForm" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_appointment_id" id="edit_appointment_id" value="{{ old('_edit_appointment_id') }}" />

        <div class="mb-4">
          <label class="form-label">Patient *</label>
          <select class="form-select @error('patient_id') is-invalid @enderror" name="patient_id" id="edit_patient_id" required>
            <option value="">Select Patient</option>
            @foreach($patients as $patient)
            <option value="{{ $patient->id }}" @selected(old('patient_id')==$patient->id)>{{ $patient->name }} ({{ $patient->mrn }})</option>
            @endforeach
          </select>
          @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Doctor *</label>
          <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id" id="edit_doctor_id" required>
            <option value="">Select Doctor</option>
            @foreach($doctors as $doctor)
            <option value="{{ $doctor->id }}" data-photo="{{ $doctor->photo_url }}" @selected(old('doctor_id')==$doctor->id)>{{ $doctor->name }} - {{ $doctor->specialization }}</option>
            @endforeach
          </select>
          @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Date *</label>
          <input type="date" class="form-control @error('appointment_date') is-invalid @enderror" name="appointment_date" id="edit_appointment_date" value="{{ old('appointment_date') }}" required />
          @error('appointment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Time *</label>
          <input type="time" class="form-control @error('appointment_time') is-invalid @enderror" name="appointment_time" id="edit_appointment_time" value="{{ old('appointment_time') }}" required />
          @error('appointment_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Type *</label>
          <select class="form-select @error('type') is-invalid @enderror" name="type" id="edit_type" required>
            <option value="op" @selected(old('type')=='op')>Out Patient (OP)</option>
            <option value="consult" @selected(old('type')=='consult')>Consult</option>
            <option value="emergency" @selected(old('type')=='emergency')>Emergency</option>
            <option value="followup" @selected(old('type')=='followup')>Follow Up</option>
          </select>
          @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
          <label class="form-label">Status</label>
          <select class="form-select @error('status') is-invalid @enderror" name="status" id="edit_status">
            <option value="scheduled" @selected(old('status')=='scheduled')>Scheduled</option>
            <option value="completed" @selected(old('status')=='completed')>Completed</option>
            <option value="cancelled" @selected(old('status')=='cancelled')>Cancelled</option>
            <option value="no_show" @selected(old('status')=='no_show')>No Show</option>
          </select>
          @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-6">
          <label class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="edit_notes" rows="2">{{ old('notes') }}</textarea>
          @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Update
          </button>
          <button type="button" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  @endrole

</div>

{{-- Scripts --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

  // DataTable
  $('#appointmentsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: '{{ route('appointments.data') }}', type: 'GET' },

    createdRow: function(row, data, dataIndex) {
      $(row).css('cursor', 'pointer');
      $(row).on('click', function(e) {
        if ($(e.target).closest('.d-flex').length) return;
        window.location.href = '/appointments/' + data.id;
      });
    },

    columns: [
      { data: null, defaultContent: '', orderable: false, searchable: false },
      { data: 'appointment_id', name: 'appointment_id' },
      { data: 'patient_info', name: 'patient_info' },
      { data: 'doctor_info', name: 'doctor_info' },
      { data: 'appointment_date', name: 'appointment_date' },
      { data: 'appointment_time', name: 'appointment_time' },
      { data: 'token_badge', name: 'token_number' },
      { data: 'type_badge', name: 'type' },
      { data: 'status_badge', name: 'status' },
      { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    pageLength: 25,
    order: [[4, 'desc']],
    language: {
      processing: '<div class="spinner-border text-primary" role="status"></div>'
    }
  });

  // Auto load slots
  const doctorSelect = document.getElementById('doctorSelect');
  const dateInput    = document.getElementById('appointmentDate');
  const timeSlots    = document.getElementById('timeSlots');

  function loadSlots() {
    const doctorId = doctorSelect?.value;
    const date     = dateInput?.value;

    if (!doctorId || !date) return;

    fetch(`/appointments/slots?doctor_id=${doctorId}&date=${date}`)
      .then(res => res.json())
      .then(data => {
        timeSlots.innerHTML = '<option value="">Select Time</option>';
        if (data.slots.length === 0) {
          timeSlots.innerHTML = '<option value="">No slots available</option>';
          return;
        }
        data.slots.forEach(slot => {
          const opt = document.createElement('option');
          opt.value = slot.time;
          opt.textContent = slot.time + (slot.available ? '' : ' (Booked)');
          opt.disabled = !slot.available;
          timeSlots.appendChild(opt);
        });
      });
  }

  doctorSelect?.addEventListener('change', loadSlots);
  dateInput?.addEventListener('change', loadSlots);
});

function editAppointment(id) {
  fetch(`/appointments/${id}/json`)
    .then(res => res.json())
    .then(a => {
      const f = document.getElementById('editAppointmentForm');
      f.action = `/appointments/${id}`;
      f.querySelector('[name="_edit_appointment_id"]').value = id;
      f.querySelector('[name="patient_id"]').value        = a.patient_id || '';
      f.querySelector('[name="doctor_id"]').value         = a.doctor_id || '';
      f.querySelector('[name="appointment_date"]').value  = a.appointment_date || '';
      f.querySelector('[name="appointment_time"]').value  = a.appointment_time || '';
      f.querySelector('[name="type"]').value              = a.type || '';
      f.querySelector('[name="status"]').value            = a.status || '';
      f.querySelector('[name="notes"]').value             = a.notes || '';

      new bootstrap.Offcanvas(document.getElementById('offcanvasAppointmentEdit')).show();
    });
}

@if ($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
  @if(old('_edit_appointment_id'))
    document.getElementById('editAppointmentForm').action = '/appointments/{{ old('_edit_appointment_id') }}';
    new bootstrap.Offcanvas(document.getElementById('offcanvasAppointmentEdit')).show();
  @else
    new bootstrap.Offcanvas(document.getElementById('offcanvasAppointmentAdd')).show();
  @endif
});
</script>
@elseif($fromPatient)
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('offcanvasAppointmentAdd').querySelector('form');
  form.querySelector('[name="patient_id"]').value = '{{ $fromPatient->id }}';
  @if($fromType)
  form.querySelector('[name="type"]').value = '{{ $fromType }}';
  @endif
  new bootstrap.Offcanvas(document.getElementById('offcanvasAppointmentAdd')).show();
});
</script>
@endif

function deleteAppointment(id) {
  confirmDelete(`/appointments/${id}`, 'Appointment', function() {
    $('#appointmentsTable').DataTable().ajax.reload(null, false);
  });
}
</script>

@endsection