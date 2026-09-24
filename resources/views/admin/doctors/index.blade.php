<x-layouts.admin :title="__('admin.doctors.heading')">
    <x-page-header :title="__('admin.doctors.heading')" :subtitle="__('admin.doctors.subheading')"/>

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('common.search')"/>
        <x-select name="specialty" :placeholder="__('admin.doctors.any_specialty')" :selected="$filters['specialty'] ?? ''"
                  :options="$specialties->pluck('name', 'slug')->all()"/>
        <x-select name="status" :placeholder="__('common.status')" :selected="$filters['status'] ?? ''"
                  :options="['active' => __('common.active'), 'inactive' => __('common.inactive')]"/>
        <x-button variant="secondary">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('auth.name') }}</x-th>
            <x-th>{{ __('admin.nav.specialties') }}</x-th>
            <x-th>{{ __('admin.doctors.clinics') }}</x-th>
            <x-th>{{ __('admin.doctors.experience') }}</x-th>
            <x-th>{{ __('admin.doctors.fee') }}</x-th>
            <x-th>{{ __('admin.doctors.visits') }}</x-th>
            <x-th>{{ __('admin.doctors.rating') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($doctors as $doctor)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
                            :alt="$doctor->name"
                            class="size-8 rounded-full"
                        />
                        <span>
                            {{ $doctor->name }}
                            @if ($doctor->credentials)
                                <span class="block text-xs text-ink-400">{{ $doctor->credentials }}</span>
                            @endif
                        </span>
                    </span>
                </x-td>
                <x-td>{{ $doctor->specialty?->name }}</x-td>
                <x-td class="text-xs text-ink-500">
                    {{ $doctor->clinics->pluck('name')->join('، ') ?: __('common.none') }}
                </x-td>
                <x-td class="tabular">{{ $doctor->years_of_experience ?? 0 }}</x-td>
                <x-td class="tabular">
                    @if ($doctor->consultation_fee)
                        {{ number_format((float) $doctor->consultation_fee) }} {{ __('common.currency') }}
                    @else
                        {{ __('common.none') }}
                    @endif
                </x-td>
                <x-td class="tabular">{{ $doctor->completed_bookings_count }}</x-td>
                <x-td><x-rating :average="$doctor->ratingAverage()" :count="$doctor->ratingCount()"/></x-td>
                <x-td><x-status-dot :active="$doctor->is_active"/></x-td>
                <x-td>
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('doctors.show', $doctor) }}" class="text-sm font-medium text-primary-700 hover:underline">
                            {{ __('common.view') }}
                        </a>
                        <form method="POST" action="{{ route('admin.doctors.update', $doctor) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="toggle_active">
                            <button type="submit" class="inline-flex items-center rounded-lg px-2 py-1.5 text-xs font-medium text-ink-600 hover:bg-ink-50">
                                {{ $doctor->is_active ? __('common.deactivate') : __('common.activate') }}
                            </button>
                        </form>
                        <x-row-actions :destroy="route('admin.doctors.destroy', $doctor)"/>
                    </div>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="9"/>
        @endforelse
        @if ($doctors->hasPages())
            <x-slot:footer>{{ $doctors->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
