<x-layouts.public :title="__('labs.cart.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('labs.cart.heading')" :subtitle="__('labs.cart.checkout_soon')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.index') }}" class="hover:text-primary-700">{{ __('discover.nav.labs') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-3 py-6 min-[390px]:px-4 sm:py-10" x-data x-init="$store.labCart.apply(@js($cart->toPayload()))">
        @php($lines = $cart->lines())

        @if ($lines->isEmpty())
            <div class="mx-auto max-w-lg rounded-[1.5rem] bg-white p-8 text-center shadow-sm ring-1 ring-ink-100">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-primary-50 text-primary-700">
                    <x-icon name="bag" class="size-7"/>
                </span>
                <div class="mt-4">
                    <x-empty-state :message="__('labs.cart.empty')"/>
                </div>
                <x-button :href="route('labs.index')" variant="accent" class="mt-5">
                    <x-icon name="beaker" class="size-4"/>
                    {{ __('labs.tests') }}
                </x-button>
            </div>
        @else
            <div x-show="$store.labCart.count === 0" x-cloak>
                <div class="mx-auto max-w-lg rounded-[1.5rem] bg-white p-8 text-center shadow-sm ring-1 ring-ink-100">
                    <x-empty-state :message="__('labs.cart.empty')"/>
                    <x-button :href="route('labs.index')" variant="accent" class="mt-5">{{ __('labs.tests') }}</x-button>
                </div>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.85fr)]" x-show="$store.labCart.count > 0">
                <ul class="space-y-3">
                    @foreach ($lines as $line)
                        <li class="overflow-hidden rounded-[1.25rem] bg-white p-4 shadow-sm ring-1 ring-ink-100 sm:p-5"
                            x-data="{ qty: {{ (int) $line['qty'] }} }"
                            x-show="qty > 0">
                            <div class="flex gap-3 text-start sm:gap-4">
                                <x-media
                                    :src="$line['type'] === 'test' ? $line['item']->imageUrl() : \App\Support\PublicImage::url($line['item']->image_path)"
                                    :alt="$line['item']->name"
                                    class="size-16 shrink-0 rounded-2xl ring-1 ring-ink-100 sm:size-20"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-teal-700">
                                                {{ $line['type'] === 'package' ? __('labs.packages') : __('labs.tests') }}
                                            </p>
                                            <a href="{{ $line['type'] === 'package' ? route('labs.packages.show', $line['item']) : route('labs.tests.show', $line['item']) }}"
                                               class="mt-0.5 block font-semibold text-ink-900 hover:text-primary-700">
                                                {{ $line['item']->name }}
                                            </a>
                                        </div>
                                        <p class="shrink-0 text-sm font-bold tabular text-primary-800 sm:text-base">
                                            <span x-text="({{ (int) $line['unit_price'] }} * qty).toLocaleString('en-EG') + ' ' + @js(__('common.currency'))">
                                                {{ number_format($line['line_total']) }} {{ __('common.currency') }}
                                            </span>
                                        </p>
                                    </div>
                                    @if ($line['type'] === 'package' && $line['item']->includes)
                                        <p class="mt-1 line-clamp-2 text-sm text-ink-500">{{ $line['item']->includes }}</p>
                                    @elseif ($line['type'] === 'test' && $line['item']->measures)
                                        <p class="mt-1 line-clamp-2 text-sm text-ink-500">{{ $line['item']->measures }}</p>
                                    @endif
                                    @if ($line['type'] === 'package' && $line['item']->relationLoaded('tests') && $line['item']->tests->isNotEmpty())
                                        <p class="mt-1 text-xs text-ink-400">{{ $line['item']->tests->pluck('name')->filter()->implode(' · ') }}</p>
                                    @endif
                                    @if ($line['type'] === 'test' && $line['item']->fasting_hours)
                                        <p class="mt-1 text-xs font-medium text-warning-700">{{ __('labs.fasting', ['hours' => $line['item']->fasting_hours]) }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-ink-400">{{ __('labs.cart.guide_price') }} · {{ number_format($line['unit_price']) }} {{ __('common.currency') }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-ink-50 pt-4">
                                <div class="inline-flex items-center rounded-full bg-ink-50 ring-1 ring-ink-200">
                                    <button type="button"
                                            class="grid size-10 place-items-center text-ink-700 hover:text-primary-700"
                                            :disabled="$store.labCart.busy"
                                            @click.prevent="await $store.labCart.setQty(@js($line['type']), {{ (int) $line['id'] }}, qty - 1); qty = Math.max(0, qty - 1)"
                                            aria-label="{{ __('labs.cart.remove') }}">
                                        <x-icon name="minus" class="size-4"/>
                                    </button>
                                    <span class="min-w-8 text-center text-sm font-bold tabular" x-text="qty">{{ $line['qty'] }}</span>
                                    <button type="button"
                                            class="grid size-10 place-items-center text-ink-700 hover:text-primary-700"
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

                <aside class="lg:sticky lg:top-24">
                    <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-[0_12px_40px_rgba(15,42,95,0.08)] ring-1 ring-ink-100">
                        <div class="border-b border-ink-100 bg-gradient-to-l from-primary-50 to-white px-5 py-4">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.cart.total') }}</h2>
                        </div>
                        <div class="space-y-4 p-5">
                            <div class="flex items-end justify-between gap-3">
                                <p class="text-sm text-ink-500">{{ __('labs.cart.total') }}</p>
                                <p class="text-2xl font-bold tabular text-primary-800" x-text="$store.labCart.total_label || @js(number_format($cart->total()).' '.__('common.currency'))">
                                    {{ number_format($cart->total()) }} {{ __('common.currency') }}
                                </p>
                            </div>
                            <p class="text-xs leading-5 text-ink-400">{{ __('labs.checkout.price_hint') }}</p>

                            @auth
                                <x-button :href="route('labs.checkout')" variant="accent" class="w-full" size="lg">
                                    <x-icon name="check" class="size-4"/>
                                    {{ __('labs.checkout.continue') }}
                                </x-button>
                            @else
                                <p class="rounded-xl bg-ink-50 px-3 py-2 text-sm text-ink-500">{{ __('labs.checkout.login_first') }}</p>
                                <x-button :href="route('login')" variant="accent" class="w-full">{{ __('auth.login') }}</x-button>
                            @endauth

                            <x-button :href="route('labs.index')" variant="ghost" class="w-full" size="sm">
                                {{ __('labs.tests') }}
                            </x-button>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.public>
