@props(['head' => null])

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-ink-200 text-sm">
            @isset($head)
                <thead class="bg-ink-50/70">
                    <tr>{{ $head }}</tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-ink-100 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-ink-200 bg-white px-4 py-3">{{ $footer }}</div>
    @endisset
</div>
