@props(['title' => null, 'bodyClass' => 'min-h-screen bg-ink-50', 'robots' => 'index,follow', 'seo' => null, 'themeColor' => '#30628b'])

@php
    $locale = app()->getLocale();
    $dir = config("hakeem.locales.{$locale}.dir", 'rtl');
    $document = $seo ?? \App\Support\SeoDocument::make(request(), $title, null, $robots);
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo-head', ['seo' => $document])
    @include('partials.tracking-head')
    @php $branding = $branding ?? app(\App\Support\Branding::class); @endphp
    @php
        $appLink = \App\Support\Deeplink::app(request()->getPathInfo());
        $androidPackage = config('hakeem.deeplinks.android_package');
        $iosStoreId = config('hakeem.deeplinks.ios_app_store_id');
    @endphp
    <link rel="alternate" href="{{ $appLink }}">
    <meta property="al:ios:url" content="{{ $appLink }}">
    <meta property="al:ios:app_name" content="{{ $branding->name() }}">
    @if (filled($iosStoreId))
        <meta property="al:ios:app_store_id" content="{{ $iosStoreId }}">
    @endif
    <meta property="al:android:url" content="{{ $appLink }}">
    @if (filled($androidPackage))
        <meta property="al:android:package" content="{{ $androidPackage }}">
        <meta property="al:android:app_name" content="{{ $branding->name() }}">
    @endif
    <link rel="icon" href="{{ $branding->faviconUrl() }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ $branding->shareImageUrl() }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="application-name" content="{{ $branding->name() }}">
    @include('partials.assets')
    @stack('head')
</head>
<body class="{{ $bodyClass }}">
    @include('partials.tracking-body')
    {{ $slot }}
    @stack('scripts')
</body>
</html>
