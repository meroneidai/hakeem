@php
    $requiredTotal = collect($checklist)->where('required', true)->count();
    $requiredDone = collect($checklist)->where('required', true)->where('done', true)->count();
    $progress = $requiredTotal > 0 ? (int) round(($requiredDone / $requiredTotal) * 100) : 100;
    $needsPhoneVerify = filled($user->phone) && ! $user->isPhoneVerified();
    $needsEmailVerify = filled($user->email) && ! $user->isEmailVerified();
    $missingPhone = blank($user->phone);
    $missingEmail = blank($user->email);
    $missingCity = blank($user->city_id);
    $openVerify = $openVerify ?? null;
@endphp

<x-layouts.public :title="__('discover.dock.account')" robots="noindex,nofollow">
    <div
        x-data="{
            verify: @js($openVerify),
            openVerify(kind) { this.verify = kind; },
            closeVerify() { this.verify = null; },
        }"
        @keydown.escape.window="closeVerify()"
    >
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
            <x-card :title="__('account.checklist_heading')" :subtitle="__('account.checklist_sub', ['done' => $requiredDone, 'total' => $requiredTotal])">
                <div class="mb-4 h-2 overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-warning-500 transition-all" style="width: {{ $progress }}%"></div>
                </div>
                <ul class="space-y-2">
                    @foreach ($checklist as $item)
                        <li @class([
                            'flex items-start gap-3 rounded-2xl border px-3 py-3',
                            'border-success-200 bg-success-50/60' => $item['done'],
                            'border-warning-300 bg-warning-50 ring-1 ring-warning-200' => ! $item['done'] && $item['required'],
                            'border-ink-200 bg-ink-50/50' => ! $item['done'] && ! $item['required'],
                        ])>
                            <span @class([
                                'mt-0.5 grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold',
                                'bg-success-600 text-white' => $item['done'],
                                'bg-warning-500 text-white' => ! $item['done'] && $item['required'],
                                'bg-ink-300 text-white' => ! $item['done'] && ! $item['required'],
                            ])>
                                @if ($item['done'])
                                    <x-icon name="check" class="size-3.5"/>
                                @else
                                    !
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-ink-900">{{ $item['label'] }}</span>
                                    @if (! $item['required'])
                                        <x-badge tone="neutral">{{ __('common.optional') }}</x-badge>
                                    @endif
                                </span>
                                <span class="mt-0.5 block text-sm text-ink-500">{{ $item['hint'] }}</span>
                            </span>
                            @if (! $item['done'])
                                @if ($item['action'] === 'phone')
                                    <button type="button" class="shrink-0 text-sm font-semibold text-primary-700 hover:underline" @click="openVerify('phone')">
                                        {{ __('account.gaps.fix_now') }}
                                    </button>
                                @elseif ($item['action'] === 'email')
                                    <button type="button" class="shrink-0 text-sm font-semibold text-primary-700 hover:underline" @click="openVerify('email')">
                                        {{ __('account.gaps.fix_now') }}
                                    </button>
                                @elseif ($item['href'])
                                    <a href="{{ $item['href'] }}" class="shrink-0 text-sm font-semibold text-primary-700 hover:underline">
                                        {{ __('account.gaps.complete_now') }}
                                    </a>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <div class="grid gap-3 sm:grid-cols-3">
            <x-card class="p-4">
                <p class="text-xs font-medium text-ink-400">{{ __('account.loyalty.heading') }}</p>
                <p class="mt-1 text-xl font-semibold tabular text-ink-900">{{ number_format((float) $user->wallet_balance, 2) }} {{ __('common.currency') }}</p>
            </x-card>

            <div @class([
                'card p-4',
                'ring-2 ring-warning-300 ring-offset-2' => $missingPhone || $needsPhoneVerify,
            ])>
                <p class="text-xs font-medium text-ink-400">{{ __('auth.phone') }}</p>
                <p class="mt-1 font-semibold text-ink-900" dir="ltr">{{ $user->phone ?: '—' }}</p>
                <p class="mt-1 text-xs {{ $user->isPhoneVerified() ? 'text-success-700' : 'text-warning-700' }}">
                    {{ $user->isPhoneVerified() ? __('auth.phone_verified') : __('auth.phone_unverified') }}
                </p>
                @if ($needsPhoneVerify)
                    <button type="button" class="mt-3 text-sm font-semibold text-primary-700 hover:underline" @click="openVerify('phone')">
                        {{ __('auth.verify_phone_title') }}
                    </button>
                @elseif ($missingPhone)
                    <a href="#field-phone" class="mt-3 inline-block text-sm font-semibold text-primary-700 hover:underline">{{ __('account.gaps.complete_now') }}</a>
                @endif
            </div>

            <div @class([
                'card p-4',
                'ring-2 ring-warning-300 ring-offset-2' => $missingEmail || $needsEmailVerify,
            ])>
                <p class="text-xs font-medium text-ink-400">{{ __('auth.email') }}</p>
                <p class="mt-1 break-all font-semibold text-ink-900" dir="ltr">{{ $user->email ?: '—' }}</p>
                <p class="mt-1 text-xs {{ $user->isEmailVerified() ? 'text-success-700' : 'text-warning-700' }}">
                    @if ($missingEmail)
                        {{ __('account.gaps.email') }}
                    @else
                        {{ $user->isEmailVerified() ? __('auth.email_verified') : __('auth.email_unverified') }}
                    @endif
                </p>
                @if ($needsEmailVerify)
                    <button type="button" class="mt-3 text-sm font-semibold text-primary-700 hover:underline" @click="openVerify('email')">
                        {{ __('auth.verify_email_title') }}
                    </button>
                @elseif ($missingEmail)
                    <a href="#field-email" class="mt-3 inline-block text-sm font-semibold text-primary-700 hover:underline">{{ __('account.gaps.complete_now') }}</a>
                @endif
            </div>
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
            <div id="field-phone" @class(['rounded-2xl p-1 -mx-1', 'ring-2 ring-warning-300' => $missingPhone || $needsPhoneVerify])>
                <x-field :label="__('auth.phone')" name="phone" :hint="__('auth.phone_profile_hint')">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-input class="flex-1" name="phone" type="tel" dir="ltr" inputmode="tel" :value="$user->phone" :placeholder="__('auth.phone_placeholder')"/>
                        @if ($needsPhoneVerify)
                            <button type="button" class="text-sm font-semibold text-primary-700 hover:underline" @click="openVerify('phone')">
                                {{ __('account.gaps.fix_now') }}
                            </button>
                        @endif
                    </div>
                </x-field>
            </div>
            <div id="field-email" @class(['rounded-2xl p-1 -mx-1', 'ring-2 ring-warning-300' => $missingEmail || $needsEmailVerify])>
                <x-field :label="__('auth.email')" name="email" :hint="__('auth.email_profile_hint')">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-input class="flex-1" name="email" type="email" dir="ltr" :value="$user->email"/>
                        @if ($needsEmailVerify)
                            <button type="button" class="text-sm font-semibold text-primary-700 hover:underline" @click="openVerify('email')">
                                {{ __('account.gaps.fix_now') }}
                            </button>
                        @endif
                    </div>
                </x-field>
            </div>
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
            <div id="field-city" @class(['rounded-2xl p-1 -mx-1', 'ring-2 ring-warning-300' => $missingCity])>
                <x-field :label="__('account.city')" name="city_id">
                    <x-select name="city_id" :placeholder="__('discover.doctors.any_city')" :selected="$user->city_id"
                              :options="$cities->mapWithKeys(fn ($city) => [$city->id => $city->name])->all()"/>
                </x-field>
            </div>
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

    @if ($needsPhoneVerify)
        <x-modal show="verify === 'phone'" close="closeVerify()" :title="__('auth.verify_phone_title')">
            <x-slot:subtitle>{{ __('auth.verify_phone_hint') }}</x-slot:subtitle>
            <p class="mb-4 text-sm text-ink-600" dir="ltr">{{ $user->phone }}</p>
            <form method="POST" action="{{ route('account.phone.code') }}" class="mb-4">
                @csrf
                <x-button variant="secondary" class="w-full sm:w-auto">{{ __('auth.send_code') }}</x-button>
            </form>
            <form method="POST" action="{{ route('account.phone.verify') }}" class="space-y-4">
                @csrf
                <x-field :label="__('auth.reset_code')" name="code" required :hint="__('auth.reset_code_hint')">
                    <x-input
                        name="code"
                        dir="ltr"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="8"
                        class="text-center text-xl tracking-[0.35em]"
                        autofocus
                    />
                </x-field>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="closeVerify()">
                        {{ __('common.cancel') }}
                    </button>
                    <x-button variant="accent">{{ __('auth.confirm_code') }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($needsEmailVerify)
        <x-modal show="verify === 'email'" close="closeVerify()" :title="__('auth.verify_email_title')">
            <x-slot:subtitle>{{ __('auth.verify_email_hint') }}</x-slot:subtitle>
            <p class="mb-4 break-all text-sm text-ink-600" dir="ltr">{{ $user->email }}</p>
            <p class="mb-4 rounded-2xl bg-primary-50 px-3 py-2 text-sm text-primary-800">{{ __('auth.verify_email_modal_help') }}</p>
            <form method="POST" action="{{ route('account.email.resend') }}" class="flex flex-wrap justify-end gap-2">
                @csrf
                <button type="button" class="rounded-xl px-4 py-2 text-sm font-medium text-ink-600 hover:bg-ink-100" @click="closeVerify()">
                    {{ __('common.cancel') }}
                </button>
                <x-button variant="accent">{{ __('auth.resend_email') }}</x-button>
            </form>
        </x-modal>
    @endif
    </div>
</x-layouts.public>
