@props([
    'type',
    'id',
    'variant' => 'accent',
    'size' => 'sm',
])

@php
    $addLabel = __('labs.add');
    $inCartLabel = __('labs.cart.in_cart');
@endphp

<form method="POST"
      action="{{ route('labs.cart.store') }}"
      @submit.prevent="$store.labCart.add(@js($type), {{ (int) $id }})"
      {{ $attributes }}>
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="id" value="{{ $id }}">
    <x-button type="submit" :variant="$variant" :size="$size" class="w-full"
              x-bind:disabled="$store.labCart.busy">
        <span x-text="$store.labCart.has(@js($type), {{ (int) $id }}) ? @js($inCartLabel) : @js($addLabel)">
            {{ $addLabel }}
        </span>
    </x-button>
</form>
