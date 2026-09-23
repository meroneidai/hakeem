<x-layouts.public :title="__('labs.cart.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('labs.cart.heading')" :subtitle="__('labs.cart.checkout_soon')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.index') }}" class="hover:text-primary-700">{{ __('discover.nav.labs') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-8" x-data x-init="$store.labCart.apply(@js($cart->toPayload()))">

        @php($lines = $cart->lines())

        @if ($lines->isEmpty())
            <x-card>
                <x-empty-state :message="__('labs.cart.empty')"/>
                <div class="mt-4 text-center">
                    <x-button :href="route('labs.index')" variant="accent">{{ __('labs.tests') }}</x-button>
                </div>
            </x-card>
        @else
            <div x-show="$store.labCart.count === 0" x-cloak>
                <x-card>
                    <x-empty-state :message="__('labs.cart.empty')"/>
                    <div class="mt-4 text-center">
                        <x-button :href="route('labs.index')" variant="accent">{{ __('labs.tests') }}</x-button>
                    </div>
                </x-card>
            </div>
            <div class="space-y-4" x-show="$store.labCart.count > 0">
                <ul class="space-y-3">
                    @foreach ($lines as $line)
                        <li class="card p-4"
                            x-data="{ qty: {{ (int) $line['qty'] }} }"
                            x-show="qty > 0">
                            <div class="flex gap-3">
                                <x-media
                                    :src="$line['type'] === 'test' ? $line['item']->imageUrl() : \App\Support\PublicImage::url($line['item']->image_path)"
                                    :alt="$line['item']->name"
                                    class="size-16 shrink-0 rounded-2xl"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-medium text-accent-700">{{ $line['type'] === 'package' ? __('labs.packages') : __('labs.tests') }}</p>
                                            <a href="{{ $line['type'] === 'package' ? route('labs.packages.show', $line['item']) : route('labs.tests.show', $line['item']) }}"
                                               class="font-semibold text-ink-900 hover:text-primary-700">{{ $line['item']->name }}</a>
                                        </div>
                                        <p class="shrink-0 text-sm font-semibold text-ink-900">
                                            <span x-text="({{ (int) $line['unit_price'] }} * qty).toLocaleString('en-EG') + ' ' + @js(__('common.currency'))">
                                                {{ number_format($line['line_total']) }} {{ __('common.currency') }}
                                            </span>
                                        </p>
                                    </div>
                                    @if ($line['type'] === 'package' && $line['item']->includes)
                                        <p class="mt-1 text-sm text-ink-500">{{ $line['item']->includes }}</p>
                                    @elseif ($line['type'] === 'test' && $line['item']->measures)
                                        <p class="mt-1 text-sm text-ink-500">{{ $line['item']->measures }}</p>
                                    @endif
                                    @if ($line['type'] === 'package' && $line['item']->relationLoaded('tests') && $line['item']->tests->isNotEmpty())
                                        <p class="mt-1 text-xs text-ink-400">{{ $line['item']->tests->pluck('name')->filter()->implode(' · ') }}</p>
                                    @endif
                                    @if ($line['type'] === 'test' && $line['item']->fasting_hours)
                                        <p class="mt-1 text-xs text-warning-700">{{ __('labs.fasting', ['hours' => $line['item']->fasting_hours]) }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-ink-400">{{ __('labs.cart.guide_price') }} · {{ number_format($line['unit_price']) }} {{ __('common.currency') }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                <div class="inline-flex items-center rounded-full bg-ink-50 ring-1 ring-ink-200">
                                    <button type="button"
                                            class="grid size-9 place-items-center text-ink-700 hover:text-primary-700"
                                            :disabled="$store.labCart.busy"
                                            @click.prevent="await $store.labCart.setQty(@js($line['type']), {{ (int) $line['id'] }}, qty - 1); qty = Math.max(0, qty - 1)"
                                            aria-label="{{ __('labs.cart.remove') }}">
                                        <x-icon name="minus" class="size-4"/>
                                    </button>
                                    <span class="min-w-8 text-center text-sm font-semibold tabular" x-text="qty">{{ $line['qty'] }}</span>
                                    <button type="button"
                                            class="grid size-9 place-items-center text-ink-700 hover:text-primary-700"
                                            :disabled="$store.labCart.busy || qty >= 20"
                                            @click.prevent="await $store.labCart.setQty(@js($line['type']), {{ (int) $line['id'] }}, qty + 1); qty = Math.min(20, qty + 1)"
                                            aria-label="{{ __('labs.add') }}">
                                        <x-icon name="plus" class="size-4"/>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('labs.cart.destroy') }}"
                                      @submit.prevent="await $store.labCart.remove(@js($line['type']), {{ (int) $line['id'] }}); qty = 0">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="type" value="{{ $line['type'] }}">
                                    <input type="hidden" name="id" value="{{ $line['id'] }}">
                                    <x-button variant="danger-ghost" size="sm">{{ __('labs.cart.remove') }}</x-button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <x-card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-ink-500">{{ __('labs.cart.total') }}</p>
                        <p class="text-lg font-semibold text-ink-900" x-text="$store.labCart.total_label || @js(number_format($cart->total()).' '.__('common.currency'))">
                            {{ number_format($cart->total()) }} {{ __('common.currency') }}
                        </p>
                    </div>
                    <p class="text-xs text-ink-400">{{ __('labs.checkout.price_hint') }}</p>

                    @auth
                        <x-button :href="route('labs.checkout')" variant="accent" class="w-full">{{ __('labs.checkout.continue') }}</x-button>
                    @else
                        <p class="text-sm text-ink-500">{{ __('labs.checkout.login_first') }}</p>
                        <x-button :href="route('login')" variant="accent" class="w-full">{{ __('auth.login') }}</x-button>
                    @endauth
                </x-card>
            </div>
        @endif
    </div>
</x-layouts.public>
