@props(['url', 'title', 'appUrl' => null, 'text' => null])

<button
    type="button"
    {{ $attributes->merge(['class' => 'inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-ink-300 bg-white px-2.5 py-1.5 text-xs font-medium text-ink-700 transition hover:bg-ink-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300']) }}
    x-data="{ copied: false }"
    data-share-url="{{ $url }}"
    data-app-url="{{ $appUrl }}"
    @click.stop="
        const payload = { title: @js($title), text: @js($text ?? $title), url: @js($url) };
        if (navigator.share) {
            navigator.share(payload).catch(() => {});
            return;
        }
        navigator.clipboard.writeText(payload.url).then(() => {
            copied = true;
            setTimeout(() => copied = false, 1600);
        });
    "
>
    <x-icon name="share" class="size-4"/>
    <span x-show="!copied">{{ __('discover.share') }}</span>
    <span x-cloak x-show="copied">{{ __('common.copied') }}</span>
</button>
