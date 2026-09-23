<x-layouts.clinic :title="__('clinic.doctors.heading')">
    <x-page-header :title="__('clinic.doctors.heading')" :subtitle="__('clinic.doctors.subtitle')">
        <x-slot:actions>
            @if ($access->canManage() && $clinic->canAddDoctor())
                <x-button :href="route('clinic.doctors.create')" variant="accent">
                    <x-icon name="plus" class="size-4"/>
                    {{ __('clinic.doctors.add') }}
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('clinic.doctors.specialty') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th></x-th>
        </x-slot:head>

        @forelse ($doctors as $doctor)
            <tr>
                <x-td>
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
                            :alt="$doctor->name"
                            class="size-9 rounded-xl"
                        />
                        <span>
                            <span class="font-medium text-ink-900">{{ $doctor->name }}</span>
                            <span class="mt-0.5 block text-xs text-ink-400" dir="ltr">{{ $doctor->name_en }}</span>
                        </span>
                    </span>
                </x-td>
                <x-td>{{ $doctor->specialty?->name ?? '—' }}</x-td>
                <x-td>
                    <x-badge :tone="$doctor->is_active ? 'success' : 'neutral'">
                        {{ $doctor->is_active ? __('common.active') : __('common.inactive') }}
                    </x-badge>
                </x-td>
                <x-td>
                    @if ($access->canManageDoctor($doctor))
                        <a href="{{ route('clinic.doctors.edit', $doctor) }}" class="text-sm font-medium text-primary-600 hover:underline">
                            {{ __('common.edit') }}
                        </a>
                    @endif
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="4"/>
        @endforelse
    </x-table>
</x-layouts.clinic>
