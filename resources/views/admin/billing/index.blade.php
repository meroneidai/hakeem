<x-layouts.admin :title="__('admin.billing.heading')">
    <x-page-header :title="__('admin.billing.heading')" :subtitle="__('admin.billing.subheading')"/>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('admin.billing.subscription_total')" :value="number_format($totals['subscription_amount']).' '.__('common.currency')"/>
        <x-stat :label="__('admin.billing.period_amount')" :value="number_format($totals['period_amount']).' '.__('common.currency')"/>
        <x-stat :label="__('admin.billing.paid_bookings')" :value="$totals['paid_bookings']"/>
        <x-stat :label="__('admin.billing.completed_bookings')" :value="$totals['completed_bookings']"/>
    </div>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.nav.clinics') }}</x-th>
            <x-th>{{ __('admin.nav.plans') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('admin.billing.amount') }}</x-th>
            <x-th>{{ __('admin.billing.period') }}</x-th>
        </x-slot:head>
        @forelse ($subscriptions as $subscription)
            <tr>
                <x-td>{{ $subscription->clinic?->name }}</x-td>
                <x-td>{{ $subscription->plan?->name }}</x-td>
                <x-td>{{ $subscription->status->value }}</x-td>
                <x-td>{{ number_format((float) $subscription->amount) }} {{ __('common.currency') }}</x-td>
                <x-td class="text-xs text-ink-500">{{ $subscription->current_period_start?->toDateString() }} → {{ $subscription->current_period_end?->toDateString() ?? '—' }}</x-td>
            </tr>
        @empty
            <x-empty-state colspan="5"/>
        @endforelse
        @if ($subscriptions->hasPages())
            <x-slot:footer>{{ $subscriptions->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
