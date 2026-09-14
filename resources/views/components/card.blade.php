@props(['title' => null, 'subtitle' => null, 'padded' => true])

<section {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @if ($title || $subtitle)
        <header class="border-b border-ink-200 bg-ink-50/60 px-5 py-4">
            @if ($title)
                <h2 class="text-base font-semibold text-ink-900">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
            @endif
        </header>
    @endif

    <div class="{{ $padded ? 'p-5' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="flex items-center justify-end gap-3 border-t border-ink-200 bg-ink-50/60 px-5 py-4">
            {{ $footer }}
        </footer>
    @endisset
</section>
