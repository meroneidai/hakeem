<x-layouts.auth :title="__('clinic.register.title')" :heading="__('clinic.register.heading')" :subheading="__('clinic.register.subtitle')" wide>
    @php
        $defaultPlanId = old('subscription_plan_id', $plans->firstWhere('is_default_free')?->id ?? $plans->first()?->id);
    @endphp

    <form method="POST" action="{{ route('register.clinic') }}" class="space-y-6"
          x-data="{
              type: '{{ old('clinic_type', 'solo') }}',
              governorateId: '{{ old('governorate_id') }}',
              cityId: '{{ old('city_id') }}',
              cities: {{ \Illuminate\Support\Js::from($citiesByGovernorate) }},
              planId: '{{ $defaultPlanId }}',
              get cityOptions() { return this.cities[this.governorateId] || [] }
          }">
        @csrf

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-ink-700">{{ __('clinic.register.type_label') }} <span class="text-danger-500">*</span></legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="card cursor-pointer p-4" :class="type === 'solo' && 'ring-2 ring-primary-400'">
                    <input type="radio" name="clinic_type" value="solo" class="sr-only" x-model="type" @checked(old('clinic_type', 'solo') === 'solo')>
                    <span class="block font-semibold text-ink-900">{{ __('clinic.register.solo') }}</span>
                    <span class="mt-1 block text-xs text-ink-500">{{ __('clinic.register.solo_hint') }}</span>
                </label>
                <label class="card cursor-pointer p-4" :class="type === 'multi' && 'ring-2 ring-primary-400'">
                    <input type="radio" name="clinic_type" value="multi" class="sr-only" x-model="type" @checked(old('clinic_type') === 'multi')>
                    <span class="block font-semibold text-ink-900">{{ __('clinic.register.multi') }}</span>
                    <span class="mt-1 block text-xs text-ink-500">{{ __('clinic.register.multi_hint') }}</span>
                </label>
            </div>
            @error('clinic_type')
                <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
            @enderror
        </fieldset>

        <div>
            <h2 class="mb-3 text-sm font-semibold text-ink-800">{{ __('clinic.register.account') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('auth.name')" name="name" required>
                    <x-input name="name" autocomplete="name" autofocus/>
                </x-field>
                <x-field :label="__('auth.email')" name="email" required>
                    <x-input name="email" type="email" dir="ltr" autocomplete="email"/>
                </x-field>
                <x-field :label="__('auth.phone')" name="phone" required :hint="__('auth.phone_hint')" class="sm:col-span-2">
                    <x-input name="phone" type="tel" dir="ltr" inputmode="tel" autocomplete="username"
                             :placeholder="__('auth.phone_placeholder')"/>
                </x-field>
                <x-field :label="__('auth.password_label')" name="password" required>
                    <x-input name="password" type="password" autocomplete="new-password"/>
                </x-field>
                <x-field :label="__('auth.password_confirmation')" name="password_confirmation" required>
                    <x-input name="password_confirmation" type="password" autocomplete="new-password"/>
                </x-field>
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-sm font-semibold text-ink-800">{{ __('clinic.register.clinic') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('common.name_ar')" name="clinic_name_ar" required>
                    <x-input name="clinic_name_ar"/>
                </x-field>
                <x-field :label="__('common.name_en')" name="clinic_name_en" required>
                    <x-input name="clinic_name_en" dir="ltr"/>
                </x-field>
                <x-field :label="__('clinic.register.specialty')" name="specialty_id" required class="sm:col-span-2">
                    <x-select name="specialty_id" :placeholder="__('clinic.register.specialty')"
                              :options="$specialties->pluck('name', 'id')->all()"/>
                </x-field>
                <template x-if="type === 'solo'">
                    <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                        <x-field :label="__('clinic.register.doctor_name_ar')" name="doctor_name_ar">
                            <x-input name="doctor_name_ar"/>
                        </x-field>
                        <x-field :label="__('clinic.register.doctor_name_en')" name="doctor_name_en">
                            <x-input name="doctor_name_en" dir="ltr"/>
                        </x-field>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-sm font-semibold text-ink-800">{{ __('clinic.register.location') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('clinic.register.governorate')" name="governorate_id" required>
                    <select name="governorate_id" id="governorate_id" x-model="governorateId" @change="cityId = ''"
                            class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="">{{ __('clinic.register.governorate') }}</option>
                        @foreach ($governorates as $governorate)
                            <option value="{{ $governorate->id }}">{{ $governorate->name }}</option>
                        @endforeach
                    </select>
                    @error('governorate_id')
                        <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
                    @enderror
                </x-field>
                <x-field :label="__('clinic.register.city')" name="city_id" required>
                    <select name="city_id" id="city_id" x-model="cityId"
                            class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100" :disabled="!cityOptions.length">
                        <option value="">{{ __('clinic.register.city') }}</option>
                        <template x-for="city in cityOptions" :key="city.id">
                            <option :value="city.id" x-text="city.name" :selected="String(city.id) === String(cityId)"></option>
                        </template>
                    </select>
                    @error('city_id')
                        <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
                    @enderror
                </x-field>
                <x-field :label="__('clinic.register.address_line')" name="address_line" required class="sm:col-span-2">
                    <x-input name="address_line"/>
                </x-field>
            </div>
        </div>

        <div>
            <h2 class="mb-1 text-sm font-semibold text-ink-800">{{ __('clinic.register.modules') }}</h2>
            <p class="mb-3 text-xs text-ink-500">{{ __('clinic.register.modules_hint') }}</p>
            <div class="space-y-3">
                @foreach ($modules as $module)
                    <label class="card flex cursor-pointer items-start gap-3 p-4">
                        <input
                            type="checkbox"
                            name="modules[]"
                            value="{{ $module->value }}"
                            class="mt-1 size-4 rounded border-ink-300 text-primary-600"
                            @checked(in_array($module->value, old('modules', []), true))
                        >
                        <span>
                            <span class="block font-semibold text-ink-900">{{ $module->label() }}</span>
                            <span class="mt-1 block text-xs text-ink-500">{{ $module->hint() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="mb-1 text-sm font-semibold text-ink-800">{{ __('clinic.register.plan') }}</h2>
            <p class="mb-3 text-xs text-ink-500">{{ __('clinic.register.plan_hint') }}</p>
            <input type="hidden" name="subscription_plan_id" :value="planId">
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($plans as $plan)
                    <button type="button" class="card p-4 text-start" :class="String(planId) === '{{ $plan->id }}' && 'ring-2 ring-primary-400'"
                            @click="planId = '{{ $plan->id }}'">
                        <span class="block font-semibold text-ink-900">{{ $plan->name }}</span>
                        <span class="mt-1 block text-sm text-primary-700">
                            {{ $plan->is_default_free ? __('common.unlimited') : number_format((float) $plan->monthly_price).' '.__('common.currency').__('common.per_month') }}
                        </span>
                        <span class="mt-2 block text-xs text-ink-500">{{ $plan->description }}</span>
                    </button>
                @endforeach
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-field :label="__('clinic.subscription.cycle')" name="billing_cycle">
                    <x-select name="billing_cycle" :selected="old('billing_cycle', 'monthly')" :options="[
                        'monthly' => __('clinic.register.billing_monthly'),
                        'yearly' => __('clinic.register.billing_yearly'),
                    ]"/>
                </x-field>
                <x-field :label="__('clinic.register.discount')" name="discount_code">
                    <x-input name="discount_code" dir="ltr"/>
                </x-field>
            </div>
        </div>

        <x-button variant="accent" class="w-full" size="lg">{{ __('clinic.register.submit') }}</x-button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-500">
        {{ __('clinic.register.patient_instead') }}
        —
        <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline">{{ __('auth.register') }}</a>
    </p>
</x-layouts.auth>
