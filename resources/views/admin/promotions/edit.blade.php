<x-layouts.admin :title="__('admin.promotions.edit')">
    <x-page-header :title="__('admin.promotions.edit')" :subtitle="$promotion->title_ar"/>

    <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('admin.promotions._form')

            <x-slot:footer>
                <x-button :href="route('admin.promotions.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
