<x-layouts.auth :title="__('auth.forgot_password')" :heading="__('auth.forgot_title')" :subheading="__('auth.forgot_subtitle')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-identifier-field autofocus :value="old('identifier')"/>

        <x-button variant="accent" class="w-full" size="lg">{{ __('auth.send_reset') }}</x-button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-500">
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.login') }}</a>
    </p>
</x-layouts.auth>
