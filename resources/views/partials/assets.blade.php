<link rel="preconnect" href="https://fonts.bunny.net">
{{-- Tajawal for Arabic UI; Inter kept as Latin fallback for English pages. --}}
<link href="https://fonts.bunny.net/css?family=tajawal:400,500,600,700,800|inter:400,500,600,700&display=swap" rel="stylesheet">

@if (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    {{-- No compiled bundle yet: run `npm install && npm run build`. Until then the
         Tailwind browser runtime compiles the same design tokens on the fly. --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">{!! file_get_contents(resource_path('css/theme.css')) !!}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endif
