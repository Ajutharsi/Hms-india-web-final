@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Feature Groups - HMS')

@section('content')

<x-breadcrumb :items="[['label' => 'Platform Dashboard', 'url' => route('dashboard')], ['label' => 'Feature Management', 'url' => route('super-admin.features.index')], ['label' => 'Feature Groups']]" />

<div class="hms-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-6">
  <div>
    <h4 class="mb-1">Feature Groups</h4>
    <p class="mb-0 text-body-secondary">Features organized by module category.</p>
  </div>
</div>

<div class="row g-4">
  @foreach($groups as $group)
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0">{{ $group['category'] }}</h6>
        <span class="badge bg-label-primary">{{ $group['enabled'] }}/{{ $group['total'] }} enabled</span>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          @foreach($group['modules'] as $module)
          <li class="d-flex justify-content-between align-items-center py-1">
            <a href="{{ route('super-admin.features.catalog', ['category' => $group['category']]) }}" class="text-reset">
              <i class="icon-base ti {{ $module['icon'] ?? 'tabler-puzzle' }} me-1"></i>{{ $module['name'] }}
            </a>
            @if($module['enabled'])
            <span class="badge bg-label-success">On</span>
            @else
            <span class="badge bg-label-danger">Off</span>
            @endif
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection
