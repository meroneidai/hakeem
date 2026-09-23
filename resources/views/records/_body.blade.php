<x-card class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <x-badge :tone="$document->type->tone()">{{ $document->type->label() }}</x-badge>
            <p class="mt-2 text-lg font-semibold text-ink-900">{{ $document->title }}</p>
            <p class="mt-1 text-sm text-ink-500">{{ $document->clinic?->name }}</p>
            @if ($document->doctor)
                <p class="text-sm text-ink-500">{{ $document->doctor->name }}</p>
            @endif
        </div>
        <div class="text-end">
            <p class="text-xs font-medium text-ink-400">{{ __('records.verification') }}</p>
            <p class="mt-1 font-mono text-lg font-semibold tracking-wide text-ink-900" dir="ltr">{{ $document->verification_code }}</p>
            <p class="mt-1 text-xs text-ink-400">{{ $document->issued_at?->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    @if ($document->valid_from || $document->valid_until)
        <p class="text-sm text-ink-600">
            {{ __('records.valid_range', [
                'from' => optional($document->valid_from)->format('Y-m-d') ?: '—',
                'until' => optional($document->valid_until)->format('Y-m-d') ?: '—',
            ]) }}
        </p>
    @endif

    @if ($document->body)
        <p class="whitespace-pre-line text-sm leading-6 text-ink-700">{{ $document->body }}</p>
    @endif

    @if ($document->medications())
        <div>
            <h2 class="text-sm font-semibold text-ink-900">{{ __('records.medications') }}</h2>
            <ul class="mt-2 divide-y divide-ink-100 text-sm">
                @foreach ($document->medications() as $medication)
                    <li class="py-2">
                        <p class="font-medium text-ink-800">{{ $medication['name'] ?? '' }}</p>
                        <p class="text-xs text-ink-500">
                            {{ collect([$medication['dose'] ?? null, $medication['frequency'] ?? null, $medication['duration'] ?? null])->filter()->join(' · ') }}
                        </p>
                        @if (! empty($medication['notes']))
                            <p class="mt-0.5 text-xs text-ink-400">{{ $medication['notes'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($document->resultRows())
        <div>
            <h2 class="text-sm font-semibold text-ink-900">{{ __('records.results') }}</h2>
            <ul class="mt-2 divide-y divide-ink-100 text-sm">
                @foreach ($document->resultRows() as $row)
                    <li class="flex flex-wrap items-start justify-between gap-2 py-2">
                        <div>
                            <p class="font-medium text-ink-800">{{ $row['name'] ?? '' }}</p>
                            @if (! empty($row['note']))
                                <p class="text-xs text-ink-400">{{ $row['note'] }}</p>
                            @endif
                        </div>
                        <p class="font-semibold text-ink-900">
                            {{ $row['value'] ?? '—' }}
                            <span class="text-xs font-normal text-ink-400">{{ $row['unit'] ?? '' }}</span>
                            @if (! empty($row['flag']))
                                <span class="ms-1 text-xs text-warning-700">{{ $row['flag'] }}</span>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-xs text-ink-400">
        {{ __('records.verify_hint') }}
        <a class="font-medium text-primary-700" href="{{ route('documents.verify', $document->verification_code) }}" dir="ltr">
            {{ url(route('documents.verify', $document->verification_code, false)) }}
        </a>
    </p>
</x-card>
