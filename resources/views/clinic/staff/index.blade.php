<x-layouts.clinic :title="__('clinic.staff.heading')">
    <x-page-header :title="__('clinic.staff.heading')" :subtitle="__('clinic.staff.subtitle')">
        <x-slot:actions>
            <x-button :href="route('clinic.staff.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('clinic.staff.add') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('auth.name') }}</x-th>
            <x-th>{{ __('auth.phone') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th></x-th>
        </x-slot:head>
        @forelse ($staff as $member)
            <tr>
                <x-td>{{ $member->name }}</x-td>
                <x-td dir="ltr">{{ $member->phone }}</x-td>
                <x-td>
                    <x-badge :tone="$member->is_active ? 'success' : 'neutral'">
                        {{ $member->is_active ? __('common.active') : __('common.inactive') }}
                    </x-badge>
                </x-td>
                <x-td>
                    <form method="POST" action="{{ route('clinic.staff.destroy', $member) }}"
                          onsubmit="return confirm(@json(__('common.confirm_delete')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-danger-500 hover:underline">
                            {{ __('common.delete') }}
                        </button>
                    </form>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="4"/>
        @endforelse
    </x-table>
</x-layouts.clinic>
