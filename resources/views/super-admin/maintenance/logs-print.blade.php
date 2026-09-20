@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Application Logs - Print - HMS')

@section('page-style')
<style>
  @media print { body { -webkit-print-color-adjust: exact; } }
  .card { break-inside: avoid; }
  .log-message { white-space: pre-wrap; font-family: monospace; font-size: 0.75rem; }
</style>
@endsection

@section('page-script')
<script>window.onload = function() { window.print(); }</script>
@endsection

@section('content')
<div class="invoice-print p-12">
  <div class="d-flex justify-content-between flex-row mb-6">
    <div>
      <span class="fw-bold fs-4">{{ config('app.name', 'HMS') }}</span>
      <p class="mb-1 text-muted">Platform Maintenance — Application Logs</p>
    </div>
    <div class="text-end">
      <p class="mb-0">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>
  </div>
  <hr class="mb-6" />
  <table class="table table-sm table-bordered">
    <thead><tr><th>Timestamp</th><th>Level</th><th>Channel</th><th>Message</th></tr></thead>
    <tbody>
      @forelse($entries as $e)
      <tr>
        <td>{{ $e['timestamp'] }}</td>
        <td>{{ $e['level'] }}</td>
        <td>{{ $e['channel'] }}</td>
        <td class="log-message">{{ $e['message'] }}</td>
      </tr>
      @empty
      <tr><td colspan="4" class="text-center text-muted">No log entries match.</td></tr>
      @endforelse
    </tbody>
  </table>
  <p class="text-muted small mt-3">{{ count($entries) }} entr{{ count($entries) === 1 ? 'y' : 'ies' }}.</p>
</div>
@endsection
