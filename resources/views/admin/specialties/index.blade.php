@php
    $categoryOptions = collect(App\Models\Specialty::CATEGORIES)
        ->mapWithKeys(fn ($category) => [$category => __('admin.specialties.categories.'.$category)])
        ->all();
@endphp

<x-layouts.admin :title="__('admin.specialties.heading')">
    <x-page-header :title="__('admin.specialties.heading')" :subtitle="__('admin.specialties.subheading')">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-select name="category" :placeholder="__('common.all')" :options="$categoryOptions"
                          :selected="request('category')" class="w-44"/>
                <x-input name="q" :value="request('q')" :placeholder="__('common.search')" class="w-40"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
            <x-button :href="route('admin.specialties.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.specialties.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('common.name_en') }}</x-th>
            <x-th>{{ __('admin.specialties.category') }}</x-th>
            <x-th>{{ __('common.slug') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($specialties as $specialty)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($specialty->image_path)"
                            :alt="$specialty->name_ar"
                            class="size-8 rounded-lg"
                        />
                        <span>
                            {{ $specialty->name_ar }}
                            @if ($specialty->is_featured)
                                <x-badge tone="accent" class="ms-1">{{ __('common.featured') }}</x-badge>
                            @endif
                        </span>
                    </span>
                </x-td>
                <x-td>{{ $specialty->name_en }}</x-td>
                <x-td>
                    <x-badge tone="primary">{{ __("admin.specialties.categories.{$specialty->category}") }}</x-badge>
                </x-td>
                <x-td><code class="text-xs text-ink-500" dir="ltr">{{ $specialty->slug }}</code></x-td>
                <x-td><x-status-dot :active="$specialty->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.specialties.edit', $specialty)"
                                   :destroy="route('admin.specialties.destroy', $specialty)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse

        @if ($specialties->hasPages())
            <x-slot:footer>{{ $specialties->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
