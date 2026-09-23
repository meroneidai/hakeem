<x-layouts.clinic :title="__('clinic.care.issue')">
    <x-page-header :title="__('clinic.care.issue')" :subtitle="$booking->patient?->name">
        <x-slot:actions>
            <x-button :href="route('clinic.queue.index', ['date' => $booking->scheduled_at?->toDateString()])" variant="ghost">
                {{ __('common.back') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <p class="font-semibold text-ink-900">{{ $booking->patient?->name }}</p>
        <p class="mt-1 text-sm text-ink-500">
            {{ $booking->doctor?->name }}
            · {{ $booking->serviceType?->name }}
            · {{ $booking->scheduled_at?->format('Y-m-d H:i') }}
        </p>
    </x-card>

    <form method="POST" action="{{ route('clinic.care.store') }}" x-data="{
        type: '{{ old('type', 'prescription') }}',
        medications: {{ \Illuminate\Support\Js::from(old('medications', [['name' => '', 'dose' => '', 'frequency' => '', 'duration' => '', 'notes' => '']])) }},
        addRow() { this.medications.push({name: '', dose: '', frequency: '', duration: '', notes: ''}) }
    }">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->id }}">

        <x-card class="space-y-4">
            <x-field :label="__('clinic.care.type')" name="type" required>
                <select name="type" x-model="type" class="field-input">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </x-field>

            <x-field :label="__('clinic.care.title')" name="title">
                <x-input name="title" :value="old('title')"/>
            </x-field>

            <div x-show="type === 'prescription'" x-cloak class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink-900">{{ __('clinic.care.medications') }}</h2>
                    <button type="button" class="text-sm font-medium text-primary-700" @click="addRow()">{{ __('clinic.care.add_medication') }}</button>
                </div>
                <template x-for="(row, index) in medications" :key="index">
                    <div class="grid gap-2 rounded-xl border border-ink-100 p-3 sm:grid-cols-2">
                        <input type="text" :name="'medications['+index+'][name]'" x-model="row.name" class="field-input" placeholder="{{ __('clinic.care.med_name') }}">
                        <input type="text" :name="'medications['+index+'][dose]'" x-model="row.dose" class="field-input" placeholder="{{ __('clinic.care.med_dose') }}">
                        <input type="text" :name="'medications['+index+'][frequency]'" x-model="row.frequency" class="field-input" placeholder="{{ __('clinic.care.med_frequency') }}">
                        <input type="text" :name="'medications['+index+'][duration]'" x-model="row.duration" class="field-input" placeholder="{{ __('clinic.care.med_duration') }}">
                        <input type="text" :name="'medications['+index+'][notes]'" x-model="row.notes" class="field-input sm:col-span-2" placeholder="{{ __('clinic.care.med_notes') }}">
                    </div>
                </template>
            </div>

            <div x-show="type === 'sick_leave'" x-cloak class="grid gap-3 sm:grid-cols-2">
                <x-field :label="__('clinic.care.valid_from')" name="valid_from" required>
                    <x-input type="date" name="valid_from" :value="old('valid_from', now()->toDateString())"/>
                </x-field>
                <x-field :label="__('clinic.care.valid_until')" name="valid_until" required>
                    <x-input type="date" name="valid_until" :value="old('valid_until', now()->addDays(3)->toDateString())"/>
                </x-field>
            </div>

            <x-field :label="__('clinic.care.body')" name="body">
                <x-textarea name="body" rows="4" :value="old('body')"/>
            </x-field>

            <x-button variant="accent">{{ __('clinic.care.submit') }}</x-button>
        </x-card>
    </form>
</x-layouts.clinic>
