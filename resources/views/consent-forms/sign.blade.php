@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Sign Consent Form - HMS')

@section('page-style')
<style>
.signature-pad {
  border: 2px dashed var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-body-bg);
  cursor: crosshair;
  touch-action: none;
  width: 100%;
  height: 200px;
}
.signature-pad:hover {
  border-color: var(--bs-primary);
}
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
  <i class="icon-base ti tabler-check me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
  <div class="col-xl-8 col-lg-10">

    {{-- Consent Details --}}
    <div class="card mb-4">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="icon-base ti tabler-writing me-2 text-primary"></i>
          Consent Form — {{ $consentForm->consent_no }}
        </h5>
        <span class="badge bg-label-{{ $consentForm->status === 'signed' ? 'success' : 'warning' }} text-capitalize">
          {{ $consentForm->status }}
        </span>
      </div>
      <div class="card-body">
        <div class="row g-3 mb-4">
          @foreach([
            ['Patient', $consentForm->patient->name],
            ['MRN', $consentForm->patient->mrn ?? 'N/A'],
            ['Consent Type', str_replace('_',' ', $consentForm->consent_type)],
            ['Procedure', $consentForm->procedure_name ?? 'N/A'],
            ['Doctor', $consentForm->doctor_name ?? 'N/A'],
            ['Witness', $consentForm->witness_name ?? 'N/A'],
            ['Date', \Carbon\Carbon::parse($consentForm->consent_date)->format('d M Y')],
          ] as [$label, $value])
          <div class="col-md-6">
            <div class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted small">{{ $label }}</span>
              <span class="fw-medium small text-capitalize">{{ $value }}</span>
            </div>
          </div>
          @endforeach
        </div>

        <div class="p-3 rounded mb-4" style="background:var(--bs-body-bg);border:1px solid var(--bs-border-color);">
          <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:0.5px;">Description</p>
          <p class="mb-0" style="font-size:0.875rem;">{{ $consentForm->description }}</p>
        </div>

        @if($consentForm->status === 'signed')
        {{-- Already signed --}}
        <div class="alert alert-success d-flex align-items-center gap-2">
          <i class="icon-base ti tabler-circle-check" style="font-size:1.25rem;"></i>
          <div>
            <strong>This consent form has been signed.</strong>
            <a href="{{ route('consent-forms.print', $consentForm->id) }}" target="_blank" class="btn btn-sm btn-success ms-3">
              <i class="icon-base ti tabler-printer me-1"></i> Print
            </a>
          </div>
        </div>

        {{-- Show existing signatures --}}
        @if($consentForm->patient_signature)
        <div class="mb-4">
          <p class="text-muted small fw-semibold mb-2">Patient Signature:</p>
          <img src="{{ $consentForm->patient_signature }}" alt="Patient Signature"
            style="border:1px solid var(--bs-border-color);border-radius:8px;max-height:150px;background:#fff;" />
        </div>
        @endif

        @if($consentForm->witness_signature)
        <div class="mb-4">
          <p class="text-muted small fw-semibold mb-2">Witness Signature:</p>
          <img src="{{ $consentForm->witness_signature }}" alt="Witness Signature"
            style="border:1px solid var(--bs-border-color);border-radius:8px;max-height:150px;background:#fff;" />
        </div>
        @endif

        @else
        {{-- Signature Form --}}
        <form action="{{ route('consent-forms.signature', $consentForm->id) }}" method="POST" id="signatureForm">
          @csrf

          @error('patient_signature')
          <div class="alert alert-danger">{{ $message }}</div>
          @enderror

          <input type="hidden" name="patient_signature" id="patientSignatureData" />
          <input type="hidden" name="witness_signature" id="witnessSignatureData" />

          {{-- Patient Signature --}}
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0 fw-semibold">Patient Signature *</label>
              <button type="button" onclick="clearCanvas('patientCanvas')" class="btn btn-sm btn-label-secondary">
                <i class="icon-base ti tabler-eraser me-1"></i> Clear
              </button>
            </div>
            <canvas id="patientCanvas" class="signature-pad"></canvas>
            <small class="text-muted">Sign above using mouse or touch</small>
          </div>

          {{-- Witness Signature --}}
          <div class="mb-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0 fw-semibold">Witness Signature (Optional)</label>
              <button type="button" onclick="clearCanvas('witnessCanvas')" class="btn btn-sm btn-label-secondary">
                <i class="icon-base ti tabler-eraser me-1"></i> Clear
              </button>
            </div>
            <canvas id="witnessCanvas" class="signature-pad"></canvas>
            <small class="text-muted">Witness sign above</small>
          </div>

          <div class="d-flex gap-2">
            <button type="button" onclick="submitSignature()" class="btn btn-success">
              <i class="icon-base ti tabler-circle-check me-1"></i> Confirm & Sign
            </button>
            <a href="{{ route('consent-forms.index') }}" class="btn btn-label-secondary">
              <i class="icon-base ti tabler-arrow-left me-1"></i> Back
            </a>
            <button type="button" onclick="declineConsent()" class="btn btn-label-danger ms-auto">
              <i class="icon-base ti tabler-x me-1"></i> Decline
            </button>
          </div>

        </form>
        @endif

      </div>
    </div>

  </div>
