<x-layouts.auth :title="__('auth.login')" :heading="__('auth.login_title')" :subheading="__('auth.login_subtitle')">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-identifier-field autofocus :value="old('identifier', old('phone', old('email')))"/>

        <x-field :label="__('auth.password_label')" name="password" required>
            <x-input name="password" type="password" autocomplete="current-password"/>
        </x-field>

        <div class="flex items-center justify-between gap-3">
            <x-checkbox name="remember" :label="__('auth.remember_me')"/>
            <a href="{{ route('password.request') }}" class="text-xs font-medium text-primary-600 hover:underline">{{ __('auth.forgot_password') }}</a>
        </div>

        <x-button variant="accent" class="w-full" size="lg">{{ __('auth.login') }}</x-button>
    </form>

    <div class="mt-5">
        <p class="mb-3 text-center text-xs text-ink-400">{{ __('auth.or_social') }}</p>
        <div class="grid grid-cols-3 gap-2">
            @foreach (['google', 'facebook', 'apple'] as $provider)
                <a href="{{ route('auth.social.redirect', $provider) }}"
                   class="inline-flex items-center justify-center rounded-lg border border-ink-200 px-2 py-2 text-xs font-medium text-ink-700 hover:bg-ink-50">
                    {{ __('auth.social_'.$provider) }}
                </a>
            @endforeach
        </div>
    </div>

    <p class="mt-5 text-center text-sm text-ink-500">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.register') }}</a>
        <span class="text-ink-300">·</span>
        <a href="{{ route('register.clinic') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.register_clinic') }}</a>
    </p>

    <p class="mt-3 text-center text-sm text-ink-500">
        {{ __('auth.staff_use_admin_login') }}
        <a href="{{ route('admin.login') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.admin_login') }}</a>
    </p>

    @if (app(\App\Support\Branding::class)->showDemoLogins())
        <p class="mt-4 rounded-lg bg-ink-50 p-3 text-center text-xs text-ink-600">{{ __('auth.demo_phone_credentials') }}</p>
    @endif
</x-layouts.auth>
