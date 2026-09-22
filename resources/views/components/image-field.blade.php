@props(['name' => 'image', 'path' => null, 'label' => null, 'hint' => null])

<x-field :label="$label ?? __('common.image')" :name="$name" :hint="$hint ?? __('common.image_hint')" class="sm:col-span-2">
    @if ($path)
        <img src="{{ \App\Support\PublicImage::url($path) }}" alt="" class="mb-3 size-20 rounded-xl object-cover ring-1 ring-ink-200">
    @endif
    <input
        type="file"
        name="{{ $name }}"
        id="{{ $name }}"
        accept="image/jpeg,image/png,image/webp"
        {{ $attributes->merge([
            'class' => 'field-input file:me-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-700 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 '
                . ($errors->has($name) ? 'border-danger-500' : ''),
        ]) }}
    >
</x-field>
