<x-layouts.public :title="$heading ?? __('pages.contact.heading')" :json-ld="$jsonLd ?? []">
    <x-catalog-hero :title="$heading ?? __('pages.contact.heading')" :subtitle="$lead ?? __('pages.contact.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ $heading ?? __('pages.contact.heading') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl space-y-5 px-4 py-10">
        @php
            $support = app(\App\Support\SupportLinks::class);
            $branding = app(\App\Support\Branding::class);
        @endphp
        @if ($support->hasPhone() || $support->email() || $support->hasWhatsapp() || $branding->social())
            <div class="grid gap-3 sm:grid-cols-3">
                @if ($support->hasPhone())
                    <x-card class="p-4">
                        <p class="text-xs font-medium text-ink-400">{{ __('pages.contact.phone') }}</p>
                        <a href="{{ $support->phoneUrl() }}" class="mt-1 block font-semibold text-ink-900" dir="ltr">{{ $support->telephone() }}</a>
                    </x-card>
                @endif
                @if ($support->email())
                    <x-card class="p-4">
                        <p class="text-xs font-medium text-ink-400">{{ __('pages.contact.email') }}</p>
                        <a href="mailto:{{ $support->email() }}" class="mt-1 block break-all font-semibold text-ink-900" dir="ltr">{{ $support->email() }}</a>
                    </x-card>
                @endif
                @if ($support->hasWhatsapp())
                    <x-card class="p-4">
                        <p class="text-xs font-medium text-ink-400">{{ __('pages.contact.whatsapp') }}</p>
                        <a href="{{ $support->whatsappUrl() }}" class="mt-1 block font-semibold text-primary-700" target="_blank" rel="noopener">{{ __('pages.contact.whatsapp') }}</a>
                    </x-card>
                @endif
            </div>
            @if ($branding->social())
                <div class="flex flex-wrap gap-3 text-sm">
                    @foreach ($branding->social() as $network => $url)
                        <a href="{{ $url }}" class="font-medium text-primary-700 hover:underline" rel="noopener noreferrer" target="_blank">{{ $network }}</a>
                    @endforeach
                </div>
            @endif
        @endif

        <x-card>
            <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
                @csrf

                <x-field :label="__('auth.name')" name="name" required>
                    <x-input name="name" autocomplete="name"/>
                </x-field>

                <x-field :label="__('auth.phone')" name="phone" required>
                    <x-input name="phone" type="tel" dir="ltr" inputmode="tel" autocomplete="tel"/>
                </x-field>

                <x-field :label="__('auth.email')" name="email">
                    <x-input name="email" type="email" autocomplete="email"/>
                </x-field>

                <x-field :label="__('pages.contact.audience')" name="audience" required>
                    <x-select
                        name="audience"
                        :placeholder="__('pages.contact.audience')"
                        :options="collect(__('pages.contact.audiences'))->all()"
                        :selected="old('audience', 'patient')"
                    />
                </x-field>

                <x-field :label="__('pages.contact.subject')" name="subject" required>
                    <x-input name="subject"/>
                </x-field>

                <x-field :label="__('pages.contact.message')" name="message" required>
                    <x-textarea name="message" rows="5"/>
                </x-field>

                <x-button variant="accent">{{ __('pages.contact.send') }}</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.public>
