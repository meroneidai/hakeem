@php
    $ios = $appIosUrl ?? null;
    $android = $appAndroidUrl ?? null;
@endphp

<section class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-primary-500 to-primary-700 p-6 text-white shadow-[0_12px_40px_rgba(30,64,175,0.16)] sm:p-8">
    <div class="flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium">
                <x-icon name="device-phone" class="size-4"/>
                {{ __('discover.app.heading') }}
            </span>
            <h2 class="mt-3 text-2xl font-bold">{{ __('discover.app.heading') }}</h2>
            <p class="mt-2 text-sm text-primary-100">{{ __('discover.app.subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a @if ($ios) href="{{ $ios }}" target="_blank" rel="noopener" @endif
               class="inline-flex items-center gap-2 rounded-2xl bg-ink-900 px-4 py-3 text-sm font-semibold text-white {{ $ios ? 'hover:bg-ink-800' : 'cursor-default opacity-90' }}">
                <x-icon name="device-phone" class="size-5"/>
                <span>
                    <span class="block text-[10px] font-normal text-ink-300">{{ $ios ? __('discover.app.ios') : __('discover.app.soon') }}</span>
                    {{ __('discover.app.ios') }}
                </span>
            </a>
            <a @if ($android) href="{{ $android }}" target="_blank" rel="noopener" @endif
               class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-ink-900 {{ $android ? 'hover:bg-primary-50' : 'cursor-default opacity-90' }}">
                <x-icon name="download" class="size-5 text-primary-700"/>
                <span>
                    <span class="block text-[10px] font-normal text-ink-500">{{ $android ? __('discover.app.android') : __('discover.app.soon') }}</span>
                    {{ __('discover.app.android') }}
                </span>
            </a>
        </div>
    </div>
</section>
