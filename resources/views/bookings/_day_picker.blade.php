@props([
    'allowOtherDate' => true,
])

<x-field :label="__('booking.day')" name="date" :hint="__('booking.day_hint')">
    <div class="space-y-3" x-data="{ showOther: false }">
        <div class="flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <template x-for="day in days" :key="day.value">
                <button
                    type="button"
                    class="relative flex min-w-[4.75rem] flex-col items-center rounded-2xl px-3 py-2.5 ring-1 transition"
                    :class="date === day.value
                        ? 'bg-primary-500 text-white ring-primary-500 shadow-[0_8px_24px_rgba(30,64,175,0.16)]'
                        : 'bg-white text-ink-700 ring-ink-200 hover:ring-primary-300'"
                    @click="date = day.value; showOther = false; $dispatch('day-changed')"
                >
                    <span class="text-[11px] font-medium opacity-80" x-text="day.weekday"></span>
                    <span class="mt-0.5 text-lg font-semibold tabular" x-text="day.day"></span>
                    <span class="text-[10px] opacity-70" x-text="day.today ? @js(__('booking.today')) : day.month"></span>
                    <span
                        class="absolute top-1.5 size-1.5 rounded-full"
                        :class="date === day.value ? 'bg-white' : 'bg-accent-500'"
                        x-show="day.today"
                    ></span>
                </button>
            </template>
        </div>

        @if ($allowOtherDate)
            <div>
                <button
                    type="button"
                    class="text-xs font-semibold text-primary-700 hover:underline"
                    @click="showOther = ! showOther"
                    x-text="showOther ? @js(__('booking.hide_other_date')) : @js(__('booking.other_date'))"
                ></button>
                <div x-cloak x-show="showOther" class="mt-2">
                    <input
                        type="date"
                        :value="date"
                        :min="days[0]?.value"
                        dir="ltr"
                        class="field-input max-w-56 focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                        @change="date = $event.target.value; $dispatch('day-changed')"
                    >
                </div>
            </div>
        @endif
    </div>
    <input type="hidden" name="date" :value="date">
</x-field>
