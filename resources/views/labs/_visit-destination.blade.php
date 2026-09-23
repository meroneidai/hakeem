@php
    $patient = $order->patient;
@endphp

<div class="space-y-3">
    <div>
        <p class="text-sm font-semibold text-ink-900">{{ $patient?->name }}</p>
        @if ($patient?->phone)
            <p class="mt-1 text-sm text-ink-700" dir="ltr">{{ $patient->phone }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @if ($patient->dialUrl())
                    <a href="{{ $patient->dialUrl() }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-800 hover:bg-primary-100">
                        <x-icon name="phone" class="size-3.5"/>
                        {{ __('clinic.lab_orders.call_patient') }}
                    </a>
                @endif
                @if ($patient->whatsappChatUrl())
                    <a href="{{ $patient->whatsappChatUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1.5 text-xs font-medium text-success-800 hover:bg-success-100">
                        <x-icon name="chat" class="size-3.5"/>
                        {{ __('clinic.lab_orders.whatsapp_patient') }}
                    </a>
                @endif
            </div>
        @endif
        @if ($patient?->email)
            <p class="mt-2 break-all text-xs text-ink-500" dir="ltr">{{ $patient->email }}</p>
        @endif
    </div>

    <div>
        <p class="text-xs font-medium text-ink-400">{{ __('clinic.lab_orders.visit_point') }}</p>
        <p class="mt-1 text-sm text-ink-800">{{ $order->patient_home_address ?: __('labs.collection.home') }}</p>
        @if ($order->hasMapPin())
            <p class="mt-1 text-xs text-ink-400" dir="ltr">{{ $order->latitude }}, {{ $order->longitude }}</p>
            <a href="{{ $order->mapsUrl() }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:text-primary-800">
                <x-icon name="map-pin" class="size-4"/>
                {{ __('clinic.lab_orders.directions') }}
            </a>
            <iframe
                title="{{ __('clinic.lab_orders.visit_point') }}"
                class="mt-3 h-56 w-full rounded-xl border-0"
                loading="lazy"
                src="{{ $order->mapsEmbedUrl() }}"
            ></iframe>
        @endif
    </div>
</div>
