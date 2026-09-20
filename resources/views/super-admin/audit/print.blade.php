@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', $title . ' - Print - HMS')

@section('page-style')
<style>
  @media print {
    body { -webkit-print-color-adjust: exact; }
  }
  .card { break-inside: avoid; }
</style>
@endsection

@section('page-script')
<script>
  window.onload = function() { window.print(); }
</script>
@endsection

@section('content')
<div class="invoice-print p-12">

  <div class="d-flex justify-content-between flex-row mb-6">
    <div>
      <span class="fw-bold fs-4">{{ config('app.name', 'HMS') }}</span>
      <p class="mb-1 text-muted">Audit & Activity Centre</p>
    </div>
    <div class="text-end">
      <h4 class="mb-2 text-uppercase fw-bold">{{ $title }}</h4>
      <p class="mb-0">Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>
  </div>

  <hr class="mb-6" />

  @if(!empty($filters))
  <p class="text-muted mb-4"><strong>Filters:</strong>
    @foreach($filters as $k => $v)
      {{ ucwords(str_replace('_', ' ', $k)) }}: {{ $v }}@if(!$loop->last), @endif
    @endforeach
  </p>
  @endif

  <table class="table table-sm table-bordered">
    <thead><tr>@foreach($columns as $c)<th>{{ $c['title'] }}</th>@endforeach</tr></thead>
    <tbody>
      @forelse($rows as $row)
      <tr>@foreach($columns as $c)<td>{{ $row[$c['data']] ?? '—' }}</td>@endforeach</tr>
      @empty
      <tr><td colspan="{{ count($columns) }}" class="text-center text-muted">Not tracked yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  <p class="text-muted small mt-3">{{ $rows->count() }} record(s).</p>

</div>
@endsection
