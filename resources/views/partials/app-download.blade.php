@php
    $branding = $branding ?? app(\App\Support\Branding::class);
    $ios = $appIosUrl ?? $branding->appIosUrl();
    $android = $appAndroidUrl ?? $branding->appAndroidUrl();
@endphp

<section class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 p-5 text-white shadow-[0_12px_40px_rgba(30,64,175,0.18)] sm:p-8">
    <div class="flex flex-col items-start gap-5 lg:flex-row lg:items-center lg:justify-between lg:gap-8">
        <div class="max-w-xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-primary-50 ring-1 ring-white/15">
                <x-icon name="device-phone" class="size-4"/>
                {{ __('discover.app.badge') }}
            </span>
            <h2 class="mt-3 text-xl font-bold tracking-tight sm:text-2xl">{{ __('discover.app.heading') }}</h2>
            <p class="mt-2 text-sm leading-6 text-primary-100">{{ __('discover.app.subtitle') }}</p>
        </div>

        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap">
            <a @if ($ios) href="{{ $ios }}" target="_blank" rel="noopener" @endif
               @class([
                   'group inline-flex min-h-[3.25rem] items-center gap-3 rounded-xl bg-black px-4 py-2.5 text-white shadow-lg ring-1 ring-white/10 transition',
                   'hover:bg-ink-900 hover:ring-white/25' => (bool) $ios,
                   'cursor-default opacity-80' => ! $ios,
               ])
               @if (! $ios) aria-disabled="true" @endif>
                <svg class="size-8 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                </svg>
                <span class="min-w-0 text-start leading-tight">
                    <span class="block text-[10px] font-normal tracking-wide text-white/75">
                        {{ $ios ? __('discover.app.download_on') : __('discover.app.soon') }}
                    </span>
                    <span class="block text-[1.05rem] font-semibold tracking-tight">{{ __('discover.app.ios') }}</span>
                </span>
            </a>

            <a @if ($android) href="{{ $android }}" target="_blank" rel="noopener" @endif
               @class([
                   'group inline-flex min-h-[3.25rem] items-center gap-3 rounded-xl bg-black px-4 py-2.5 text-white shadow-lg ring-1 ring-white/10 transition',
                   'hover:bg-ink-900 hover:ring-white/25' => (bool) $android,
                   'cursor-default opacity-80' => ! $android,
               ])
               @if (! $android) aria-disabled="true" @endif>
                <svg class="size-8 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M3.6 2.2 13.3 12 3.6 21.8c-.5-.3-.8-.9-.8-1.5V3.7c0-.6.3-1.2.8-1.5z"/>
                    <path fill="#FBBC04" d="m13.3 12 2.7-2.7 4.6 2.6c.7.4.7 1.4 0 1.8l-4.6 2.6L13.3 12z"/>
                    <path fill="#4285F4" d="M13.3 12 3.6 2.2c.3-.2.6-.2.9-.1l11.5 6.6L13.3 12z"/>
                    <path fill="#34A853" d="m13.3 12 2.7 2.7-11.5 6.6c-.3.1-.6.1-.9-.1L13.3 12z"/>
                </svg>
                <span class="min-w-0 text-start leading-tight">
                    <span class="block text-[10px] font-normal tracking-wide text-white/75">
                        {{ $android ? __('discover.app.get_it_on') : __('discover.app.soon') }}
                    </span>
                    <span class="block text-[1.05rem] font-semibold tracking-tight">{{ __('discover.app.android') }}</span>
                </span>
            </a>
        </div>
    </div>
</section>
