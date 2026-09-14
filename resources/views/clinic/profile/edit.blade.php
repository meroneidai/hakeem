<x-layouts.clinic :title="__('clinic.profile.heading')">
    <x-page-header :title="__('clinic.profile.heading')" :subtitle="__('clinic.profile.subtitle')"/>

    <form method="POST" action="{{ route('clinic.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <x-card class="max-w-3xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('common.name_ar')" name="name_ar" required>
                    <x-input name="name_ar" :value="$clinic->name_ar"/>
                </x-field>
                <x-field :label="__('common.name_en')" name="name_en" required>
                    <x-input name="name_en" :value="$clinic->name_en" dir="ltr"/>
                </x-field>
                <x-field :label="__('auth.email')" name="email" required>
                    <x-input name="email" type="email" :value="$clinic->email" dir="ltr"/>
                </x-field>
                <x-field :label="__('clinic.profile.phone')" name="phone" required>
                    <x-input name="phone" type="tel" :value="$clinic->phone" dir="ltr"/>
                </x-field>
                <x-field :label="__('common.description_ar')" name="description_ar" class="sm:col-span-2">
                    <x-textarea name="description_ar" :value="$clinic->description_ar"/>
                </x-field>
                <x-field :label="__('common.description_en')" name="description_en" class="sm:col-span-2">
                    <x-textarea name="description_en" :value="$clinic->description_en"/>
                </x-field>
                <x-field :label="__('clinic.profile.logo')" name="logo" :hint="__('clinic.profile.logo_hint')" class="sm:col-span-2">
                    @if ($clinic->logo_path)
                        <img src="{{ Storage::url($clinic->logo_path) }}" alt="" class="mb-3 size-16 rounded-xl object-cover">
                    @endif
                    <x-input name="logo" type="file" accept="image/*"/>
                </x-field>
            </div>

            <x-slot:footer>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.clinic>
