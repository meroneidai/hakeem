@props(['title' => null, 'heading' => null, 'subheading' => null, 'wide' => false])

<x-layouts.base :title="$title" body-class="min-h-screen bg-gradient-to-b from-primary-50 via-ink-50 to-ink-50">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div @class(['w-full', 'max-w-3xl' => $wide, 'max-w-md' => ! $wide])>
            <div class="mb-6 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                    <span class="grid size-10 place-items-center rounded-xl bg-primary-600 text-xl font-bold text-white">ح</span>
                    <span>
                        <span class="block text-base font-bold text-ink-900">{{ __('common.app_name') }}</span>
                        <span class="block text-xs text-ink-500">{{ __('common.app_tagline') }}</span>
                    </span>
                </a>
                <x-locale-switcher/>
            </div>

            <div class="card p-6">
                @if ($heading)
                    <h1 class="text-lg font-semibold text-ink-900">{{ $heading }}</h1>
                @endif
                @if ($subheading)
                    <p class="mt-1 text-sm text-ink-500">{{ $subheading }}</p>
                @endif

                @if (session('status'))
                    <x-alert tone="success" class="mt-4">{{ session('status') }}</x-alert>
                @endif

                <div class="mt-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.base>
