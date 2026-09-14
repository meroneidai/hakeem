<x-layouts.admin :title="__('admin.discount_codes.edit')">
    <x-page-header :title="__('admin.discount_codes.edit')" :subtitle="$code->code"/>

    <form method="POST" action="{{ route('admin.discount-codes.update', $code) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('admin.discount-codes._form')

            <x-slot:footer>
                <x-button :href="route('admin.discount-codes.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
