<x-layouts.public :title="__('pages.contact.heading')">
    <x-catalog-hero :title="__('pages.contact.heading')" :subtitle="__('pages.contact.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('pages.contact.heading') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-10">
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
