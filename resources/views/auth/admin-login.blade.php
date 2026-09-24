<x-layouts.auth :title="__('auth.admin_login')" :heading="__('auth.admin_login_title')" :subheading="__('auth.admin_login_subtitle')">
    <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
        @csrf

        <x-identifier-field autofocus :value="old('identifier', old('email'))"/>

        <x-field :label="__('auth.password_label')" name="password" required>
            <x-input name="password" type="password" autocomplete="current-password"/>
        </x-field>

        <x-checkbox name="remember" :label="__('auth.remember_me')"/>

        <x-button variant="accent" class="w-full" size="lg">{{ __('auth.admin_login') }}</x-button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-500">
        {{ __('auth.forgot_password') }}
        <a href="{{ route('password.request') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.reset_password') }}</a>
    </p>

    <p class="mt-3 text-center text-sm text-ink-500">
        {{ __('auth.phone_users_only') }}
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.login') }}</a>
    </p>

    @if (app(\App\Support\Branding::class)->showDemoLogins())
        <p class="mt-4 rounded-lg bg-ink-50 p-3 text-center text-xs text-ink-600">{{ __('auth.demo_admin_credentials') }}</p>
    @endif
</x-layouts.auth>
