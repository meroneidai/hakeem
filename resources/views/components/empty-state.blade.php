@props(['message' => null, 'colspan' => null])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="px-4 py-12 text-center text-sm text-ink-400">
            {{ $message ?? __('common.no_results') }}
            {{ $slot }}
        </td>
    </tr>
@else
    <div class="px-4 py-12 text-center text-sm text-ink-400">
        {{ $message ?? __('common.no_results') }}
        {{ $slot }}
    </div>
@endif
