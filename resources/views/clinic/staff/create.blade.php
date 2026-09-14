<x-layouts.clinic :title="__('clinic.staff.add')">
    <x-page-header :title="__('clinic.staff.add')"/>

    <form method="POST" action="{{ route('clinic.staff.store') }}">
        @csrf
        <x-card class="max-w-xl">
            <div class="space-y-4">
                <x-field :label="__('auth.name')" name="name" required>
                    <x-input name="name" autocomplete="name"/>
                </x-field>
                <x-field :label="__('auth.phone')" name="phone" required :hint="__('auth.phone_hint')">
                    <x-input name="phone" type="tel" dir="ltr" :placeholder="__('auth.phone_placeholder')"/>
                </x-field>
                <x-field :label="__('auth.password_label')" name="password" required>
                    <x-input name="password" type="password" autocomplete="new-password"/>
                </x-field>
            </div>
            <x-slot:footer>
                <x-button :href="route('clinic.staff.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.clinic>
