@php
    $autoPrint = $autoPrint ?? false;
    $locale = app()->getLocale();
    $dir = config("hakeem.locales.{$locale}.dir", 'rtl');
    $branding = app(\App\Support\Branding::class);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->downloadName() }}</title>
    <meta name="robots" content="noindex,nofollow">
    @include('partials.assets')
    <style>
        @page { size: A4; margin: 16mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-ink-50 text-ink-900">
    <div class="no-print mx-auto flex max-w-3xl justify-end gap-2 px-4 py-4">
        <button type="button" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white" onclick="window.print()">
            {{ __('records.download_pdf') }}
        </button>
        <a href="{{ url()->previous() }}" class="rounded-lg border border-ink-300 bg-white px-4 py-2 text-sm">{{ __('common.back') }}</a>
    </div>

    <article class="mx-auto max-w-3xl bg-white p-8 shadow-sm print:max-w-none print:p-0 print:shadow-none">
        <header class="flex items-start justify-between gap-4 border-b border-ink-200 pb-4">
            <div>
                <p class="text-sm font-semibold text-primary-700">{{ $branding->name() }}</p>
                <p class="mt-1 text-xl font-bold">{{ $document->clinic?->name }}</p>
                @if ($document->doctor)
                    <p class="mt-1 text-sm text-ink-600">{{ $document->doctor->name }}</p>
                @endif
            </div>
            <div class="text-end">
                <p class="text-xs font-medium text-ink-400">{{ __('records.verification') }}</p>
                <p class="mt-1 font-mono text-2xl font-bold tracking-widest" dir="ltr">{{ $document->verification_code }}</p>
                <p class="mt-1 text-xs text-ink-500">{{ $document->issued_at?->format('Y-m-d') }}</p>
            </div>
        </header>

        <h1 class="mt-6 text-lg font-semibold">{{ $document->type->label() }}: {{ $document->title }}</h1>
        <p class="mt-2 text-sm text-ink-600">{{ __('records.patient') }}: {{ $document->patient?->name }}</p>

        @if ($document->valid_from || $document->valid_until)
            <p class="mt-2 text-sm">
                {{ __('records.valid_range', [
                    'from' => optional($document->valid_from)->format('Y-m-d') ?: '—',
                    'until' => optional($document->valid_until)->format('Y-m-d') ?: '—',
                ]) }}
            </p>
        @endif

        @if ($document->body)
            <p class="mt-4 whitespace-pre-line text-sm leading-7">{{ $document->body }}</p>
        @endif

        @if ($document->medications())
            <table class="mt-5 w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-ink-200 text-start">
                        <th class="py-2 font-semibold">{{ __('records.med_name') }}</th>
                        <th class="py-2 font-semibold">{{ __('records.med_dose') }}</th>
                        <th class="py-2 font-semibold">{{ __('records.med_frequency') }}</th>
                        <th class="py-2 font-semibold">{{ __('records.med_duration') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($document->medications() as $medication)
                        <tr class="border-b border-ink-100">
                            <td class="py-2">{{ $medication['name'] ?? '' }}</td>
                            <td class="py-2">{{ $medication['dose'] ?? '' }}</td>
                            <td class="py-2">{{ $medication['frequency'] ?? '' }}</td>
                            <td class="py-2">{{ $medication['duration'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($document->resultRows())
            <table class="mt-5 w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-ink-200 text-start">
                        <th class="py-2 font-semibold">{{ __('records.result_name') }}</th>
                        <th class="py-2 font-semibold">{{ __('records.result_value') }}</th>
                        <th class="py-2 font-semibold">{{ __('records.result_flag') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($document->resultRows() as $row)
                        <tr class="border-b border-ink-100">
                            <td class="py-2">{{ $row['name'] ?? '' }}</td>
                            <td class="py-2">{{ $row['value'] ?? '—' }} {{ $row['unit'] ?? '' }}</td>
                            <td class="py-2">{{ $row['flag'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <footer class="mt-10 border-t border-ink-200 pt-4 text-xs text-ink-500">
            <p>{{ __('records.verified_footer') }}</p>
            <p class="mt-1" dir="ltr">{{ route('documents.verify', $document->verification_code) }}</p>
        </footer>
    </article>

    @if ($autoPrint)
        <script>window.addEventListener('load', () => window.print())</script>
    @endif
</body>
</html>
