<x-layouts.auth :title="__('auth.login')" :heading="__('auth.login_title')" :subheading="__('auth.login_subtitle')">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-field :label="__('auth.phone')" name="phone" required :hint="__('auth.phone_hint')">
            <x-input name="phone" type="tel" dir="ltr" inputmode="tel" autocomplete="username"
                     :placeholder="__('auth.phone_placeholder')" autofocus/>
        </x-field>

        <x-field :label="__('auth.password_label')" name="password" required>
            <x-input name="password" type="password" autocomplete="current-password"/>
        </x-field>

        <x-checkbox name="remember" :label="__('auth.remember_me')"/>

        <x-button variant="accent" class="w-full" size="lg">{{ __('auth.login') }}</x-button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-500">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.register') }}</a>
        <span class="text-ink-300">·</span>
        <a href="{{ route('register.clinic') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.register_clinic') }}</a>
    </p>
</x-layouts.auth>
