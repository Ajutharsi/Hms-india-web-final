@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('page-style')
@vite('resources/assets/vendor/scss/pages/app-invoice-print.scss')
<style>
  .signature-img {
    max-height: 80px;
    border-bottom: 2px solid #333;
  }
  .sign-box {
    border-top: 2px solid #333;
    min-height: 80px;
    width: 200px;
    display: inline-block;
    text-align: center;
  }
  @media print {
    body { -webkit-print-color-adjust: exact; }
  }
</style>
@endsection

@section('page-script')
<script>
  window.onload = function() { window.print(); }
</script>
@endsection

@section('content')
<div class="invoice-print p-12">

  {{-- Header --}}
  <div class="d-flex justify-content-between flex-row mb-6">
    <div>
      @if($hospitalSettings->logo_url)
      <img src="{{ $hospitalSettings->logo_url }}"
        alt="Logo" style="height:60px;object-fit:contain;" class="mb-2 d-block" />
      @endif
      <span class="fw-bold fs-4">{{ $hospitalSettings->hospital_name }}</span>
      @if($hospitalSettings->address)
      <p class="mb-1">{{ $hospitalSettings->address }}{{ $hospitalSettings->city ? ', ' . $hospitalSettings->city : '' }}</p>
      @endif
      <p class="mb-0">
        @if($hospitalSettings->phone) {{ $hospitalSettings->phone }} @endif
        @if($hospitalSettings->phone && $hospitalSettings->email) | @endif
        @if($hospitalSettings->email) {{ $hospitalSettings->email }} @endif
      </p>
    </div>
    <div class="text-end">
      <h4 class="mb-2 text-uppercase fw-bold">Patient Consent Form</h4>
      <p class="mb-1 fw-medium">{{ $consentForm->consent_no }}</p>
      <p class="mb-1 text-capitalize">
        Type: <strong>{{ str_replace('_', ' ', $consentForm->consent_type) }}</strong>
      </p>
      <p class="mb-0">Date: {{ \Carbon\Carbon::parse($consentForm->consent_date)->format('d M Y') }}</p>
    </div>
  </div>

  <hr class="mb-6" />

  {{-- Patient Info --}}
  <div class="row mb-6">
    <div class="col-6">
      <h6 class="fw-bold mb-3">Patient Information:</h6>
      <table class="table table-sm table-borderless">
        <tr>
          <td class="text-muted" style="width:140px;">Patient Name</td>
          <td><strong>{{ $consentForm->patient->name }}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">MRN</td>
          <td>{{ $consentForm->patient->mrn ?? 'N/A' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Date of Birth</td>
          <td>{{ $consentForm->patient->dob ? \Carbon\Carbon::parse($consentForm->patient->dob)->format('d M Y') : 'N/A' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Gender</td>
          <td class="text-capitalize">{{ $consentForm->patient->gender ?? 'N/A' }}</td>
        </tr>
      </table>
    </div>
    <div class="col-6">
      <h6 class="fw-bold mb-3">Procedure Details:</h6>
      <table class="table table-sm table-borderless">
        <tr>
          <td class="text-muted" style="width:140px;">Procedure</td>
          <td><strong>{{ $consentForm->procedure_name ?? 'N/A' }}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">Doctor</td>
          <td>{{ $consentForm->doctor_name ?? 'N/A' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Witness</td>
          <td>{{ $consentForm->witness_name ?? 'N/A' }}</td>
        </tr>
        <tr>
          <td class="text-muted">Status</td>
          <td class="text-capitalize"><strong>{{ $consentForm->status }}</strong></td>
        </tr>
      </table>
    </div>
  </div>

  {{-- Description --}}
  <div class="mb-6 p-4 border rounded">
    <h6 class="fw-bold mb-3">Consent Description:</h6>
    <p class="mb-0" style="line-height:1.8;">{{ $consentForm->description }}</p>
  </div>

  {{-- Declaration --}}
  <div class="mb-6 p-4" style="background:#f8f9fa;border-radius:8px;">
    <h6 class="fw-bold mb-3">Declaration:</h6>
    <p class="mb-0" style="line-height:1.8;font-size:0.9rem;">
      I, <strong>{{ $consentForm->patient->name }}</strong>, hereby voluntarily consent to the
      <strong>{{ str_replace('_', ' ', $consentForm->consent_type) }}</strong>
      @if($consentForm->procedure_name)
      procedure — <strong>{{ $consentForm->procedure_name }}</strong>
      @endif
      as described above. I confirm that the nature, purpose, risks, and alternatives of the
      procedure have been explained to me in a language I understand. I have had the opportunity
      to ask questions and all my questions have been answered to my satisfaction.
    </p>
  </div>

  {{-- Signatures --}}
  <div class="row mt-6">
    <div class="col-6 text-center">
      <div class="mb-2">
        @if($consentForm->patient_signature)
        <img src="{{ $consentForm->patient_signature }}" alt="Patient Signature" class="signature-img d-block mx-auto" />
        @else
        <div class="sign-box mx-auto"></div>
        @endif
      </div>
      <p class="mb-0 fw-semibold">{{ $consentForm->patient->name }}</p>
      <small class="text-muted">Patient Signature</small>
    </div>
    <div class="col-6 text-center">
      <div class="mb-2">
        @if($consentForm->witness_signature)
        <img src="{{ $consentForm->witness_signature }}" alt="Witness Signature" class="signature-img d-block mx-auto" />
        @else
        <div class="sign-box mx-auto"></div>
        @endif
      </div>
      <p class="mb-0 fw-semibold">{{ $consentForm->witness_name ?? '________________________' }}</p>
      <small class="text-muted">Witness Signature</small>
    </div>
  </div>

  <hr class="mt-6 mb-4" />

  {{-- Footer --}}
  <div class="d-flex justify-content-between align-items-end">
    <div>
      @if($hospitalSettings->footer_note)
      <p class="mb-1 small text-muted">{{ $hospitalSettings->footer_note }}</p>
      @endif
      <p class="mb-0 small text-muted">
        Generated by {{ $hospitalSettings->hospital_name }} — {{ now()->format('d M Y H:i') }}
      </p>
    </div>
    <div class="text-center">
      <div style="border-top:2px solid #333;width:180px;margin-bottom:8px;"></div>
      <p class="mb-0 fw-medium small">Authorized Signature</p>
      <small class="text-muted">{{ $consentForm->doctor_name ?? 'Doctor' }}</small>
    </div>
  </div>

</div>
@endsection