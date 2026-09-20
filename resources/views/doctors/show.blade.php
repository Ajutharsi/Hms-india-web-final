@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Doctor Details - HMS')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/page-user-view.scss')
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">

  {{-- Left Sidebar --}}
  <div class="col-xl-4 col-lg-5 order-1 order-md-0">

    {{-- Doctor Card --}}
    <div class="card mb-4">
      <div class="card-body pt-8">

        <div class="d-flex flex-column align-items-center text-center mb-4">
          <div class="avatar avatar-xl mb-3">
            <img src="{{ $doctor->photo_url }}" alt="{{ $doctor->name }}"
              class="rounded-circle" style="width:72px;height:72px;object-fit:cover;"
              onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
          </div>
          <h5 class="mb-1">Dr. {{ $doctor->name }}</h5>
          <span class="text-muted small mb-2">{{ $doctor->doctor_id }}</span>
          <div class="d-flex gap-1 flex-wrap justify-content-center">
            <span class="badge bg-label-primary">{{ $doctor->specialization }}</span>
            <span class="badge bg-label-{{ $doctor->status === 'active' ? 'success' : 'danger' }}">
              {{ ucfirst($doctor->status) }}
            </span>
          </div>
        </div>

        <div class="row text-center mb-4 mx-0 p-3 rounded" style="background:rgba(var(--bs-primary-rgb),0.04);">
          <div class="col-6 border-end">
            <i class="icon-base ti tabler-calendar text-primary mb-1" style="font-size:1.25rem;"></i>
            <h5 class="mb-0">{{ \App\Models\Appointment::where('doctor_id', $doctor->id)->count() }}</h5>
            <small class="text-muted">Appointments</small>
          </div>
          <div class="col-6">
            <i class="icon-base ti tabler-calendar-event text-warning mb-1" style="font-size:1.25rem;"></i>
            <h5 class="mb-0">{{ \App\Models\Appointment::where('doctor_id', $doctor->id)->whereDate('appointment_date', today())->count() }}</h5>
            <small class="text-muted">Today</small>
          </div>
        </div>

        <div class="section-title" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--bs-secondary-color);padding:0.5rem 0;border-bottom:2px solid var(--bs-border-color);margin-bottom:0.75rem;">
          Doctor Details
        </div>
        <div class="mb-4">
          @foreach([
            ['label' => 'Doctor ID',        'value' => $doctor->doctor_id],
            ['label' => 'Specialization',   'value' => $doctor->specialization],
            ['label' => 'Department',       'value' => $doctor->department ?? 'N/A'],
            ['label' => 'Qualification',    'value' => $doctor->qualification ?? 'N/A'],
            ['label' => 'Reg. Number',      'value' => $doctor->registration_number ?? 'N/A'],
            ['label' => 'Phone',            'value' => $doctor->phone ?? 'N/A'],
            ['label' => 'Email',            'value' => $doctor->email ?? 'N/A'],
            ['label' => 'Consultation Fee', 'value' => 'LKR ' . number_format($doctor->consultation_fee ?? 0, 2)],
          ] as $detail)
          <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:0.5rem 0;border-bottom:1px dashed var(--bs-border-color);">
            <span style="font-size:0.8rem;font-weight:600;color:var(--bs-secondary-color);text-transform:uppercase;letter-spacing:0.3px;min-width:120px;">
              {{ $detail['label'] }}
            </span>
            <span style="font-size:0.875rem;color:var(--bs-heading-color);font-weight:500;text-align:right;">
              {{ $detail['value'] }}
            </span>
          </div>
          @endforeach
        </div>

        <div class="d-flex justify-content-center gap-2">
          <a href="{{ route('doctors.index') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ti tabler-arrow-left me-1"></i> Back
          </a>
          @can('edit doctors')
          <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDoctorEdit">
            <i class="icon-base ti tabler-edit me-1"></i> Edit
          </button>
          @endcan
        </div>

      </div>
    </div>

    {{-- Documents Card --}}
    <div class="card mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-file-text me-2 text-primary"></i>Documents
        </h6>
      </div>
      <div class="card-body py-3">
        @if($doctor->license_file)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-license text-success icon-lg"></i>
            <div>
              <p class="mb-0 fw-medium small">Medical License</p>
              <small class="text-muted">{{ $doctor->license_file_name }}</small>
            </div>
          </div>
          <a href="{{ route('doctors.download', [$doctor->id, 'license']) }}"
            class="btn btn-sm btn-icon btn-label-success">
            <i class="icon-base ti tabler-download"></i>
          </a>
        </div>
        @endif

        @if($doctor->certificate_file)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-certificate text-warning icon-lg"></i>
            <div>
              <p class="mb-0 fw-medium small">Certificate</p>
              <small class="text-muted">{{ $doctor->certificate_file_name }}</small>
            </div>
          </div>
          <a href="{{ route('doctors.download', [$doctor->id, 'certificate']) }}"
            class="btn btn-sm btn-icon btn-label-warning">
            <i class="icon-base ti tabler-download"></i>
          </a>
        </div>
        @endif

        @if($doctor->id_file)
        <div class="d-flex justify-content-between align-items-center py-2">
          <div class="d-flex align-items-center gap-2">
            <i class="icon-base ti tabler-id text-info icon-lg"></i>
            <div>
              <p class="mb-0 fw-medium small">ID Document</p>
              <small class="text-muted">{{ $doctor->id_file_name }}</small>
            </div>
          </div>
          <a href="{{ route('doctors.download', [$doctor->id, 'id']) }}"
            class="btn btn-sm btn-icon btn-label-info">
            <i class="icon-base ti tabler-download"></i>
          </a>
        </div>
        @endif

        @if(!$doctor->license_file && !$doctor->certificate_file && !$doctor->id_file)
        <p class="text-muted text-center mb-0 small py-2">No documents uploaded.</p>
        @endif
      </div>
    </div>

  </div>

  {{-- Right Content --}}
  <div class="col-xl-8 col-lg-7 order-0 order-md-1">

    {{-- Schedule Card --}}
    <div class="card mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-clock me-2 text-warning"></i>Weekly Schedule
        </h6>
      </div>
      <div class="card-body">

        {{-- Add Schedule Form --}}
        @can('edit doctors')
        <form action="{{ route('doctors.schedules.store', $doctor->id) }}" method="POST" class="mb-4">
          @csrf
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">Day *</label>
              <select class="form-select" name="day" required>
                <option value="">Select</option>
                @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                <option value="{{ $day }}">{{ ucfirst($day) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Start Time *</label>
              <input type="time" class="form-control" name="start_time" required />
            </div>
            <div class="col-md-2">
              <label class="form-label">End Time *</label>
              <input type="time" class="form-control" name="end_time" required />
            </div>
            <div class="col-md-2">
              <label class="form-label">Slot (mins) *</label>
              <select class="form-select" name="slot_duration" required>
                <option value="10">10 mins</option>
                <option value="15">15 mins</option>
                <option value="20">20 mins</option>
                <option value="30" selected>30 mins</option>
                <option value="45">45 mins</option>
                <option value="60">60 mins</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Available</label>
              <div class="form-check mt-2">
                <input type="checkbox" class="form-check-input" name="is_available" checked />
                <label class="form-check-label">Yes</label>
              </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">
                <i class="icon-base ti tabler-plus me-1"></i> Add
              </button>
            </div>
          </div>
        </form>
        @endcan

        {{-- Schedule Table --}}
        @if($doctor->schedules->count() > 0)
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Day</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Slot Duration</th>
                <th>Available</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($doctor->schedules->sortBy('day') as $schedule)
              <tr>
                <td class="fw-medium text-capitalize">{{ $schedule->day }}</td>
                <td>{{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}</td>
                <td>{{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}</td>
                <td>{{ $schedule->slot_duration }} mins</td>
                <td>
                  <span class="badge bg-label-{{ $schedule->is_available ? 'success' : 'danger' }}">
                    {{ $schedule->is_available ? 'Yes' : 'No' }}
                  </span>
                </td>
                <td>
                  @can('edit doctors')
                  <form action="{{ route('doctors.schedules.destroy', $schedule->id) }}" method="POST"
                    onsubmit="event.preventDefault(); confirmFormSubmit(this, 'Delete Schedule?', 'Are you sure you want to delete this schedule? This action cannot be undone.'); return false;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger">
                      <i class="icon-base ti tabler-trash"></i>
                    </button>
                  </form>
                  @endcan
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <p class="text-muted text-center mb-0 py-4">No schedule set. Add schedule above.</p>
        @endif

      </div>
    </div>

    {{-- Recent Appointments --}}
    <div class="card mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
          <i class="icon-base ti tabler-calendar me-2 text-primary"></i>Recent Appointments
        </h6>
        <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-label-primary">
          View All
        </a>
      </div>
      <div class="card-body p-0">
        @php
          $appointments = \App\Models\Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->latest()
            ->take(5)
            ->get();
        @endphp
        @forelse($appointments as $appt)
        <div class="d-flex align-items-center p-3 border-bottom">
          <div class="avatar me-3">
            <span class="avatar-initial rounded-circle bg-label-primary">
              {{ strtoupper(substr($appt->patient->name ?? 'N', 0, 1)) }}
            </span>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0 small fw-medium">{{ $appt->patient->name ?? 'N/A' }}</h6>
            <small class="text-muted">
              {{ \Carbon\Carbon::parse($appt->appointment_date)->format('d M Y') }} —
              {{ \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') }}
            </small>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-label-{{ $appt->type === 'op' ? 'info' : 'danger' }}">
              {{ strtoupper($appt->type) }}
            </span>
            <span class="badge bg-label-{{ $appt->status === 'completed' ? 'success' : ($appt->status === 'scheduled' ? 'warning' : 'secondary') }}">
              {{ ucfirst($appt->status) }}
            </span>
          </div>
        </div>
        @empty
        <p class="text-muted text-center py-4 mb-0">No appointments found.</p>
        @endforelse
      </div>
    </div>

  </div>

</div>

{{-- Edit Offcanvas --}}
@can('edit doctors')
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasDoctorEdit" style="width:500px;max-width:92vw;">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Edit Doctor</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-6">
    <form action="{{ route('doctors.update', $doctor->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="mb-4 text-center">
        <img id="show_edit_photo_preview" src="{{ $doctor->photo_url }}" alt="Photo preview"
          class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;border:2px solid var(--bs-border-color);"
          onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
        <label class="form-label d-block">Doctor Photo</label>
        <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" accept=".jpg,.jpeg,.png,.webp"
          onchange="previewDoctorPhoto(this, 'show_edit_photo_preview')" />
        @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-muted">JPG, JPEG, PNG or WEBP. Max 2MB. Leave empty to keep current photo.</small>
      </div>
      <div class="mb-4">
        <label class="form-label">Full Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $doctor->name) }}" required />
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Specialization *</label>
        <select class="form-select @error('specialization') is-invalid @enderror" name="specialization" required>
          @foreach(['Cardiology','Neurology','Orthopedics','Pediatrics','Ophthalmology','Dentistry','Dermatology','Gynecology','General Medicine','Surgery','ENT','Psychiatry','Radiology','Oncology','Urology'] as $spec)
          <option value="{{ $spec }}" {{ old('specialization', $doctor->specialization) === $spec ? 'selected' : '' }}>{{ $spec }}</option>
          @endforeach
        </select>
        @error('specialization') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Department</label>
        <input type="text" class="form-control" name="department" value="{{ old('department', $doctor->department) }}" />
      </div>
      <div class="mb-4">
        <label class="form-label">Qualification</label>
        <input type="text" class="form-control" name="qualification" value="{{ old('qualification', $doctor->qualification) }}" />
      </div>
      <div class="mb-4">
        <label class="form-label">Registration Number</label>
        <input type="text" class="form-control @error('registration_number') is-invalid @enderror" name="registration_number" value="{{ old('registration_number', $doctor->registration_number) }}" />
        @error('registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Phone</label>
        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $doctor->phone) }}" maxlength="15" pattern="[0-9+\-\s\(\)]+" oninput="this.value=this.value.replace(/[^0-9+\-\s\(\)]/g,'')" />
        <small class="text-muted">Max 15 digits. Numbers, +, -, spaces only.</small>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $doctor->email) }}" />
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Consultation Fee (LKR)</label>
        <input type="number" class="form-control" name="consultation_fee" value="{{ old('consultation_fee', $doctor->consultation_fee) }}" step="0.01" min="0" />
      </div>
      <div class="mb-4">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="active" {{ old('status', $doctor->status) === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ old('status', $doctor->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </div>

      {{-- Documents --}}
      <div class="border rounded p-4 mb-4">
        <h6 class="mb-3"><i class="icon-base ti tabler-file-text me-2 text-primary"></i>Documents</h6>

        <div class="mb-3">
          <label class="form-label"><i class="icon-base ti tabler-license me-1 text-success"></i> Medical License</label>
          @if($doctor->license_file)
          <div class="d-flex align-items-center gap-2 p-2 border rounded mb-2">
            <i class="icon-base ti tabler-file text-success"></i>
            <span class="small">{{ $doctor->license_file_name }}</span>
            <a href="{{ route('doctors.download', [$doctor->id, 'license']) }}" class="btn btn-sm btn-label-success ms-auto py-0 px-2">
              <i class="icon-base ti tabler-download icon-sm"></i>
            </a>
          </div>
          @endif
          <input type="file" class="form-control" name="license_file" accept=".pdf,.jpg,.jpeg,.png" />
          <small class="text-muted">Upload new file to replace existing</small>
        </div>

        <div class="mb-3">
          <label class="form-label"><i class="icon-base ti tabler-certificate me-1 text-warning"></i> Certificate</label>
          @if($doctor->certificate_file)
          <div class="d-flex align-items-center gap-2 p-2 border rounded mb-2">
            <i class="icon-base ti tabler-file text-warning"></i>
            <span class="small">{{ $doctor->certificate_file_name }}</span>
            <a href="{{ route('doctors.download', [$doctor->id, 'certificate']) }}" class="btn btn-sm btn-label-warning ms-auto py-0 px-2">
              <i class="icon-base ti tabler-download icon-sm"></i>
            </a>
          </div>
          @endif
          <input type="file" class="form-control" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png" />
          <small class="text-muted">Upload new file to replace existing</small>
        </div>

        <div class="mb-0">
          <label class="form-label"><i class="icon-base ti tabler-id me-1 text-info"></i> ID Document</label>
          @if($doctor->id_file)
          <div class="d-flex align-items-center gap-2 p-2 border rounded mb-2">
            <i class="icon-base ti tabler-file text-info"></i>
            <span class="small">{{ $doctor->id_file_name }}</span>
            <a href="{{ route('doctors.download', [$doctor->id, 'id']) }}" class="btn btn-sm btn-label-info ms-auto py-0 px-2">
              <i class="icon-base ti tabler-download icon-sm"></i>
            </a>
          </div>
          @endif
          <input type="file" class="form-control" name="id_file" accept=".pdf,.jpg,.jpeg,.png" />
          <small class="text-muted">Upload new file to replace existing</small>
        </div>
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
@endcan

@endsection

@section('page-script')
<script>
function previewDoctorPhoto(input, previewId) {
  const img = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      img.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    img.src = '{{ asset('images/defaults/doctor.png') }}';
  }
}

@if ($errors->has('specialization') || $errors->has('registration_number') || $errors->has('photo'))
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Offcanvas(document.getElementById('offcanvasDoctorEdit')).show();
});
@endif
</script>
@endsection