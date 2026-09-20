{{--
  Read-only patient handover card — used by show.blade.php and print.blade.php.
  Expects $card = a ShiftHandoverPatient with `patient` and `admission.bed` eager-loaded.
  Name/photo/MRN/bed/ward/admission-date/vitals are read live from those relationships,
  never duplicated onto the handover row itself.
--}}
@php
$conditionColor = ['stable' => 'success', 'improving' => 'info', 'critical' => 'danger', 'observation' => 'warning'][$card->condition] ?? 'secondary';
$medColor       = ['completed' => 'success', 'pending' => 'warning', 'missed' => 'danger'][$card->medication_status] ?? 'secondary';
$vitals         = $card->latestVitals();
@endphp
<div class="card mb-4 {{ $card->is_high_priority ? 'border-danger' : '' }}">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div class="d-flex align-items-center gap-3">
        <img src="{{ $card->patient->photo_url }}" alt="{{ $card->patient->name }}"
          class="rounded-circle" style="width:52px;height:52px;object-fit:cover;"
          onerror="this.onerror=null;this.src='{{ asset('images/defaults/patient.png') }}';" />
        <div>
          <a href="{{ route('patients.show', $card->patient_id) }}" class="fw-semibold text-body">{{ $card->patient->name }}</a>
          <div class="text-muted small">{{ $card->patient->mrn }} &middot; Bed {{ $card->admission->bed->bed_no ?? 'N/A' }}</div>
          <div class="text-muted small">Admitted {{ $card->admission?->admission_date ? \Carbon\Carbon::parse($card->admission->admission_date)->format('d M Y') : 'N/A' }}</div>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @if($card->is_high_priority)
        <span class="badge bg-danger"><i class="icon-base ti tabler-alert-triangle me-1"></i>High Priority</span>
        @endif
        <span class="badge bg-label-{{ $conditionColor }} text-capitalize">{{ $card->condition }}</span>
        <span class="badge bg-label-{{ $medColor }} text-capitalize">Meds: {{ $card->medication_status }}</span>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="text-muted small fw-semibold text-uppercase mb-2">Latest Vitals</div>
          @if($vitals)
          <div class="row g-2 small">
            <div class="col-6"><span class="text-muted">BP:</span> {{ $vitals->blood_pressure ?? '—' }}</div>
            <div class="col-6"><span class="text-muted">Pulse:</span> {{ $vitals->pulse_rate ?? '—' }}</div>
            <div class="col-6"><span class="text-muted">Temp:</span> {{ $vitals->temperature ?? '—' }}&deg;C</div>
            <div class="col-6"><span class="text-muted">RR:</span> {{ $vitals->respiratory_rate ?? '—' }}</div>
            <div class="col-6"><span class="text-muted">SpO2:</span> {{ $vitals->spo2 ?? '—' }}%</div>
            <div class="col-12 text-muted" style="font-size:0.7rem;">as of {{ $vitals->recorded_at?->format('d M h:i A') }}</div>
          </div>
          @else
          <div class="text-muted small">No vitals recorded</div>
          @endif
        </div>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <div class="text-muted small fw-semibold text-uppercase mb-2">IV Fluids</div>
          @if($card->iv_fluid_status)
          <span class="badge bg-label-{{ $card->iv_fluid_status === 'running' ? 'info' : 'secondary' }} text-capitalize mb-2">{{ $card->iv_fluid_status }}</span>
          @if($card->iv_next_change_time)
          <div class="small text-muted">Next change: {{ $card->iv_next_change_time->format('d M h:i A') }}</div>
          @endif
          @else
          <div class="text-muted small">Not applicable</div>
          @endif
        </div>
      </div>
    </div>

    @foreach([
      ['Doctor Instructions', $card->doctor_instructions, 'tabler-stethoscope'],
      ['Pending Procedures', $card->pending_procedures, 'tabler-clipboard-list'],
      ['Pending Lab Reports', $card->pending_lab_reports, 'tabler-flask'],
      ['Pending Imaging', $card->pending_imaging, 'tabler-radioactive'],
      ['Special Notes', $card->special_notes, 'tabler-notes'],
    ] as [$label, $value, $icon])
    @if($value)
    <div class="d-flex gap-2 mb-2">
      <i class="icon-base ti {{ $icon }} text-muted mt-1" style="font-size:0.9rem;"></i>
      <div><span class="fw-medium small">{{ $label }}:</span> <span class="small">{{ $value }}</span></div>
    </div>
    @endif
    @endforeach
  </div>
</div>
