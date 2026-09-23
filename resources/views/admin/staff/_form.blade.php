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

    <div class="space-y-3">
        @foreach ($roles as $role)
            @php
                $roleEnum = \App\Enums\RoleName::tryFrom($role->name);
                $permissionKeys = $roleEnum?->permissions() ?? [];
                $permissionLabels = in_array('*', $permissionKeys, true)
                    ? [__('admin.staff.full_access')]
                    : collect($permissionKeys)
                        ->map(fn (string $key) => \App\Enums\Permission::tryFrom($key)?->labelAr() ?? $key)
                        ->all();
            @endphp
            <div>
                <x-checkbox name="roles[]" :value="$role->name" :label="$role->label"
                            :checked="in_array($role->name, old('roles', $assigned), true)"
                            :disabled="$isSelf"/>
                <p class="ms-6 mt-0.5 text-xs text-ink-400">{{ implode(' · ', $permissionLabels) }}</p>
            </div>
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
