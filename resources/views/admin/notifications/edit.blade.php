@php
    $events = config('hakeem.notification_events');
    $channels = config('hakeem.notification_channels');
@endphp

<x-layouts.admin :title="__('admin.notifications.heading')">
    <x-page-header :title="__('admin.notifications.heading')" :subtitle="__('admin.notifications.subheading')"/>

    <form method="POST" action="{{ route('admin.notifications.update') }}">
        @csrf
        @method('PUT')

        <x-table class="max-w-4xl">
            <x-slot:head>
                <x-th>{{ __('admin.notifications.event') }}</x-th>
                @foreach ($channels as $channel)
                    <x-th class="text-center">{{ __('admin.notifications.channels.'.$channel) }}</x-th>
                @endforeach
            </x-slot:head>

            @foreach ($events as $event)
                <tr>
                    <x-td class="font-medium text-ink-800">{{ __('admin.notifications.events.'.$event) }}</x-td>
                    @foreach ($channels as $channel)
                        <x-td class="text-center">
                            <input type="checkbox"
                                   name="matrix[{{ $event }}][{{ $channel }}]"
                                   value="1"
                                   @checked(in_array($channel, $matrix[$event] ?? [], true))
                                   class="size-4 rounded border-ink-300 text-primary-600 focus:ring-primary-300">
                        </x-td>
                    @endforeach
                </tr>
            @endforeach

            <x-slot:footer>
                <div class="flex justify-end">
                    <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
                </div>
            </x-slot:footer>
        </x-table>
    </form>
</x-layouts.admin>
