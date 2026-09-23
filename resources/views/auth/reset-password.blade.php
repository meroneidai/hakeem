<x-layouts.auth :title="__('auth.reset_password')" :heading="__('auth.reset_title')" :subheading="__('auth.reset_subtitle')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="identifier" value="{{ old('identifier', $identifier) }}">

        @if ($token)
            <input type="hidden" name="token" value="{{ $token }}">
        @else
            <x-field :label="__('auth.reset_code')" name="token" required :hint="__('auth.reset_code_hint')">
                <x-input name="token" dir="ltr" inputmode="numeric" autocomplete="one-time-code"/>
            </x-field>
        @endif

        <x-field :label="__('auth.password_label')" name="password" required>
            <x-input name="password" type="password" autocomplete="new-password"/>
        </x-field>

        <x-field :label="__('auth.password_confirmation')" name="password_confirmation" required>
            <x-input name="password_confirmation" type="password" autocomplete="new-password"/>
        </x-field>

        <x-button variant="accent" class="w-full" size="lg">{{ __('auth.reset_password') }}</x-button>
    </form>
</x-layouts.auth>
