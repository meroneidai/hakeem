<x-layouts.auth :title="__('auth.register')" :heading="__('auth.register_title')" :subheading="__('auth.register_subtitle')">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-field :label="__('auth.name')" name="name" required>
            <x-input name="name" autocomplete="name" autofocus/>
        </x-field>

        <x-field :label="__('auth.phone')" name="phone" required :hint="__('auth.phone_hint')">
            <x-input name="phone" type="tel" dir="ltr" inputmode="tel" autocomplete="username"
                     :placeholder="__('auth.phone_placeholder')"/>
        </x-field>

        <x-field :label="__('auth.password_label')" name="password" required>
            <x-input name="password" type="password" autocomplete="new-password"/>
        </x-field>

        <x-field :label="__('auth.password_confirmation')" name="password_confirmation" required>
            <x-input name="password_confirmation" type="password" autocomplete="new-password"/>
        </x-field>

        <x-button variant="accent" class="w-full" size="lg">{{ __('auth.register') }}</x-button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-500">
        {{ __('auth.have_account') }}
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.login') }}</a>
        <span class="mt-2 block">
            <a href="{{ route('register.clinic') }}" class="font-medium text-primary-600 hover:underline">{{ __('clinic.register.clinic_instead') }}</a>
        </span>
    </p>
</x-layouts.auth>
