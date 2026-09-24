<x-layouts.admin :title="$clinic->name">
    <x-page-header :title="$clinic->name" :subtitle="$clinic->owner?->name">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.clinics.update', $clinic) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                @method('PUT')
                <select name="verification_status" class="field-input">
                    @foreach (App\Enums\VerificationStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($clinic->verification_status === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <x-input name="rejection_reason" :value="$clinic->rejection_reason" :placeholder="__('admin.clinics.rejection')"/>
                <x-button size="sm" variant="accent">{{ __('common.save_changes') }}</x-button>
            </form>
            <x-row-actions :destroy="route('admin.clinics.destroy', $clinic)"/>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card :title="__('admin.clinics.detail')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-ink-500">{{ __('auth.phone') }}</dt><dd dir="ltr">{{ $clinic->phone }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">{{ __('admin.nav.plans') }}</dt><dd>{{ $clinic->plan?->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">{{ __('admin.users.bookings') }}</dt><dd>{{ $clinic->bookings_count }}</dd></div>
            </dl>
        </x-card>
        <x-card :title="__('clinic.nav.addresses')">
            <ul class="space-y-2 text-sm">
                @foreach ($clinic->addresses as $address)
                    <li>{{ $address->displayName() }} · {{ $address->city?->name }}
                        @if ($address->latitude)
                            <span class="text-ink-400" dir="ltr">{{ $address->latitude }}, {{ $address->longitude }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
        <x-card :title="__('clinic.profile.modules')" class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.clinics.update', $clinic) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <input type="hidden" name="verification_status" value="{{ $clinic->verification_status->value }}">
                <input type="hidden" name="modules_present" value="1">
                @foreach ($modules as $module)
                    <x-checkbox
                        name="modules[]"
                        :value="$module->value"
                        :label="$module->label()"
                        :hint="$module->hint()"
                        :checked="in_array($module->value, $clinic->enabledModuleValues(), true)"
                        :withHidden="false"
                    />
                @endforeach
                <x-button size="sm" variant="accent">{{ __('common.save_changes') }}</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
