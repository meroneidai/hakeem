<x-layouts.public :title="__('discover.dock.account')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('discover.dock.account')" :subtitle="__('account.hub.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.dock.account') }}</span>
        </x-slot:crumbs>
        @if (auth()->user()->isClinicStaff() || auth()->user()->isInternalStaff())
            <x-slot:actions>
                @if (auth()->user()->isInternalStaff())
                    <x-button :href="route('admin.dashboard')" variant="accent" size="sm">{{ __('account.dashboard') }}</x-button>
                @else
                    <x-button :href="route('clinic.dashboard')" variant="accent" size="sm">{{ __('account.dashboard') }}</x-button>
                @endif
            </x-slot:actions>
        @endif
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl space-y-5 px-4 py-8">
        <div class="grid gap-3 sm:grid-cols-2">
            <a href="#profile-form" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-primary-50 text-primary-700">
                    <x-icon name="user" class="size-5"/>
                </span>
                <span>
                    <span class="block font-semibold text-ink-900">{{ __('account.profile') }}</span>
                    <span class="mt-0.5 block text-sm text-ink-500">{{ __('account.hub.profile_hint') }}</span>
                </span>
            </a>
            <a href="{{ route('appointments.index') }}" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700">
                    <x-icon name="calendar" class="size-5"/>
                </span>
                <span>
                    <span class="block font-semibold text-ink-900">{{ __('booking.my_appointments') }}</span>
                    <span class="mt-0.5 block text-sm text-ink-500">{{ __('account.hub.appointments_hint') }}</span>
                </span>
            </a>
            <a href="{{ route('records.index') }}" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-success-50 text-success-800">
                    <x-icon name="shield" class="size-5"/>
                </span>
                <span>
                    <span class="block font-semibold text-ink-900">{{ __('records.heading') }}</span>
                    <span class="mt-0.5 block text-sm text-ink-500">{{ __('account.hub.records_hint') }}</span>
                </span>
            </a>
            <a href="{{ route('labs.index') }}" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-ink-50 text-ink-700">
                    <x-icon name="beaker" class="size-5"/>
                </span>
                <span>
                    <span class="block font-semibold text-ink-900">{{ __('labs.checkout.my_orders') }}</span>
                    <span class="mt-0.5 block text-sm text-ink-500">{{ __('account.hub.labs_hint') }}</span>
                </span>
            </a>
        </div>
        @if (session('profile_complete'))
            <x-alert tone="success">{{ __('account.complete') }}</x-alert>
        @elseif (! $complete && ! $user->isInternalStaff())
            <x-alert tone="warning">
                <a href="#profile-form" class="font-medium underline-offset-2 hover:underline">{{ __('account.incomplete') }}</a>
            </x-alert>
        @endif

        <div class="grid gap-3 sm:grid-cols-3">
            <x-card class="p-4">
                <p class="text-xs font-medium text-ink-400">{{ __('account.loyalty.heading') }}</p>
                <p class="mt-1 text-xl font-semibold tabular text-ink-900">{{ number_format((float) $user->wallet_balance, 2) }} {{ __('common.currency') }}</p>
            </x-card>
            <x-card class="p-4">
                <p class="text-xs font-medium text-ink-400">{{ __('auth.phone') }}</p>
                <p class="mt-1 font-semibold text-ink-900" dir="ltr">{{ $user->phone ?: '—' }}</p>
                <p class="mt-1 text-xs {{ $user->isPhoneVerified() ? 'text-success-700' : 'text-warning-700' }}">
                    {{ $user->isPhoneVerified() ? __('auth.phone_verified') : __('auth.phone_unverified') }}
                </p>
            </x-card>
            <x-card class="p-4">
                <p class="text-xs font-medium text-ink-400">{{ __('auth.email') }}</p>
                <p class="mt-1 break-all font-semibold text-ink-900" dir="ltr">{{ $user->email ?: '—' }}</p>
                <p class="mt-1 text-xs {{ $user->isEmailVerified() ? 'text-success-700' : 'text-warning-700' }}">
                    {{ $user->isEmailVerified() ? __('auth.email_verified') : __('auth.email_unverified') }}
                </p>
            </x-card>
        </div>

        <x-card :title="__('account.loyalty.heading')">
            <p class="text-sm text-ink-500">{{ __('account.loyalty.share_hint') }}</p>
            <div class="mt-3">
                <x-copy-field :value="$referralUrl"/>
            </div>
            @if ($campaign)
                <p class="mt-3 rounded-xl bg-primary-50 px-3 py-2 text-sm text-primary-800">{{ $campaign['body'] }}</p>
            @endif
            <ul class="mt-4 divide-y divide-ink-100 text-sm">
                @forelse ($ledgers as $ledger)
                    <li class="flex justify-between gap-3 py-2">
                        <span>{{ $ledger->type->label() }}@if ($ledger->note) · {{ $ledger->note }}@endif</span>
                        <span class="tabular {{ $ledger->amount >= 0 ? 'text-success-700' : 'text-danger-600' }}">
                            {{ $ledger->amount >= 0 ? '+' : '' }}{{ number_format((float) $ledger->amount, 2) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-ink-400">{{ __('account.loyalty.empty') }}</li>
                @endforelse
            </ul>
        </x-card>

        @if (filled($user->phone) && ! $user->isPhoneVerified())
            <x-card :title="__('auth.verify_phone_title')">
                <p class="text-sm text-ink-500">{{ __('auth.verify_phone_hint') }}</p>
                <form method="POST" action="{{ route('account.phone.code') }}" class="mt-3">
                    @csrf
                    <x-button variant="secondary" size="sm">{{ __('auth.send_code') }}</x-button>
                </form>
                <form method="POST" action="{{ route('account.phone.verify') }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <x-field :label="__('auth.reset_code')" name="code" required class="min-w-40 flex-1">
                        <x-input name="code" dir="ltr" inputmode="numeric" autocomplete="one-time-code"/>
                    </x-field>
                    <x-button variant="accent" size="sm">{{ __('auth.confirm_code') }}</x-button>
                </form>
            </x-card>
        @endif

        @if (filled($user->email) && ! $user->isEmailVerified())
            <x-card :title="__('auth.verify_email_title')">
                <p class="text-sm text-ink-500">{{ __('auth.verify_email_hint') }}</p>
                <form method="POST" action="{{ route('account.email.resend') }}" class="mt-3">
                    @csrf
                    <x-button variant="secondary" size="sm">{{ __('auth.resend_email') }}</x-button>
                </form>
            </x-card>
        @endif

        <x-card :title="__('booking.my_appointments')">
            <ul class="divide-y divide-ink-100 text-sm">
                @forelse ($appointments as $appointment)
                    <li class="flex justify-between gap-3 py-2">
                        <span>
                            {{ $appointment->clinic?->name }}
                            <span class="text-ink-400">·</span>
                            {{ optional($appointment->scheduled_at)->format('Y-m-d H:i') }}
                        </span>
                        <span>{{ $appointment->status->label() }}</span>
                    </li>
                @empty
                    <li class="py-3 text-ink-400">{{ __('booking.empty') }}</li>
                @endforelse
            </ul>
            <a href="{{ route('appointments.index') }}" class="mt-3 inline-block text-sm font-medium text-primary-700 hover:underline">{{ __('account.all_appointments') }}</a>
        </x-card>

        <x-card :title="__('records.heading')">
            <p class="text-sm text-ink-500">{{ __('account.hub.records_hint') }}</p>
            <a href="{{ route('records.index') }}" class="mt-3 inline-block text-sm font-medium text-primary-700 hover:underline">{{ __('account.hub.open_records') }}</a>
        </x-card>

        <form id="profile-form" method="POST" action="{{ route('account.update') }}" class="card space-y-4 p-6">
            @csrf
            @method('PUT')
            <x-field :label="__('auth.name')" name="name" required>
                <x-input name="name" :value="$user->name"/>
            </x-field>
            <x-field :label="__('auth.phone')" name="phone" :hint="__('auth.phone_profile_hint')">
                <x-input name="phone" type="tel" dir="ltr" inputmode="tel" :value="$user->phone" :placeholder="__('auth.phone_placeholder')"/>
            </x-field>
            <x-field :label="__('auth.email')" name="email" :hint="__('auth.email_profile_hint')">
                <x-input name="email" type="email" dir="ltr" :value="$user->email"/>
            </x-field>
            <x-field :label="__('account.birth')" name="date_of_birth" :hint="__('account.birth_hint')">
                @php
                    $savedBirth = old('date_of_birth', $user->date_of_birth?->format('Y-m-d'));
                    $birthYear = (string) old('birth_year', $savedBirth ? substr($savedBirth, 0, 4) : '');
                    $birthMonth = (string) old('birth_month', $savedBirth ? (int) substr($savedBirth, 5, 2) : '');
                    $birthDay = (string) old('birth_day', $savedBirth ? (int) substr($savedBirth, 8, 2) : '');
                @endphp
                <div class="grid grid-cols-3 gap-2">
                    <x-select name="birth_day" :placeholder="__('account.birth_day')" :selected="$birthDay"
                              :options="collect(range(1, 31))->mapWithKeys(fn ($day) => [$day => $day])->all()"/>
                    <x-select name="birth_month" :placeholder="__('account.birth_month')" :selected="$birthMonth"
                              :options="collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => $month])->all()"/>
                    <x-select name="birth_year" :placeholder="__('account.birth_year')" :selected="$birthYear"
                              :options="collect(range(1925, (int) now()->year - 1))->reverse()->mapWithKeys(fn ($year) => [$year => $year])->all()"/>
                </div>
                @if ($user->date_of_birth)
                    <p class="mt-1 text-xs text-ink-500">{{ __('account.saved_birth', ['date' => $user->date_of_birth->format('Y-m-d')]) }}</p>
                @endif
            </x-field>
            <x-field :label="__('account.gender')" name="gender">
                <x-select name="gender" :placeholder="__('common.optional')" :selected="$user->gender"
                          :options="['male' => __('account.male'), 'female' => __('account.female')]"/>
            </x-field>
            <x-field :label="__('account.city')" name="city_id">
                <x-select name="city_id" :placeholder="__('discover.doctors.any_city')" :selected="$user->city_id"
                          :options="$cities->mapWithKeys(fn ($city) => [$city->id => $city->name])->all()"/>
            </x-field>
            <x-insurance-select :providers="$insuranceProviders" :selected="$user->insurance_provider_id"/>
            <x-field :label="__('common.language')" name="preferred_language" required>
                <x-select name="preferred_language" :options="collect(config('hakeem.locales'))->map(fn ($locale) => $locale['native'])->all()" :selected="$user->preferred_language"/>
            </x-field>
            <x-checkbox name="notify_email" :label="__('account.notify_email')" :checked="$user->notify_email"/>
            <x-checkbox name="notify_sms" :label="__('account.notify_sms')" :checked="$user->notify_sms"/>
            <x-checkbox name="notify_push" :label="__('account.notify_push')" :checked="$user->notify_push"/>
            <x-button variant="accent">{{ __('account.save') }}</x-button>
        </form>
    </div>
</x-layouts.public>
