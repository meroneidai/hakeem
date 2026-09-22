<x-layouts.public :title="__('pages.complaints.heading')">
    <x-catalog-hero :title="__('pages.complaints.heading')" :subtitle="__('pages.complaints.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('pages.complaints.heading') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-card>
            <form method="POST" action="{{ route('complaints.store') }}" class="space-y-4">
                @csrf
                <x-field :label="__('auth.name')" name="name" required>
                    <x-input name="name" :value="old('name', auth()->user()?->name)"/>
                </x-field>
                <x-field :label="__('auth.phone')" name="phone" required>
                    <x-input name="phone" type="tel" dir="ltr" :value="old('phone', auth()->user()?->phone)"/>
                </x-field>
                <x-field :label="__('pages.contact.subject')" name="subject" required>
                    <x-input name="subject"/>
                </x-field>
                <x-field :label="__('pages.complaints.body')" name="body" required>
                    <x-textarea name="body" rows="6"/>
                </x-field>
                <x-button variant="accent">{{ __('pages.complaints.send') }}</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.public>
