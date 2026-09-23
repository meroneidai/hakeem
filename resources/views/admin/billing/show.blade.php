<x-layouts.admin :title="$subscription->invoice_number">
    <x-page-header :title="$subscription->invoice_number" :subtitle="$subscription->clinic?->name">
        <x-slot:actions>
            <x-button :href="route('admin.billing')" variant="secondary">{{ __('common.back') }}</x-button>
            <form method="POST" action="{{ route('admin.billing.invoice', $subscription) }}">
                @csrf
                <x-button variant="primary">{{ __('admin.billing.send_invoice') }}</x-button>
            </form>
            <form method="POST" action="{{ route('admin.billing.reminder', $subscription) }}">
                @csrf
                <x-button variant="secondary">{{ __('admin.billing.send_reminder') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('admin.billing.amount')" :value="number_format((float) $subscription->amount).' '.__('common.currency')"/>
        <x-stat :label="__('admin.billing.payment_status')" :value="$subscription->payment_status->label()"/>
        <x-stat :label="__('admin.billing.subscription_status')" :value="$subscription->status->label()"/>
        <x-stat :label="__('admin.billing.plan')" :value="$subscription->plan?->name"/>
    </div>

    <div class="mb-5 grid gap-5 lg:grid-cols-2">
        <x-card :title="__('admin.billing.clinic_details')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.nav.clinics') }}</dt><dd class="font-medium">{{ $subscription->clinic?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('auth.name') }}</dt><dd>{{ $subscription->clinic?->owner?->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('pages.contact.email') }}</dt><dd dir="ltr">{{ $subscription->clinic?->email ?? $subscription->clinic?->owner?->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('pages.contact.phone') }}</dt><dd dir="ltr">{{ $subscription->clinic?->phone ?? $subscription->clinic?->owner?->phone ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card :title="__('admin.billing.payment_details')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.invoice_number') }}</dt><dd class="font-mono" dir="ltr">{{ $subscription->invoice_number }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.cycle') }}</dt><dd>{{ $subscription->billing_cycle->label() }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.payment_method') }}</dt><dd>{{ $subscription->payment_method?->label() ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.payment_reference') }}</dt><dd dir="ltr">{{ $subscription->payment_reference ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.paid_at') }}</dt><dd class="tabular">{{ $subscription->paid_at?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.due_at') }}</dt><dd class="tabular">{{ $subscription->due_at?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.invoice_sent_at') }}</dt><dd class="tabular">{{ $subscription->invoice_sent_at?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.billing.reminder_sent_at') }}</dt><dd class="tabular">{{ $subscription->reminder_sent_at?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.nav.discount_codes') }}</dt><dd>{{ $subscription->discountCode?->code ?? '—' }}</dd></div>
            </dl>
        </x-card>
    </div>

    <form method="POST" action="{{ route('admin.billing.update', $subscription) }}">
        @csrf
        @method('PUT')
        <x-card :title="__('admin.billing.manage')" class="max-w-4xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.billing.plan')" name="subscription_plan_id" required>
                    <x-select name="subscription_plan_id" :options="$plans" :selected="old('subscription_plan_id', $subscription->subscription_plan_id)"/>
                </x-field>
                <x-field :label="__('admin.billing.cycle')" name="billing_cycle" required>
                    <x-select name="billing_cycle" :options="$cycles" :selected="old('billing_cycle', $subscription->billing_cycle->value)"/>
                </x-field>
                <x-field :label="__('admin.billing.subscription_status')" name="status" required>
                    <x-select name="status" :options="$statuses" :selected="old('status', $subscription->status->value)"/>
                </x-field>
                <x-field :label="__('admin.billing.payment_status')" name="payment_status" required>
                    <x-select name="payment_status" :options="$paymentStatuses" :selected="old('payment_status', $subscription->payment_status->value)"/>
                </x-field>
                <x-field :label="__('admin.billing.amount')" name="amount" required>
                    <x-input name="amount" type="number" min="0" step="0.01" :value="old('amount', $subscription->amount)" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.billing.payment_method')" name="payment_method">
                    <x-select name="payment_method" :options="$paymentMethods" :placeholder="__('common.optional')" :selected="old('payment_method', $subscription->payment_method?->value)"/>
                </x-field>
                <x-field :label="__('admin.billing.payment_reference')" name="payment_reference">
                    <x-input name="payment_reference" :value="$subscription->payment_reference" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.nav.discount_codes')" name="discount_code_id">
                    <x-select name="discount_code_id" :options="$discountCodes" :placeholder="__('common.none')" :selected="old('discount_code_id', $subscription->discount_code_id)"/>
                </x-field>
                <x-field :label="__('admin.billing.paid_at')" name="paid_at">
                    <x-input name="paid_at" type="datetime-local" :value="old('paid_at', $subscription->localInput($subscription->paid_at))" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.billing.due_at')" name="due_at">
                    <x-input name="due_at" type="datetime-local" :value="old('due_at', $subscription->localInput($subscription->due_at))" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.billing.period_start')" name="current_period_start">
                    <x-input name="current_period_start" type="datetime-local" :value="old('current_period_start', $subscription->localInput($subscription->current_period_start))" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.billing.period_end')" name="current_period_end">
                    <x-input name="current_period_end" type="datetime-local" :value="old('current_period_end', $subscription->localInput($subscription->current_period_end))" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.billing.notes')" name="notes" class="sm:col-span-2">
                    <x-textarea name="notes" :value="$subscription->notes" rows="4"/>
                </x-field>
            </div>
            <x-slot:footer>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
