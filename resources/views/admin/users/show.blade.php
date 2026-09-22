<x-layouts.admin :title="$user->name">
    <x-page-header :title="$user->name" :subtitle="$user->phone">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex gap-2">
                @csrf
                @method('PUT')
                @unless ($user->isPhoneVerified())
                    <input type="hidden" name="action" value="verify_phone">
                    <x-button size="sm" variant="secondary">{{ __('admin.users.mark_verified') }}</x-button>
                @endunless
            </form>
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="toggle_active">
                <x-button size="sm" variant="secondary">{{ $user->is_active ? __('common.deactivate') : __('common.activate') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card :title="__('admin.users.detail')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('auth.email') }}</dt><dd dir="ltr">{{ $user->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.users.phone_status') }}</dt><dd>{{ $user->isPhoneVerified() ? __('admin.users.phone_verified') : __('admin.users.phone_unverified') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.users.app') }}</dt><dd>{{ $user->hasInstalledApp() ? __('admin.users.app_yes') : __('admin.users.app_no') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.users.last_login') }}</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.cities.heading') }}</dt><dd>{{ $user->city?->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.loyalty.balance') }}</dt><dd class="tabular">{{ number_format((float) $user->wallet_balance, 2) }} {{ __('common.currency') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.loyalty.referrals') }}</dt><dd>{{ $user->referrals_count }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.loyalty.referred_by') }}</dt><dd>{{ $user->referredBy?->name ?? '—' }}</dd></div>
            </dl>
            <div class="mt-4">
                <p class="mb-2 text-xs font-medium text-ink-500">{{ __('admin.loyalty.copy_link') }}</p>
                <x-copy-field :value="$referralUrl"/>
            </div>
        </x-card>
        <x-card class="lg:col-span-2" :title="__('admin.users.bookings')">
            <ul class="divide-y divide-ink-100 text-sm">
                @forelse ($bookings as $booking)
                    <li class="flex justify-between gap-3 py-2">
                        <span>{{ $booking->clinic?->name }} · {{ $booking->doctor?->name }}</span>
                        <span class="text-ink-400">{{ $booking->status->label() }}</span>
                    </li>
                @empty
                    <li class="py-3 text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
        <x-card :title="__('admin.loyalty.adjust')" class="lg:col-span-3">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-3 sm:grid-cols-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="adjust_wallet">
                <x-field :label="__('admin.loyalty.amount')" name="amount" required>
                    <x-input name="amount" type="number" step="1" dir="ltr" placeholder="100"/>
                </x-field>
                <x-field :label="__('admin.loyalty.note')" name="note">
                    <x-input name="note" maxlength="190"/>
                </x-field>
                <div class="flex items-end">
                    <x-button variant="secondary">{{ __('admin.loyalty.credit') }}</x-button>
                </div>
            </form>
            <ul class="mt-4 divide-y divide-ink-100 text-sm">
                @forelse ($ledgers as $ledger)
                    <li class="flex justify-between gap-3 py-2">
                        <span>{{ $ledger->type->label() }}@if ($ledger->note) · {{ $ledger->note }}@endif</span>
                        <span class="tabular {{ $ledger->amount >= 0 ? 'text-success-700' : 'text-danger-600' }}">
                            {{ $ledger->amount >= 0 ? '+' : '' }}{{ number_format((float) $ledger->amount, 2) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.admin>
