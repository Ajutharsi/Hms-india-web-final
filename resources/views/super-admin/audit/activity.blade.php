@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Activity Log - Audit Centre - HMS')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-style')
<style>
.timeline-item { display: flex; gap: 1rem; padding: 0.9rem 0; border-left: 2px solid var(--bs-border-color); margin-left: 14px; padding-left: 1.5rem; position: relative; }
.timeline-item:before { content: ''; position: absolute; left: -7px; top: 1.1rem; width: 12px; height: 12px; border-radius: 50%; background: var(--bs-primary); }
.timeline-item.failure:before { background: var(--bs-danger); }
.timeline-item:last-child { border-left-color: transparent; }
.not-tracked { color: var(--bs-secondary-color); font-style: italic; font-size: 0.8125rem; }
</style>
@endsection

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Audit & Activity Centre', 'url' => route('super-admin.audit.index')], ['label' => 'Activity Log']]" />

@php
$logViewData = [
  'title' => 'Activity Log',
  'description' => 'Every logged action across the platform — every user, every module.',
  'dataUrl' => route('super-admin.audit.activity.data'),
  'csvUrl' => route('super-admin.audit.export-csv', 'activity'),
  'printUrl' => route('super-admin.audit.print', 'activity'),
];
@endphp

@include('super-admin.audit._filterable-log', $logViewData)

@endsection

@include('super-admin.audit._filterable-log-script', $logViewData)
