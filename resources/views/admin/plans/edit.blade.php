<x-layouts.admin :title="__('admin.plans.edit')">
    <x-page-header :title="__('admin.plans.edit')" :subtitle="$plan->name_ar"/>

    <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('admin.plans._form')

            <x-slot:footer>
                <x-button :href="route('admin.plans.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
