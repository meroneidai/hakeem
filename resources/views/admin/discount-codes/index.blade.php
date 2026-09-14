<x-layouts.admin :title="__('admin.discount_codes.heading')">
    <x-page-header :title="__('admin.discount_codes.heading')" :subtitle="__('admin.discount_codes.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.discount-codes.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.discount_codes.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.discount_codes.code') }}</x-th>
            <x-th>{{ __('admin.discount_codes.discount_value') }}</x-th>
            <x-th>{{ __('admin.discount_codes.plan') }}</x-th>
            <x-th>{{ __('admin.discount_codes.valid_to') }}</x-th>
            <x-th>{{ __('admin.discount_codes.times_used') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($codes as $code)
            <tr>
                <x-td>
                    <code class="rounded bg-ink-100 px-1.5 py-0.5 text-xs font-semibold text-ink-800" dir="ltr">{{ $code->code }}</code>
                    @if ($code->description)
                        <span class="mt-0.5 block text-xs text-ink-500">{{ $code->description }}</span>
                    @endif
                </x-td>
                <x-td class="tabular font-medium">
                    {{ $code->discount_type === 'percentage'
                        ? number_format((float) $code->discount_value).'%'
                        : number_format((float) $code->discount_value).' '.__('common.currency') }}
                </x-td>
                <x-td>{{ $code->subscriptionPlan?->name ?? __('admin.discount_codes.all_plans') }}</x-td>
                <x-td class="text-sm text-ink-500">
                    {{ $code->valid_to?->translatedFormat('d M Y') ?? '—' }}
                </x-td>
                <x-td class="tabular">
                    {{ $code->times_used }}{{ $code->max_uses ? ' / '.$code->max_uses : '' }}
                </x-td>
                <x-td>
                    @if ($code->isRedeemable())
                        <x-badge tone="success">{{ __('admin.discount_codes.redeemable') }}</x-badge>
                    @else
                        <x-badge tone="neutral">{{ __('admin.discount_codes.expired') }}</x-badge>
                    @endif
                </x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.discount-codes.edit', $code)"
                                   :destroy="route('admin.discount-codes.destroy', $code)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7"/>
        @endforelse

        @if ($codes->hasPages())
            <x-slot:footer>{{ $codes->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
