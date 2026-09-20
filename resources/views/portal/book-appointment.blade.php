@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Book Appointment - HMS')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0"><i class="icon-base ti tabler-calendar-plus me-2 text-primary"></i>Book an Appointment</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('portal.store-appointment') }}" method="POST" id="bookForm">
          @csrf
          <div class="mb-4">
            <label class="form-label">Doctor *</label>
            <select class="form-select @error('doctor_id') is-invalid @enderror" name="doctor_id" id="doctor_id" required>
              <option value="">Select Doctor</option>
              @foreach ($doctors as $d)
              <option value="{{ $d->id }}" data-photo="{{ $d->photo_url }}" @selected(old('doctor_id')==$d->id)>{{ $d->name }} — {{ $d->specialization }}</option>
              @endforeach
            </select>
            @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Appointment Type *</label>
            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
              <option value="op" @selected(old('type','op')=='op')>OP Visit</option>
              <option value="consult" @selected(old('type')=='consult')>Consultation</option>
              <option value="followup" @selected(old('type')=='followup')>Follow-up</option>
            </select>
            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Date *</label>
            <input type="date" class="form-control @error('appointment_date') is-invalid @enderror" name="appointment_date" id="appointment_date"
              min="{{ now()->toDateString() }}" value="{{ old('appointment_date') }}" required />
            @error('appointment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="mb-4">
            <label class="form-label">Available Time Slots *</label>
            <div id="slotsWrap" class="d-flex flex-wrap gap-2">
              <p class="text-muted mb-0">Select a doctor and date to see available slots.</p>
            </div>
            <input type="hidden" name="appointment_time" id="appointment_time" required />
            @error('appointment_time') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
          <div class="mb-6">
            <label class="form-label">Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2" placeholder="Describe your concern briefly...">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-calendar-check me-1"></i> Book Appointment
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
function loadSlots() {
  const doctorId = document.getElementById('doctor_id').value;
  const date = document.getElementById('appointment_date').value;
  const wrap = document.getElementById('slotsWrap');
  document.getElementById('appointment_time').value = '';

  if (!doctorId || !date) {
    wrap.innerHTML = '<p class="text-muted mb-0">Select a doctor and date to see available slots.</p>';
    return;
  }

  wrap.innerHTML = '<p class="text-muted mb-0">Loading slots...</p>';

  fetch(`{{ route('portal.slots') }}?doctor_id=${doctorId}&date=${date}`)
    .then(res => res.json())
    .then(res => {
      if (!res.slots || res.slots.length === 0) {
        wrap.innerHTML = '<p class="text-muted mb-0">No slots available for this date.</p>';
        return;
      }
      wrap.innerHTML = '';
      res.slots.forEach(s => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm ' + (s.available ? 'btn-label-primary slot-btn' : 'btn-label-secondary disabled');
        btn.textContent = s.time;
        btn.disabled = !s.available;
        if (s.available) {
          btn.addEventListener('click', function () {
            document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('btn-primary'));
            document.querySelectorAll('.slot-btn').forEach(b => b.classList.add('btn-label-primary'));
            btn.classList.remove('btn-label-primary');
            btn.classList.add('btn-primary');
            document.getElementById('appointment_time').value = s.time;
          });
        }
        wrap.appendChild(btn);
      });
    });
}

document.getElementById('doctor_id').addEventListener('change', loadSlots);
document.getElementById('appointment_date').addEventListener('change', loadSlots);

document.getElementById('bookForm').addEventListener('submit', function (e) {
  if (!document.getElementById('appointment_time').value) {
    e.preventDefault();
    alert('Please select an available time slot.');
  }
});
</script>
@endsection

@endsection
