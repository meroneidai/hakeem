@props([
    'show' => 'open',
    'close' => 'open = false',
    'title' => null,
    'maxWidth' => 'max-w-md',
])

<div
    x-cloak
    x-show="{{ $show }}"
    x-transition.opacity.duration.150ms
    class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
>
    <div
        class="absolute inset-0 bg-ink-900/45"
        @click="{{ $close }}"
        aria-hidden="true"
    ></div>

    <div
        x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
        x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        @click.stop
        {{ $attributes->class(['relative w-full rounded-t-3xl border border-ink-200 bg-white shadow-xl sm:rounded-3xl', $maxWidth]) }}
    >
        <div class="flex items-start justify-between gap-3 border-b border-ink-100 px-5 py-4">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base font-semibold text-ink-900">{{ $title }}</h2>
                @endif
                @isset($subtitle)
                    <div class="mt-1 text-sm text-ink-500">{{ $subtitle }}</div>
                @endisset
            </div>
            <button
                type="button"
                class="grid size-9 shrink-0 place-items-center rounded-full text-ink-400 hover:bg-ink-100 hover:text-ink-700"
                @click="{{ $close }}"
                aria-label="{{ __('common.close') }}"
            >
                <x-icon name="x-mark" class="size-5"/>
            </button>
        </div>

        <div class="px-5 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-ink-100 bg-ink-50/70 px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
