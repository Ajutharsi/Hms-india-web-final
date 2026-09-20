{{--
  Shared DataTables row-action buttons.
  Usage from a controller:
    ->addColumn('actions', fn($row) => view('_partials.dt-actions', [
        'buttons' => [
            ['type' => 'link',   'url' => route('...', $row), 'icon' => 'tabler-eye',   'class' => 'btn-label-info',    'title' => 'View'],
            ['type' => 'button', 'onclick' => "editX({$row->id})", 'icon' => 'tabler-edit',  'class' => 'btn-label-warning', 'title' => 'Edit', 'can' => 'edit patients'],
            ['type' => 'button', 'onclick' => "deleteX({$row->id})", 'icon' => 'tabler-trash', 'class' => 'btn-label-danger',  'title' => 'Delete', 'can' => 'delete patients'],
        ],
    ])->render())
  A button with a 'can' key is only rendered when the user has that permission.
--}}
<div class="d-flex gap-2">
  @foreach ($buttons as $btn)
    @continue(isset($btn['can']) && !auth()->user()->can($btn['can']))
    @if (($btn['type'] ?? 'link') === 'link')
      <a href="{{ $btn['url'] }}" @if(!empty($btn['target'])) target="{{ $btn['target'] }}" @endif
         class="btn btn-sm btn-icon {{ $btn['class'] ?? 'btn-label-secondary' }}" title="{{ $btn['title'] ?? '' }}">
        <i class="icon-base ti {{ $btn['icon'] }}"></i>
      </a>
    @else
      <button type="button" onclick="{{ $btn['onclick'] }}"
              class="btn btn-sm btn-icon {{ $btn['class'] ?? 'btn-label-secondary' }}" title="{{ $btn['title'] ?? '' }}">
        <i class="icon-base ti {{ $btn['icon'] }}"></i>
      </button>
    @endif
  @endforeach
</div>
