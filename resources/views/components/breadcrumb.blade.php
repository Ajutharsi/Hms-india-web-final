{{--
    Shared breadcrumb component.

    Usage:
    <x-breadcrumb :items="[
        ['label' => 'Patients', 'url' => route('patients.index')],
        ['label' => $patient->name],
    ]" />

    The last item (or any item without a 'url' key) renders as the active,
    non-linked crumb.
--}}
@props(['items' => []])

@if (!empty($items))
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb breadcrumb-style1 mb-0">
    @foreach ($items as $index => $item)
      @if (!empty($item['url']) && $index !== count($items) - 1)
        <li class="breadcrumb-item">
          <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        </li>
      @else
        <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
      @endif
    @endforeach
  </ol>
</nav>
@endif
