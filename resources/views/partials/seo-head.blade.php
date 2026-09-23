<title>{{ $seo->title }}</title>
<link rel="canonical" href="{{ $seo->canonical }}">
<meta name="robots" content="{{ $seo->robots }}">
@if ($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif
@if ($seo->keywords)
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif
@foreach ($seo->openGraph() as $property => $content)
    @if (str_starts_with($property, 'twitter:'))
        <meta name="{{ $property }}" content="{{ $content }}">
    @else
        <meta property="{{ $property }}" content="{{ $content }}">
    @endif
@endforeach
@foreach ($seo->jsonLd as $graph)
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
