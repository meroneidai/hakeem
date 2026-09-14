@props(['edit' => null, 'destroy' => null])

<div class="flex items-center justify-end gap-1">
    {{ $slot ?? '' }}

    @if ($edit)
        <a href="{{ $edit }}" class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50">
            <x-icon name="pencil" class="size-3.5"/>
            {{ __('common.edit') }}
        </a>
    @endif

    @if ($destroy)
        <form method="POST" action="{{ $destroy }}" onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center rounded-lg px-2 py-1.5 text-xs font-medium text-danger-500 hover:bg-danger-50">
                {{ __('common.delete') }}
            </button>
        </form>
    @endif
</div>
