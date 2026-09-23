@php
    $categoryOptions = collect(App\Enums\LabTestCategory::cases())
        ->mapWithKeys(fn ($category) => [$category->value => $category->label()])
        ->all();
@endphp

<x-layouts.admin :title="__('admin.labs.tests_heading')">
    <x-page-header :title="__('admin.labs.tests_heading')" :subtitle="__('admin.labs.tests_subheading')">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-select name="category" :placeholder="__('common.all')" :options="$categoryOptions"
                          :selected="request('category')" class="w-44"/>
                <x-input name="q" :value="request('q')" :placeholder="__('common.search')" class="w-40"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
            <x-button :href="route('admin.lab-tests.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.labs.create_test') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('common.name_en') }}</x-th>
            <x-th>{{ __('admin.labs.category') }}</x-th>
            <x-th>{{ __('admin.labs.sample') }}</x-th>
            <x-th>{{ __('admin.labs.price') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($tests as $test)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="$test->imageUrl()"
                            :alt="$test->name_ar"
                            class="size-8 rounded-lg"
                        />
                        {{ $test->name_ar }}
                    </span>
                </x-td>
                <x-td>{{ $test->name_en }}</x-td>
                <x-td><x-badge tone="primary">{{ $test->category->label() }}</x-badge></x-td>
                <x-td class="text-sm">{{ $test->sample_type->label() }}</x-td>
                <x-td class="tabular text-sm">{{ number_format((float) $test->suggested_price) }} {{ __('common.currency') }}</x-td>
                <x-td><x-status-dot :active="$test->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.lab-tests.edit', $test)"
                                   :destroy="route('admin.lab-tests.destroy', $test)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7"/>
        @endforelse

        @if ($tests->hasPages())
            <x-slot:footer>{{ $tests->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
