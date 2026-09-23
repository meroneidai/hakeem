<x-layouts.admin :title="__('admin.labs.edit_test')">
    <x-page-header :title="__('admin.labs.edit_test')" :subtitle="$test->name_ar"/>

    <form method="POST" action="{{ route('admin.lab-tests.update', $test) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('admin.labs.tests._form')
            <x-slot:footer>
                <x-button :href="route('admin.lab-tests.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
