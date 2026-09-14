@php
    $localeOptions = collect(config('hakeem.locales'))->map(fn ($locale) => $locale['native'])->all();
    $isSelf = $member->exists && $member->is(auth()->user());
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('auth.name')" name="name" required>
        <x-input name="name" :value="$member->name"/>
    </x-field>

    <x-field :label="__('auth.phone')" name="phone" required>
        <x-input name="phone" type="tel" dir="ltr" :value="$member->phone" :placeholder="__('auth.phone_placeholder')"/>
    </x-field>

    <x-field :label="__('auth.email')" name="email" required>
        <x-input name="email" type="email" dir="ltr" :value="$member->email"/>
    </x-field>

    <x-field :label="__('common.language')" name="preferred_language" required>
        <x-select name="preferred_language" :options="$localeOptions" :selected="$member->preferred_language ?? 'ar'"/>
    </x-field>

    <x-field :label="__('auth.password_label')" name="password" :required="! $member->exists"
             :hint="$member->exists ? __('admin.staff.password_hint') : null">
        <x-input name="password" type="password" autocomplete="new-password"/>
    </x-field>

    <x-field :label="__('auth.password_confirmation')" name="password_confirmation" :required="! $member->exists">
        <x-input name="password_confirmation" type="password" autocomplete="new-password"/>
    </x-field>
</div>

<fieldset class="mt-5 rounded-lg border border-ink-200 p-4">
    <legend class="px-1 text-sm font-medium text-ink-700">{{ __('admin.staff.roles') }}</legend>

    @if ($isSelf)
        <x-alert tone="warning" class="mb-3">{{ __('admin.staff.cannot_edit_self_roles') }}</x-alert>
    @endif

    <div class="space-y-2.5">
        @foreach ($roles as $role)
            <x-checkbox name="roles[]" :value="$role->name" :label="$role->label"
                        :checked="in_array($role->name, old('roles', $assigned), true)"
                        :disabled="$isSelf"/>
        @endforeach
    </div>

    @if ($isSelf)
        @foreach ($assigned as $roleName)
            <input type="hidden" name="roles[]" value="{{ $roleName }}">
        @endforeach
    @endif

    @error('roles')
        <p class="mt-2 text-xs font-medium text-danger-500">{{ $message }}</p>
    @enderror
</fieldset>

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$member->is_active ?? true"/>
</div>
