@props(['title' => null, 'bodyClass' => 'min-h-screen bg-ink-50'])

@php
    $locale = app()->getLocale();
    $dir = config("hakeem.locales.{$locale}.dir", 'rtl');
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.__('common.app_name') : __('common.app_name') }}</title>
    @include('partials.assets')
    @stack('head')
</head>
<body class="{{ $bodyClass }}">
    {{ $slot }}
    @stack('scripts')
</body>
</html>