</div>

<script>
// Signature pad setup
function setupCanvas(canvasId) {
  const canvas  = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx     = canvas.getContext('2d');
  canvas.width  = canvas.offsetWidth;
  canvas.height = canvas.offsetHeight;
  ctx.strokeStyle = '#000';
  ctx.lineWidth   = 2;
  ctx.lineCap     = 'round';

  let drawing = false;
  let lastX = 0, lastY = 0;

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    if (e.touches) {
      return {
        x: e.touches[0].clientX - rect.left,
        y: e.touches[0].clientY - rect.top
      };
    }
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
  }

  function startDraw(e) {
    drawing = true;
    const pos = getPos(e);
    lastX = pos.x;
    lastY = pos.y;
    e.preventDefault();
  }

  function draw(e) {
    if (!drawing) return;
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastX = pos.x;
    lastY = pos.y;
    e.preventDefault();
  }

  function stopDraw() { drawing = false; }

  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  canvas.addEventListener('mouseup', stopDraw);
  canvas.addEventListener('mouseleave', stopDraw);
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  canvas.addEventListener('touchend', stopDraw);
}

function clearCanvas(canvasId) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function isCanvasBlank(canvas) {
  const ctx       = canvas.getContext('2d');
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  return !imageData.data.some(channel => channel !== 0);
}

function submitSignature() {
  const patientCanvas = document.getElementById('patientCanvas');

  if (isCanvasBlank(patientCanvas)) {
    alert('Please provide patient signature before submitting.');
    return;
  }

  document.getElementById('patientSignatureData').value = patientCanvas.toDataURL('image/png');

  const witnessCanvas = document.getElementById('witnessCanvas');
  if (!isCanvasBlank(witnessCanvas)) {
    document.getElementById('witnessSignatureData').value = witnessCanvas.toDataURL('image/png');
  }

  document.getElementById('signatureForm').submit();
}

function declineConsent() {
  confirmAction('Decline Consent Form?', 'Are you sure you want to decline this consent form? This action cannot be undone.', function() {
    fetch(`/consent-forms/{{ $consentForm->id }}/status`, {
      method: 'PUT',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ status: 'declined' })
    }).then(() => window.location.href = '/consent-forms');
  });
}

document.addEventListener('DOMContentLoaded', function() {
  setupCanvas('patientCanvas');
  setupCanvas('witnessCanvas');
});

window.addEventListener('resize', function() {
  setupCanvas('patientCanvas');
  setupCanvas('witnessCanvas');
});
</script>

@endsection