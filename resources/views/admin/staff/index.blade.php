<x-layouts.admin :title="__('admin.staff.heading')">
    <x-page-header :title="__('admin.staff.heading')" :subtitle="__('admin.staff.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.staff.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.staff.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('auth.name') }}</x-th>
            <x-th>{{ __('auth.phone') }}</x-th>
            <x-th>{{ __('auth.email') }}</x-th>
            <x-th>{{ __('admin.staff.roles') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($staff as $member)
            <tr>
                <x-td class="font-medium text-ink-900">{{ $member->name }}</x-td>
                <x-td><span class="tabular text-sm" dir="ltr">{{ $member->phone }}</span></x-td>
                <x-td><span class="text-sm text-ink-500" dir="ltr">{{ $member->email }}</span></x-td>
                <x-td>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($member->roles as $role)
                            <x-badge :tone="$role->name === 'platform_admin' ? 'primary' : 'neutral'">{{ $role->label }}</x-badge>
                        @endforeach
                    </div>
                </x-td>
                <x-td><x-status-dot :active="$member->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.staff.edit', $member)"
                                   :destroy="$member->is(auth()->user()) ? null : route('admin.staff.destroy', $member)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse

        @if ($staff->hasPages())
            <x-slot:footer>{{ $staff->links() }}</x-slot:footer>
        @endif
    </x-table>

    <x-card class="mt-5" :title="__('admin.staff.permissions')">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach (App\Enums\RoleName::internalStaff() as $role)
                <div>
                    <p class="mb-2 text-sm font-semibold text-ink-800">{{ $role->labelAr() }}</p>
                    @if (in_array('*', $role->permissions(), true))
                        <x-badge tone="primary">{{ __('admin.staff.full_access') }}</x-badge>
                    @else
                        <ul class="space-y-1">
                            @foreach ($role->permissions() as $permission)
                                <li class="flex items-center gap-1.5 text-xs text-ink-600">
                                    <x-icon name="check" class="size-3 text-success-500"/>
                                    {{ App\Enums\Permission::from($permission)->labelAr() }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </x-card>
</x-layouts.admin>
